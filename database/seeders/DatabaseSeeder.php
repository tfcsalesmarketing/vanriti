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

        // Test credentials must never be seeded inadvertently: seeding would
        // reset the admin password and force super-admin promotion. Require an
        // explicit opt-in AND a non-production environment.
        if (! app()->environment('production') && filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL)) {
            $this->call(TestCredentialsSeeder::class);
        }

        $this->command?->info('Database seeded successfully.');
    }
}
