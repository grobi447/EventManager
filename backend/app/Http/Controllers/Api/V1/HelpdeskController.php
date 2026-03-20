<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Services\HelpdeskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpdeskController extends Controller
{
    public function __construct(
        private HelpdeskService $helpdeskService
    ) {}

    public function startChat(Request $request): JsonResponse
    {
        $chat = $this->helpdeskService->startChat(auth()->user());

        return response()->json([
            'success' => true,
            'data' => $chat,
            'message' => 'Chat started successfully.',
        ], 201);
    }

    public function sendMessage(Request $request, Chat $chat): JsonResponse
    {
        $request->validate(['content' => 'required|string']);

        if ($chat->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($chat->status === 'closed') {
            return response()->json(['success' => false, 'message' => 'Chat is closed.'], 400);
        }

        $messages = $this->helpdeskService->sendMessage($chat, auth()->user(), $request->content);

        return response()->json(['success' => true, 'data' => $messages]);
    }

    public function transfer(Request $request, Chat $chat): JsonResponse
    {
        if ($chat->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $chat = $this->helpdeskService->transferToAgent($chat);

        return response()->json([
            'success' => true,
            'data' => $chat,
            'message' => 'Transferred to human agent.',
        ]);
    }

    public function agentChats(Request $request): JsonResponse
    {
        if (! auth()->user()->isAgent()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $chats = Chat::whereIn('status', ['pending', 'active', 'closed'])
            ->with(['user', 'messages' => fn ($q) => $q->latest('created_at')->limit(1)])
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $chats]);
    }

    public function getMessages(Request $request, Chat $chat): JsonResponse
    {
        $user = auth()->user();

        if ($chat->user_id !== $user->id && ! $user->isAgent()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $messages = $chat->messages()->orderBy('created_at')->get();

        return response()->json(['success' => true, 'data' => $messages]);
    }

    public function agentRespond(Request $request, Chat $chat): JsonResponse
    {
        if (! auth()->user()->isAgent()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate(['content' => 'required|string']);

        $message = $this->helpdeskService->agentRespond($chat, auth()->user(), $request->content);

        return response()->json(['success' => true, 'data' => $message]);
    }

    public function closeChat(Request $request, Chat $chat): JsonResponse
    {
        $user = auth()->user();

        if ($chat->user_id !== $user->id && ! $user->isAgent()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $chat = $this->helpdeskService->closeChat($chat);

        return response()->json(['success' => true, 'data' => $chat, 'message' => 'Chat closed.']);
    }

    public function myChats(Request $request): JsonResponse
    {
        $chats = Chat::where('user_id', auth()->id())
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $chats,
        ]);
    }
    public function deleteChat(Chat $chat): JsonResponse
    {
        if ($chat->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }
        $chat->delete();
        return response()->json(['success' => true, 'message' => 'Chat deleted.']);
    }
}
