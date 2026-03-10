<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MfaSecret extends Model
{
    protected $fillable = [
        'user_id',
        'secret',
        'backup_codes',
    ];

    protected function casts(): array
    {
        return [
            'backup_codes' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}