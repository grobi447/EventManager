<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Gemini\Data\Content;
use Gemini\Enums\Role;
use Gemini\Laravel\Facades\Gemini;

class HelpdeskService
{
    public function startChat(User $user): Chat
    {
        return Chat::create([
            'user_id' => $user->id,
            'status' => 'ai',
            'ai_context' => [],
        ]);
    }

    public function sendMessage(Chat $chat, User $user, string $content): array
    {
        $userMessage = Message::create([
            'chat_id' => $chat->id,
            'sender_type' => 'user',
            'sender_id' => $user->id,
            'content' => $content,
        ]);

        if ($chat->status === 'ai') {
            $aiResponse = $this->getAiResponse($chat, $content);

            $aiMessage = Message::create([
                'chat_id' => $chat->id,
                'sender_type' => 'ai',
                'sender_id' => null,
                'content' => $aiResponse,
            ]);

            $context = $chat->ai_context ?? [];
            $context[] = ['role' => 'user', 'content' => $content];
            $context[] = ['role' => 'ai',   'content' => $aiResponse];
            $chat->update(['ai_context' => $context]);

            return [
                'user_message' => $userMessage,
                'ai_message' => $aiMessage,
            ];
        }

        return ['user_message' => $userMessage];
    }

    public function transferToAgent(Chat $chat): Chat
    {
        $chat->update(['status' => 'pending']);

        Message::create([
            'chat_id' => $chat->id,
            'sender_type' => 'ai',
            'sender_id' => null,
            'content' => 'Your request has been transferred to a human agent. Please wait.',
        ]);

        return $chat->fresh();
    }

    public function agentRespond(Chat $chat, User $agent, string $content): Message
    {
        if ($chat->status === 'pending') {
            $chat->update([
                'status' => 'active',
                'agent_id' => $agent->id,
            ]);
        }

        return Message::create([
            'chat_id' => $chat->id,
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
            'content' => $content,
        ]);
    }

    public function closeChat(Chat $chat): Chat
    {
        $chat->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return $chat->fresh();
    }

    private function getAiResponse(Chat $chat, string $userMessage): string
    {
        $context = $chat->ai_context ?? [];

        $history = [];
        foreach ($context as $msg) {
            $history[] = Content::parse(
                part: $msg['content'],
                role: $msg['role'] === 'ai' ? Role::MODEL : Role::USER
            );
        }

        try {
            $geminiChat = Gemini::generativeModel(model: 'gemini-2.5-flash')
            ->withSystemInstruction('You are a helpful customer support agent for EventManager, a web application for managing personal events. Users can create events with a title, date/time, and optional description. They can list, update, and delete their events. Help users with questions specifically about using EventManager. If the user explicitly asks for a human agent, say TRANSFER_REQUESTED.')
            ->startChat(history: $history);
            $response = $geminiChat->sendMessage($userMessage);

            return $response->text();
        } catch (\Exception $e) {
            \Log::error('Gemini error: '.$e->getMessage());

            return 'I apologize, I am currently unavailable. Please try again later.';
        }
    }
}
