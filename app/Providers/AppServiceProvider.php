<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            if (auth()->check()) {
                $user = auth()->user();
                $conversations = \App\Models\AiConversation::where('user_id', $user->id)
                    ->orderBy('updated_at', 'desc')
                    ->get();
                
                $activeConversation = null;
                $conversationId = request()->query('conversation_id') ?: request()->input('conversation_id');
                if ($conversationId) {
                    $activeConversation = \App\Models\AiConversation::where('user_id', $user->id)
                        ->where('id', $conversationId)
                        ->with('messages')
                        ->first();
                }

                if (!$activeConversation && $conversations->isNotEmpty()) {
                    $activeConversation = \App\Models\AiConversation::where('user_id', $user->id)
                        ->where('id', $conversations->first()->id)
                        ->with('messages')
                        ->first();
                }

                $role = session('active_role', $user->role);
                if ($role === 'employee') {
                    $suggestedQuestions = [
                        "Show my assigned tasks status",
                        "What are my logged hours this week?",
                        "Show my recent attendance clock logs"
                    ];
                } else {
                    $suggestedQuestions = [
                        "Which employee should be promoted next quarter and why?",
                        "Who is the top performer this month?",
                        "Show active burnout and deadline risks alerts",
                        "Which team is overloading and overloaded?",
                        "Show daily attendance logs summary today",
                        "Show gitlab commit contribution ranking"
                    ];
                }

                $view->with([
                    'conversations' => $conversations,
                    'activeConversation' => $activeConversation,
                    'suggestedQuestions' => $suggestedQuestions
                ]);
            }
        });
    }
}
