<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'occurs_at',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'occurs_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendees()
    {
        return $this->belongsToMany(User::class, 'event_attendees')->withTimestamps();
    }

    public function attendeeCount(): int
    {
        return $this->attendees()->count();
    }
}