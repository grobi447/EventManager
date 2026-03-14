<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventAttendeeController extends Controller
{
    public function join(Request $request, Event $event): JsonResponse
    {
        $user = auth()->user();

        if ($event->attendees()->where('user_id', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Already joined.'], 400);
        }

        $event->attendees()->attach($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Joined event successfully.',
            'data' => ['attendee_count' => $event->attendees()->count()],
        ]);
    }

    public function leave(Request $request, Event $event): JsonResponse
    {
        $user = auth()->user();

        if (! $event->attendees()->where('user_id', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Not joined.'], 400);
        }

        $event->attendees()->detach($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Left event successfully.',
            'data' => ['attendee_count' => $event->attendees()->count()],
        ]);
    }

    public function publicEvents(): JsonResponse
    {
        $events = Event::with(['user:id,name', 'attendees:id'])
            ->withCount('attendees')
            ->where('occurs_at', '>=', now())
            ->orderBy('occurs_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'meta' => [
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
            ],
        ]);
    }

    public function joinedEvents(Request $request): JsonResponse
    {
        $events = auth()->user()->joinedEvents()
            ->with(['user:id,name'])
            ->withCount('attendees')
            ->orderBy('occurs_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'meta' => [
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
            ],
        ]);
    }
}
