<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AiConversation;
use App\Models\AiMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiChatFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::create([
            'name' => 'Amelia Brand',
            'email' => 'amelia@company.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $this->employee = User::create([
            'name' => 'Sneha Gupta',
            'email' => 'sneha@company.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
        ]);
    }

    /**
     * Test manager can access the AI Agent Chat index page.
     */
    public function test_manager_can_access_ai_chat_page(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.ai-chat.index'));

        $response->assertStatus(200);
        $response->assertSee('AI Assistant Chat');
        $response->assertSee('New Conversation');
    }

    /**
     * Test employee can access the AI Agent Chat page (scoped to their own portal context).
     */
    public function test_employee_can_access_ai_chat_page(): void
    {
        $response = $this->actingAs($this->employee)
            ->withSession(['active_role' => 'employee'])
            ->get(route('dashboard.ai-chat.index'));

        $response->assertStatus(200);
        $response->assertSee('AI Assistant Chat');
    }

    /**
     * Test sending a message successfully creates conversations and messages, and returns JSON structure.
     */
    public function test_user_can_send_chat_message(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->postJson(route('dashboard.ai-chat.send'), [
                'question' => 'Who is the top performer this month?',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'conversation_id',
            'conversation_title',
            'message' => [
                'id',
                'role',
                'content',
                'data_sources',
                'structured_response' => [
                    'direct_answer',
                    'supporting_metrics',
                    'ai_analysis',
                    'recommendations',
                    'data_sources_used',
                    'visual_type'
                ],
                'created_at'
            ]
        ]);

        $this->assertDatabaseHas('ai_conversations', [
            'user_id' => $this->manager->id,
        ]);

        $this->assertDatabaseHas('ai_messages', [
            'role' => 'user',
            'content' => 'Who is the top performer this month?',
        ]);

        $this->assertDatabaseHas('ai_messages', [
            'role' => 'assistant',
        ]);
    }

    /**
     * Test user can query worst performing employee.
     */
    public function test_user_can_query_worst_performer(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->postJson(route('dashboard.ai-chat.send'), [
                'question' => 'Who is the worst performer this week?',
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
        ]);
        
        $this->assertDatabaseHas('ai_messages', [
            'role' => 'user',
            'content' => 'Who is the worst performer this week?',
        ]);

        $this->assertDatabaseHas('ai_messages', [
            'role' => 'assistant',
        ]);
    }

    /**
     * Test user can query measures to resolve.
     */
    public function test_user_can_query_measures_to_resolve(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->postJson(route('dashboard.ai-chat.send'), [
                'question' => 'Suggest measures to resolve team overload issues',
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
        ]);
        
        $this->assertDatabaseHas('ai_messages', [
            'role' => 'user',
            'content' => 'Suggest measures to resolve team overload issues',
        ]);

        $this->assertDatabaseHas('ai_messages', [
            'role' => 'assistant',
        ]);

        $data = $response->json();
        $this->assertEquals('table', $data['message']['structured_response']['visual_type']);
        $this->assertContains('Actionable Resolution Measure', $data['message']['structured_response']['visual_data']['headers']);
    }

    /**
     * Test user can clear messages in a conversation.
     */
    public function test_user_can_clear_conversation(): void
    {
        $conversation = AiConversation::create([
            'user_id' => $this->manager->id,
            'title' => 'Test Discussion'
        ]);

        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hello'
        ]);

        $this->assertDatabaseHas('ai_messages', ['conversation_id' => $conversation->id]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.ai-chat.clear', $conversation->id));

        $response->assertRedirect(route('dashboard.ai-chat.index', ['conversation_id' => $conversation->id]));
        $this->assertDatabaseMissing('ai_messages', ['conversation_id' => $conversation->id]);
    }

    /**
     * Test user can delete a conversation.
     */
    public function test_user_can_delete_conversation(): void
    {
        $conversation = AiConversation::create([
            'user_id' => $this->manager->id,
            'title' => 'Test Discussion'
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->delete(route('dashboard.ai-chat.destroy', $conversation->id));

        $response->assertRedirect(route('dashboard.ai-chat.index'));
        $this->assertDatabaseMissing('ai_conversations', ['id' => $conversation->id]);
    }

    /**
     * Test user can export a conversation history.
     */
    public function test_user_can_export_conversation(): void
    {
        $conversation = AiConversation::create([
            'user_id' => $this->manager->id,
            'title' => 'Test Discussion'
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.ai-chat.export', $conversation->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        
        $data = json_decode($response->streamedContent(), true);
        $this->assertEquals($conversation->id, $data['conversation_id']);
        $this->assertEquals('Test Discussion', $data['title']);
    }
}
