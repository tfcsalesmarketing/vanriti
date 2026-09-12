<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestCredentialsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'phone' => '9876543210',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $customerRole = Role::where('slug', 'customer')->first();
        if ($customerRole && ! $user->roles()->where('roles.id', $customerRole->id)->exists()) {
            $user->roles()->attach($customerRole->id);
        }

        $admin = Admin::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@vanriti.com')],
            [
                'name' => 'Test Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'Vanriti@2026')),
                'is_super_admin' => true,
                'status' => 'active',
            ]
        );

        $superAdmin = Role::where('slug', 'super-admin')->first();
        if ($superAdmin && ! $admin->roles()->where('roles.id', $superAdmin->id)->exists()) {
            $admin->roles()->attach($superAdmin->id);
        }
    }
}