<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailVerification;
use App\Models\User;

class EmailVerificationsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereNull('email_verified_at')->pluck('id')->toArray();
        foreach ($users as $uid) {
            EmailVerification::create([
                'user_id' => $uid,
                'otp_code' => str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT),
                'is_used' => false,
                'expires_at' => now()->addHours(24),
            ]);
        }
    }
}
