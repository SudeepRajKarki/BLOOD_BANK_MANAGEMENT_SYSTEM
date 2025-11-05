<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BloodRequest;
use App\Models\User;

class BloodRequestsTableSeeder extends Seeder
{
    public function run(): void
    {
        $receivers = User::where('role', 'receiver')->pluck('id')->toArray();

        foreach ($receivers as $r) {
            BloodRequest::create([
                'receiver_id' => $r,
                'blood_type' => ['A+','B+','O+','AB+'][rand(0,3)],
                'reason' => 'Medical need ' . rand(1,100),
                'quantity_ml' => rand(200, 2000),
                'priority' => ['High','Medium','Low'][rand(0,2)],
                'status' => 'Pending',
            ]);
        }
    }
}
