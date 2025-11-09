<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BloodRequest;
use App\Models\User;
use App\Models\BloodInventory;
use Carbon\Carbon;

class BloodRequestsTableSeeder extends Seeder
{
    public function run(): void
    {
        $receivers = User::where('role', 'receiver')->get();
        $nepalCities = [
            'Kathmandu', 'Lalitpur', 'Bhaktapur', 'Pokhara', 'Butwal', 
            'Biratnagar', 'Hetauda', 'Dharan', 'Janakpur', 'Birgunj', 
            'Nepalgunj', 'Mahendranagar', 'Chitwan'
        ];

        $reasons = [
            'Emergency surgery requiring blood transfusion',
            'Accident victim in critical condition',
            'Cancer treatment requiring regular blood transfusions',
            'Childbirth complications',
            'Chronic anemia requiring blood support',
            'Organ transplant surgery',
            'Blood loss from trauma',
            'Pre-surgery blood requirement',
            'Thalassemia patient requiring regular transfusions',
            'Post-operative blood requirement',
        ];

        $priorities = ['High', 'Medium', 'Low'];
        $statuses = ['Pending', 'Approved', 'Rejected'];

        if ($receivers->isEmpty()) {
            return;
        }

        // Create requests in the past (for historical data)
        for ($i = 0; $i < 40; $i++) {
            $receiver = $receivers->random();
            $createdAt = Carbon::now()->subDays(rand(1, 90));
            $status = $statuses[array_rand($statuses)];
            
            // Check inventory to determine if request should be approved or pending
            $bloodType = $receiver->blood_type;
            $inventory = BloodInventory::where('blood_type', $bloodType)->sum('quantity_ml');
            $quantity = rand(200, 2000);
            
            // If inventory was sufficient and status is approved, mark as approved
            if ($status === 'Approved' && $inventory >= $quantity) {
                // Request was approved (sent to admin)
            } else {
                // Request is pending or rejected, or inventory was insufficient (sent to donors)
                $status = rand(0, 2) ? 'Pending' : 'Rejected';
            }

            BloodRequest::create([
                'receiver_id' => $receiver->id,
                'blood_type' => $bloodType,
                'reason' => $reasons[array_rand($reasons)],
                'quantity_ml' => $quantity,
                'priority' => $priorities[array_rand($priorities)],
                'status' => $status,
                'location' => $nepalCities[array_rand($nepalCities)],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        // Create recent requests (last 7 days)
        for ($i = 0; $i < 15; $i++) {
            $receiver = $receivers->random();
            $createdAt = Carbon::now()->subDays(rand(0, 7));
            
            BloodRequest::create([
                'receiver_id' => $receiver->id,
                'blood_type' => $receiver->blood_type,
                'reason' => $reasons[array_rand($reasons)],
                'quantity_ml' => rand(200, 2000),
                'priority' => $priorities[array_rand($priorities)],
                'status' => 'Pending',
                'location' => $nepalCities[array_rand($nepalCities)],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
