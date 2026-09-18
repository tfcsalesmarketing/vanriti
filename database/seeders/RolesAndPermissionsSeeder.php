<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public static array $permissions = [
        'view dashboard',
        'manage admins',
        'manage roles',
        'manage products',
        'manage categories',
        'manage inventory',
        'manage orders',
        'manage payments',
        'manage shipments',
        'manage returns',
        'manage refunds',
        'manage customers',
        'manage coupons',
        'manage reviews',
        'manage blogs',
        'manage banners',
        'manage pages',
        'manage faqs',
        'manage settings',
        'manage newsletters',
        'manage media',
        'view reports',
        'manage notifications',
        'manage activities',
        'review dadi product profiles',
    ];

    public function run(): void
    {
        foreach (self::$permissions as $permission) {
            Permission::updateOrCreate(['slug' => str($permission)->slug()], [
                'name' => ucfirst($permission),
            ]);
        }

        $superAdmin = Role::updateOrCreate(['slug' => 'super-admin'], [
            'name' => 'Super Admin',
            'description' => 'Full access to the admin panel',
            'is_system' => true,
        ]);
        $superAdmin->permissions()->sync(Permission::all()->pluck('id'));

        $manager = Role::updateOrCreate(['slug' => 'manager'], [
            'name' => 'Manager',
            'description' => 'Manage catalogue, orders, customers and content',
            'is_system' => true,
        ]);
        $manager->permissions()->sync(Permission::whereIn('slug', [
            'view-dashboard',
            'manage-products',
            'manage-categories',
            'manage-inventory',
            'manage-orders',
            'manage-shipments',
            'manage-returns',
            'manage-refunds',
            'manage-customers',
            'manage-coupons',
            'manage-reviews',
            'manage-blogs',
            'manage-banners',
            'manage-pages',
            'manage-faqs',
            'manage-newsletters',
            'manage-media',
            'view-reports',
            'manage-activities',
        ])->pluck('id'));

        $support = Role::updateOrCreate(['slug' => 'support'], [
            'name' => 'Support',
            'description' => 'Handle orders, returns and customer queries',
            'is_system' => true,
        ]);
        $support->permissions()->sync(Permission::whereIn('slug', [
            'view-dashboard',
            'manage-orders',
            'manage-shipments',
            'manage-returns',
            'manage-refunds',
            'manage-customers',
            'manage-reviews',
        ])->pluck('id'));

        $super = Admin::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@vanriti.com')],
            [
                'name' => 'VANRITI Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'Vanriti@2026')),
                'status' => 'active',
            ]
        );

        // is_super_admin is guarded; promote explicitly.
        $super->makeSuperAdmin();

        if (! $super->roles()->where('slug', 'super-admin')->exists()) {
            $super->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail()->id);
        }
    }
}
