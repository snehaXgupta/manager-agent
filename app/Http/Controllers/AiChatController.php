<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Services\AiManagerAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AiChatController extends Controller
{
    protected $aiService;

    public function __construct(AiManagerAgentService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Show the ChatGPT-style conversational assistant page.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Search filter for conversations history
        $search = $request->query('search', '');
        $conversationsQuery = AiConversation::where('user_id', $user->id);
        if (!empty($search)) {
            $conversationsQuery->where('title', 'like', "%{$search}%");
        }
        $conversations = $conversationsQuery->orderBy('updated_at', 'desc')->get();

        // Get current active conversation
        $activeConversation = null;
        if ($request->has('conversation_id')) {
            $activeConversation = AiConversation::where('user_id', $user->id)
                ->where('id', $request->conversation_id)
                ->with('messages')
                ->first();
        }

        if (!$activeConversation && $conversations->isNotEmpty()) {
            $activeConversation = AiConversation::where('user_id', $user->id)
                ->where('id', $conversations->first()->id)
                ->with('messages')
                ->first();
        }

        // Suggested questions based on role
        $suggestedQuestions = $this->getSuggestedQuestions($user);

        return view('dashboard.ai-chat.index', compact('conversations', 'activeConversation', 'suggestedQuestions', 'search'));
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:1000',
            'conversation_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = auth()->user();
        $question = $request->question;

        // 1. Resolve or create conversation
        if ($request->filled('conversation_id')) {
            $conversation = AiConversation::where('user_id', $user->id)->findOrFail($request->conversation_id);
        } else {
            // Title is the first 40 chars of question
            $title = substr($question, 0, 40) . (strlen($question) > 40 ? '...' : '');
            $conversation = AiConversation::create([
                'user_id' => $user->id,
                'title' => $title
            ]);
        }

        // 2. Save User Message
        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $question
        ]);

        // 3. Compile history context
        $chatHistory = $conversation->messages()
            ->get(['role', 'content'])
            ->toArray();

        // Check if client requested streaming
        $stream = $request->input('stream', false) || $request->header('X-Request-Stream', false);

        if ($stream) {
            return response()->stream(function() use ($question, $chatHistory, $request, $conversation) {
                $accumulated = '';
                $this->aiService->askStream(
                    $question,
                    $chatHistory,
                    $request->start_date,
                    $request->end_date,
                    function($chunk) use (&$accumulated) {
                        echo $chunk;
                        $accumulated .= $chunk;
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }
                );

                $marker = "\n\n[STRUCTURED_METRICS_DATA_JSON]\n";
                $parts = explode($marker, $accumulated);
                $directAnswer = $parts[0] ?? '';
                $jsonData = $parts[1] ?? '';

                $reply = json_decode($jsonData, true) ?: [];

                AiMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $reply['direct_answer'] ?? $directAnswer ?: 'Insufficient data available.',
                    'data_sources' => $reply['data_sources_used'] ?? [],
                    'structured_response' => $reply
                ]);

                $conversation->touch();
            }, 200, [
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'text/event-stream',
                'X-Accel-Encoding' => 'no',
                'X-Conversation-Id' => $conversation->id,
                'X-Conversation-Title' => urlencode($conversation->title),
            ]);
        }

        // 4. Invoke AI Service (Synchronously for fallback/tests)
        $reply = $this->aiService->ask($question, $chatHistory, $request->start_date, $request->end_date);

        // 5. Save Assistant Message
        $aiMessage = AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $reply['direct_answer'] ?? 'Insufficient data available.',
            'data_sources' => $reply['data_sources_used'] ?? [],
            'structured_response' => $reply
        ]);

        // Touch conversation to update timestamps
        $conversation->touch();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'conversation_title' => $conversation->title,
                'message' => [
                    'id' => $aiMessage->id,
                    'role' => 'assistant',
                    'content' => $aiMessage->content,
                    'data_sources' => $aiMessage->data_sources,
                    'structured_response' => $aiMessage->structured_response,
                    'created_at' => $aiMessage->created_at->toDateTimeString()
                ]
            ]);
        }

        return redirect()->route('dashboard.ai-chat.index', ['conversation_id' => $conversation->id]);
    }

    /**
     * Clear all messages in a conversation.
     */
    public function clearConversation(Request $request, $id)
    {
        $user = auth()->user();
        $conversation = AiConversation::where('user_id', $user->id)->findOrFail($id);
        $conversation->messages()->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Conversation messages cleared successfully.']);
        }

        return redirect()->route('dashboard.ai-chat.index', ['conversation_id' => $conversation->id])
            ->with('success', 'Conversation messages cleared successfully.');
    }

    /**
     * Delete an entire conversation.
     */
    public function destroy(Request $request, $id)
    {
        $user = auth()->user();
        $conversation = AiConversation::where('user_id', $user->id)->findOrFail($id);
        $conversation->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Conversation deleted successfully.']);
        }

        return redirect()->route('dashboard.ai-chat.index')
            ->with('success', 'Conversation deleted successfully.');
    }

    /**
     * Export conversation chat.
     */
    public function exportChat($id)
    {
        $user = auth()->user();
        $conversation = AiConversation::where('user_id', $user->id)->with('messages')->findOrFail($id);

        $exportData = [
            'conversation_id' => $conversation->id,
            'title' => $conversation->title,
            'created_at' => $conversation->created_at->toDateTimeString(),
            'messages' => $conversation->messages->map(function ($msg) {
                return [
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'data_sources' => $msg->data_sources,
                    'structured' => $msg->structured_response,
                    'timestamp' => $msg->created_at->toDateTimeString()
                ];
            })
        ];

        return response()->streamDownload(function () use ($exportData) {
            echo json_encode($exportData, JSON_PRETTY_PRINT);
        }, "conversation-export-{$id}.json", [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Suggested questions list.
     */
    protected function getSuggestedQuestions($user): array
    {
        $role = session('active_role', $user->role);
        if ($role === 'employee') {
            return [
                "Show my assigned tasks status",
                "What are my logged hours this week?",
                "Show my recent attendance clock logs"
            ];
        }

        return [
            "Which employee should be promoted next quarter and why?",
            "Who is the top performer this month?",
            "Show active burnout and deadline risks alerts",
            "Which team is overloading and overloaded?",
            "Show daily attendance logs summary today",
            "Show gitlab commit contribution ranking"
        ];
    }
}
