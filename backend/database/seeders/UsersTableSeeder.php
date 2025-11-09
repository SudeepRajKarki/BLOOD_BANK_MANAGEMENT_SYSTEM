<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'email_verified_at' => now(),
            'is_verified' => true,
        ]);

        // Nepal cities to use for realistic locations
        $nepalCities = [
            'Kathmandu', 'Lalitpur', 'Bhaktapur', 'Pokhara', 'Butwal', 
            'Biratnagar', 'Hetauda', 'Dharan', 'Janakpur', 'Birgunj', 
            'Nepalgunj', 'Mahendranagar', 'Chitwan', 'Itahari', 'Bharatpur'
        ];

        $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

        // Create 30+ donors with realistic data
        for ($i = 1; $i <= 35; $i++) {
            $donor = User::factory()->create([
                'name' => 'Donor ' . $i,
                'email' => 'donor' . $i . '@example.com',
                'role' => 'donor',
                'blood_type' => $bloodTypes[array_rand($bloodTypes)],
                'location' => $nepalCities[array_rand($nepalCities)],
                'phone' => '98' . str_pad(rand(1000000, 9999999), 8, '0', STR_PAD_LEFT),
                'is_verified' => rand(0, 10) > 1, // 90% verified
                'health_status' => rand(0, 8) ? 'Good' : 'Minor issue',
            ]);

            // Set last_donation_date based on eligibility (56-day rule)
            // Some donors are eligible, some are not
            $daysAgo = rand(0, 200);
            if ($daysAgo >= 56) {
                // Eligible donors (56-200 days ago)
                $donor->last_donation_date = Carbon::now()->subDays($daysAgo)->toDateString();
            } else {
                // Some ineligible donors (0-55 days ago)
                if (rand(0, 1)) {
                    $donor->last_donation_date = Carbon::now()->subDays($daysAgo)->toDateString();
                }
            }
            $donor->save();
        }

        // Create 15+ receivers with realistic data
        for ($i = 1; $i <= 18; $i++) {
            User::factory()->create([
                'name' => 'Receiver ' . $i,
                'email' => 'receiver' . $i . '@example.com',
                'role' => 'receiver',
                'blood_type' => $bloodTypes[array_rand($bloodTypes)],
                'location' => $nepalCities[array_rand($nepalCities)],
                'phone' => '98' . str_pad(rand(1000000, 9999999), 8, '0', STR_PAD_LEFT),
                'is_verified' => true,
            ]);
        }
    }
}
