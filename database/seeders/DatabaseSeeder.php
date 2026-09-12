<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            CouponSeeder::class,
            FaqSeeder::class,
            BannerSeeder::class,
            ReturnPolicySeeder::class,
            CancellationPolicySeeder::class,
            DisclaimerPolicySeeder::class,
            TestCredentialsSeeder::class,
        ]);

        $this->command?->info('Database seeded successfully.');
    }
}