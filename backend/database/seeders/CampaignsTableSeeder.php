<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campaign;
use App\Models\User;
use Carbon\Carbon;

class CampaignsTableSeeder extends Seeder
{
    public function run(): void
    {
        $admins = User::where('role', 'admin')->pluck('id')->toArray();
        if (empty($admins)) {
            // Create admin if doesn't exist
            $admin = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
            $admins = [$admin->id];
        }

        $nepalCities = [
            'Kathmandu', 'Lalitpur', 'Bhaktapur', 'Pokhara', 'Butwal', 
            'Biratnagar', 'Hetauda', 'Dharan', 'Janakpur', 'Birgunj', 
            'Nepalgunj', 'Mahendranagar', 'Chitwan'
        ];

        $campaignDescriptions = [
            'Community blood drive organized by local hospital',
            'Emergency blood donation camp for disaster relief',
            'Regular monthly blood donation campaign',
            'Festival season blood drive',
            'University campus blood donation event',
            'Corporate blood donation initiative',
            'Religious organization blood drive',
            'Red Cross blood donation camp',
        ];

        // Create 8-10 completed campaigns (in the past)
        for ($i = 1; $i <= 10; $i++) {
            $campaignDate = Carbon::now()->subDays(rand(5, 90));
            Campaign::create([
                'location' => $nepalCities[array_rand($nepalCities)],
                'date' => $campaignDate->toDateString(),
                'created_by' => $admins[array_rand($admins)],
                'status' => 'Completed',
                'description' => $campaignDescriptions[array_rand($campaignDescriptions)],
            ]);
        }

        // Create 3-5 ongoing campaigns
        for ($i = 1; $i <= 4; $i++) {
            Campaign::create([
                'location' => $nepalCities[array_rand($nepalCities)],
                'date' => Carbon::now()->subDays(rand(0, 3))->toDateString(),
                'created_by' => $admins[array_rand($admins)],
                'status' => 'Ongoing',
                'description' => $campaignDescriptions[array_rand($campaignDescriptions)],
            ]);
        }

        // Create 5-7 upcoming campaigns
        for ($i = 1; $i <= 6; $i++) {
            Campaign::create([
                'location' => $nepalCities[array_rand($nepalCities)],
                'date' => Carbon::now()->addDays(rand(1, 30))->toDateString(),
                'created_by' => $admins[array_rand($admins)],
                'status' => 'Upcoming',
                'description' => $campaignDescriptions[array_rand($campaignDescriptions)],
            ]);
        }
    }
}
