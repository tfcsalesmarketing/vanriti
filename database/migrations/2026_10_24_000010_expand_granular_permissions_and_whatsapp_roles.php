<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add 'group' column to permissions table if missing
        if (!Schema::hasColumn('permissions', 'group')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('group', 50)->default('general')->after('name');
            });
        }

        // 2. Define Granular Permissions with Groupings
        $permissions = [
            // WhatsApp CRM Suite
            [
                'name' => 'View Live Chat Inbox',
                'slug' => 'view-whatsapp-inbox',
                'group' => 'whatsapp',
                'description' => 'Read-only access to view WhatsApp customer conversations and active chats.',
            ],
            [
                'name' => 'Manage Live Chat Inbox',
                'slug' => 'manage-whatsapp-inbox',
                'group' => 'whatsapp',
                'description' => 'Reply to customer messages, send media, templates, assign staff, and add notes.',
            ],
            [
                'name' => 'View Broadcast Campaigns',
                'slug' => 'view-whatsapp-campaigns',
                'group' => 'whatsapp',
                'description' => 'View broadcast marketing campaigns and delivery metrics.',
            ],
            [
                'name' => 'Manage Broadcast Campaigns',
                'slug' => 'manage-whatsapp-campaigns',
                'group' => 'whatsapp',
                'description' => 'Create, test-send, and dispatch WhatsApp marketing broadcasts.',
            ],
            [
                'name' => 'Manage Chatbot & Rules',
                'slug' => 'manage-whatsapp-bot',
                'group' => 'whatsapp',
                'description' => 'Configure chatbot auto-reply rules, menus, and quick-reply canned responses.',
            ],
            [
                'name' => 'Manage Meta Templates',
                'slug' => 'manage-whatsapp-templates',
                'group' => 'whatsapp',
                'description' => 'Create, delete, and sync Meta Cloud message templates.',
            ],
            [
                'name' => 'Manage WhatsApp Profile',
                'slug' => 'manage-whatsapp-profile',
                'group' => 'whatsapp',
                'description' => 'View WhatsApp account health, quality rating, and update business profile.',
            ],

            // Catalogue & Inventory
            [
                'name' => 'View Products',
                'slug' => 'view-products',
                'group' => 'catalogue',
                'description' => 'View product listings, details, and SKUs.',
            ],
            [
                'name' => 'Manage Products',
                'slug' => 'manage-products',
                'group' => 'catalogue',
                'description' => 'Create, edit, delete products, variants, images, stock, and toggle status.',
            ],
            [
                'name' => 'View Categories',
                'slug' => 'view-categories',
                'group' => 'catalogue',
                'description' => 'View store product categories and main categories.',
            ],
            [
                'name' => 'Manage Categories',
                'slug' => 'manage-categories',
                'group' => 'catalogue',
                'description' => 'Create, edit, and delete product categories and main categories.',
            ],
            [
                'name' => 'View Inventory',
                'slug' => 'view-inventory',
                'group' => 'catalogue',
                'description' => 'View inventory stock levels and out-of-stock items.',
            ],
            [
                'name' => 'Manage Inventory',
                'slug' => 'manage-inventory',
                'group' => 'catalogue',
                'description' => 'Update stock counts, batch adjust inventory, and trigger restock alerts.',
            ],
            [
                'name' => 'Review Dadi AI Profiles',
                'slug' => 'review-dadi-product-profiles',
                'group' => 'catalogue',
                'description' => 'Review, approve, and refine AI-generated product profiles and tags.',
            ],

            // Orders & Shipping
            [
                'name' => 'View Orders',
                'slug' => 'view-orders',
                'group' => 'orders',
                'description' => 'View customer order details, invoices, and payment statuses.',
            ],
            [
                'name' => 'Manage Orders',
                'slug' => 'manage-orders',
                'group' => 'orders',
                'description' => 'Change order statuses, add staff notes, send recovery messages, and process orders.',
            ],
            [
                'name' => 'View Shipments',
                'slug' => 'view-shipments',
                'group' => 'orders',
                'description' => 'View shipping status and courier tracking details.',
            ],
            [
                'name' => 'Manage Shipments',
                'slug' => 'manage-shipments',
                'group' => 'orders',
                'description' => 'Create shipments, generate labels, and push orders to ShipMojo.',
            ],
            [
                'name' => 'View Returns',
                'slug' => 'view-returns',
                'group' => 'orders',
                'description' => 'View customer return and exchange requests.',
            ],
            [
                'name' => 'Manage Returns',
                'slug' => 'manage-returns',
                'group' => 'orders',
                'description' => 'Approve, reject, receive, and complete customer returns.',
            ],
            [
                'name' => 'View Refunds',
                'slug' => 'view-refunds',
                'group' => 'orders',
                'description' => 'View processed and pending customer refunds.',
            ],
            [
                'name' => 'Manage Refunds',
                'slug' => 'manage-refunds',
                'group' => 'orders',
                'description' => 'Approve, reject, and process refunds via payment gateways.',
            ],
            [
                'name' => 'Manage Payments',
                'slug' => 'manage-payments',
                'group' => 'orders',
                'description' => 'Access and configure payment gateways and transactions.',
            ],

            // Customers & Growth
            [
                'name' => 'View Customers',
                'slug' => 'view-customers',
                'group' => 'customers',
                'description' => 'View customer accounts, 360 profiles, active carts, and wishlists.',
            ],
            [
                'name' => 'Manage Customers',
                'slug' => 'manage-customers',
                'group' => 'customers',
                'description' => 'Edit customer profiles, manage tags, block/unblock, and export data.',
            ],
            [
                'name' => 'View Coupons',
                'slug' => 'view-coupons',
                'group' => 'customers',
                'description' => 'View promotional discount coupons and usage statistics.',
            ],
            [
                'name' => 'Manage Coupons',
                'slug' => 'manage-coupons',
                'group' => 'customers',
                'description' => 'Create, edit, and deactivate discount coupons.',
            ],
            [
                'name' => 'View Reviews',
                'slug' => 'view-reviews',
                'group' => 'customers',
                'description' => 'View submitted product reviews and ratings.',
            ],
            [
                'name' => 'Manage Reviews',
                'slug' => 'manage-reviews',
                'group' => 'customers',
                'description' => 'Approve, feature, unpublish, or delete product reviews.',
            ],
            [
                'name' => 'Manage Newsletters',
                'slug' => 'manage-newsletters',
                'group' => 'customers',
                'description' => 'Manage email newsletter subscribers and contact form inquiries.',
            ],

            // Storefront & Content CMS
            [
                'name' => 'View Media',
                'slug' => 'view-media',
                'group' => 'content',
                'description' => 'Browse uploaded images, videos, and media assets.',
            ],
            [
                'name' => 'Manage Media',
                'slug' => 'manage-media',
                'group' => 'content',
                'description' => 'Upload, organize, and delete media files in the library.',
            ],
            [
                'name' => 'View Banners',
                'slug' => 'view-banners',
                'group' => 'content',
                'description' => 'View storefront banners and sliders.',
            ],
            [
                'name' => 'Manage Banners',
                'slug' => 'manage-banners',
                'group' => 'content',
                'description' => 'Create, sort, and publish promotional homepage banners.',
            ],
            [
                'name' => 'View Blogs',
                'slug' => 'view-blogs',
                'group' => 'content',
                'description' => 'Read and preview blog posts.',
            ],
            [
                'name' => 'Manage Blogs',
                'slug' => 'manage-blogs',
                'group' => 'content',
                'description' => 'Write, publish, edit, and delete editorial blog articles.',
            ],
            [
                'name' => 'View Pages',
                'slug' => 'view-pages',
                'group' => 'content',
                'description' => 'View custom CMS landing pages.',
            ],
            [
                'name' => 'Manage Pages',
                'slug' => 'manage-pages',
                'group' => 'content',
                'description' => 'Create, edit, and delete legal, policy, and information pages.',
            ],
            [
                'name' => 'View FAQs',
                'slug' => 'view-faqs',
                'group' => 'content',
                'description' => 'View frequently asked questions.',
            ],
            [
                'name' => 'Manage FAQs',
                'slug' => 'manage-faqs',
                'group' => 'content',
                'description' => 'Create, edit, reorder, and delete FAQs.',
            ],

            // System & Administration
            [
                'name' => 'View Dashboard',
                'slug' => 'view-dashboard',
                'group' => 'system',
                'description' => 'Access main store executive dashboard.',
            ],
            [
                'name' => 'View Reports & Analytics',
                'slug' => 'view-reports',
                'group' => 'system',
                'description' => 'View analytics center, conversion metrics, and financial reports.',
            ],
            [
                'name' => 'View Activities',
                'slug' => 'view-activities',
                'group' => 'system',
                'description' => 'View administrator audit logs and system activity trails.',
            ],
            [
                'name' => 'Manage Activities',
                'slug' => 'manage-activities',
                'group' => 'system',
                'description' => 'Export and purge activity logs.',
            ],
            [
                'name' => 'Manage Admins',
                'slug' => 'manage-admins',
                'group' => 'system',
                'description' => 'Create, invite, update, and deactivate sub-admin accounts.',
            ],
            [
                'name' => 'Manage Roles',
                'slug' => 'manage-roles',
                'group' => 'system',
                'description' => 'Create custom roles and configure granular permissions.',
            ],
            [
                'name' => 'Manage Settings',
                'slug' => 'manage-settings',
                'group' => 'system',
                'description' => 'Configure store preferences, Razorpay, WhatsApp Meta API, and ShipMojo.',
            ],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(
                ['slug' => $perm['slug']],
                [
                    'name' => $perm['name'],
                    'group' => $perm['group'],
                    'description' => $perm['description'],
                ]
            );
        }

        // 3. Sync Super Admin with ALL permissions
        $superAdmin = Role::where('slug', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->sync(Permission::all()->pluck('id'));
        }

        // 4. Update Manager Role
        $manager = Role::where('slug', 'manager')->first();
        if ($manager) {
            $managerPermSlugs = [
                'view-dashboard', 'view-reports',
                'view-products', 'manage-products', 'view-categories', 'manage-categories',
                'view-inventory', 'manage-inventory', 'review-dadi-product-profiles',
                'view-orders', 'manage-orders', 'view-shipments', 'manage-shipments',
                'view-returns', 'manage-returns', 'view-refunds', 'manage-refunds',
                'view-whatsapp-inbox', 'manage-whatsapp-inbox',
                'view-whatsapp-campaigns', 'manage-whatsapp-campaigns',
                'manage-whatsapp-bot', 'manage-whatsapp-templates', 'manage-whatsapp-profile',
                'view-customers', 'manage-customers', 'view-coupons', 'manage-coupons',
                'view-reviews', 'manage-reviews', 'manage-newsletters',
                'view-media', 'manage-media', 'view-banners', 'manage-banners',
                'view-blogs', 'manage-blogs', 'view-pages', 'manage-pages',
                'view-faqs', 'manage-faqs', 'view-activities', 'manage-activities',
            ];
            $manager->permissions()->sync(Permission::whereIn('slug', $managerPermSlugs)->pluck('id'));
        }

        // 5. Update Support Role
        $support = Role::where('slug', 'support')->first();
        if ($support) {
            $supportPermSlugs = [
                'view-dashboard',
                'view-orders', 'manage-orders',
                'view-shipments',
                'view-returns', 'manage-returns',
                'view-refunds', 'manage-refunds',
                'view-whatsapp-inbox', 'manage-whatsapp-inbox',
                'view-customers',
                'view-reviews',
            ];
            $support->permissions()->sync(Permission::whereIn('slug', $supportPermSlugs)->pluck('id'));
        }

        // 6. Create Dedicated "WhatsApp Live Chat Agent" Role
        $waAgent = Role::updateOrCreate(
            ['slug' => 'whatsapp-agent'],
            [
                'name' => 'WhatsApp Live Chat Agent',
                'description' => 'Dedicated customer support agent restricted solely to WhatsApp Live Chat Inbox.',
                'is_system' => false,
            ]
        );
        $waAgent->permissions()->sync(
            Permission::whereIn('slug', [
                'view-dashboard',
                'view-whatsapp-inbox',
                'manage-whatsapp-inbox',
            ])->pluck('id')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::where('slug', 'whatsapp-agent')->delete();
    }
};
