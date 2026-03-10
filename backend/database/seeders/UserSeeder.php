<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@eventmanager.com',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        User::create([
            'name'     => 'Helpdesk Agent',
            'email'    => 'agent@eventmanager.com',
            'password' => Hash::make('password'),
            'role'     => 'helpdesk_agent',
        ]);

        User::create([
            'name'     => 'Regular User',
            'email'    => 'user@eventmanager.com',
            'password' => Hash::make('password'),
            'role'     => 'user',
        ]);
    }
}