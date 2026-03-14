<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function __construct(
        private EventService $eventService
    ) {}

    public function index(): JsonResponse
    {
        $events = Event::with(['user:id,name'])
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

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->createEvent(
            auth()->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => $event,
            'message' => 'Event created successfully',
        ], 201);
    }

    public function show(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        return response()->json([
            'success' => true,
            'data' => $event,
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $event = $this->eventService->updateEvent(
            $event,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => $event,
            'message' => 'Event updated successfully',
        ]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        $this->eventService->deleteEvent($event);

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully',
        ]);
    }
}
