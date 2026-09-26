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
        ]);

        // Test credentials must never be seeded in production: seeding would
        // reset the admin password and force super-admin promotion.
        if (! app()->environment('production')) {
            $this->call(TestCredentialsSeeder::class);
        }

        $this->command?->info('Database seeded successfully.');
    }
}
