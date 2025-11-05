<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;

class NotificationsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id')->toArray();
        foreach ($users as $uid) {
            Notification::create([
                'user_id' => $uid,
                'message' => 'Welcome user ' . $uid,
                'type' => 'email',
                'is_read' => false,
            ]);
        }
    }
}
