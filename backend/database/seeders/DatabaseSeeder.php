<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\UsersTableSeeder;
use Database\Seeders\CampaignsTableSeeder;
use Database\Seeders\BloodInventorySeeder;
use Database\Seeders\DonationsTableSeeder;
use Database\Seeders\BloodRequestsTableSeeder;
use Database\Seeders\DonorMatchesSeeder;
use Database\Seeders\ChurnPredictionsSeeder;
use Database\Seeders\DemandForecastsSeeder;
use Database\Seeders\NotificationsSeeder;
use Database\Seeders\EmailVerificationsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UsersTableSeeder::class,
            CampaignsTableSeeder::class,
            BloodInventorySeeder::class,
            DonationsTableSeeder::class,
            BloodRequestsTableSeeder::class,
            DonorMatchesSeeder::class,
            ChurnPredictionsSeeder::class,
            DemandForecastsSeeder::class,
            NotificationsSeeder::class,
            EmailVerificationsSeeder::class,
        ]);
    }
}
