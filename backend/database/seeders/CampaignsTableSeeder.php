<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campaign;
use App\Models\User;

class CampaignsTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'admin')->orWhere('role', 'donor')->pluck('id')->toArray();

        for ($i = 1; $i <= 3; $i++) {
            Campaign::create([
                'location' => 'City ' . $i,
                'date' => now()->addDays($i),
                'created_by' => $users[array_rand($users)],
                'status' => 'Upcoming',
                'description' => 'Campaign ' . $i . ' description',
            ]);
        }
    }
}
