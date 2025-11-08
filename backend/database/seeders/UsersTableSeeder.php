<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

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
        ]);

        // Nepal cities to use for realistic locations
        $nepalCities = [
            'Kathmandu','Lalitpur','Bhaktapur','Pokhara','Butwal','Biratnagar',
            'Hetauda','Dharan','Janakpur','Birgunj','Nepalgunj','Mahendranagar'
        ];

        // Some donors
        User::factory()->count(8)->create()->each(function ($u) use ($nepalCities) {
            $u->role = 'donor';
            $u->blood_type = ['A+','A-','B+','B-','O+','O-','AB+','AB-'][rand(0,7)];
            $u->location = $nepalCities[array_rand($nepalCities)];
            // randomly set last_donation_date to simulate eligibility
            if (rand(0, 1)) {
                $u->last_donation_date = now()->subDays(rand(30, 400));
            }
            $u->is_verified = (bool) rand(0, 1);
            $u->health_status = rand(0, 5) ? 'Good' : 'Minor issue';
            $u->save();
        });

        // Some receivers
        User::factory()->count(5)->create()->each(function ($u) use ($nepalCities) {
            $u->role = 'receiver';
            $u->blood_type = ['A+','A-','B+','B-','O+','O-','AB+','AB-'][rand(0,7)];
            $u->location = $nepalCities[array_rand($nepalCities)];
            $u->save();
        });
    }
}
