<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class EventService
{
    public function getUserEvents(User $user): LengthAwarePaginator
    {
        return Event::where('user_id', $user->id)
            ->orderBy('occurs_at')
            ->paginate(15);
    }

    public function createEvent(User $user, array $data): Event
    {
        return Event::create([
            'user_id'     => $user->id,
            'title'       => $data['title'],
            'occurs_at'   => $data['occurs_at'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function updateEvent(Event $event, array $data): Event
    {
        $event->update([
            'description' => $data['description'],
        ]);

        return $event->fresh();
    }

    public function deleteEvent(Event $event): void
    {
        $event->delete();
    }
}