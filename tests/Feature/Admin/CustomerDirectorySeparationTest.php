<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\Tfc\DirectSellingMember;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\TfcDefaultContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDirectorySeparationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected User $retailCustomer1;
    protected User $retailCustomer2;
    protected User $directSellerUser;
    protected DirectSellingMember $directSellerMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(TfcDefaultContentSeeder::class);

        $this->admin = Admin::factory()->create([
            'status' => 'active',
        ]);
        $this->admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        // Create 2 VANRITI retail customers
        $this->retailCustomer1 = User::factory()->create([
            'name' => 'Meera Retail Storefront',
            'email' => 'meera.retail@example.com',
            'phone' => '9811122233',
            'status' => 'active',
        ]);

        $this->retailCustomer2 = User::factory()->create([
            'name' => 'Rohan Shopper',
            'email' => 'rohan.shopper@example.com',
            'phone' => '9822233344',
            'status' => 'active',
        ]);

        // Create 1 TFC Direct Seller user
        $this->directSellerUser = User::factory()->create([
            'name' => 'Kavita DirectSeller',
            'email' => 'kavita.seller@example.com',
            'phone' => '9833344455',
            'status' => 'active',
        ]);

        $this->directSellerMember = DirectSellingMember::create([
            'user_id' => $this->directSellerUser->id,
            'member_id' => 'TFC998877',
            'membership_status' => 'active',
            'activation_status' => 'active',
            'kyc_status' => 'approved',
            'registration_date' => now()->toDateString(),
        ]);
    }

    public function test_user_scopes_correctly_separate_retail_and_direct_sellers(): void
    {
        $retailList = User::vanritiCustomers()->pluck('email')->toArray();
        $this->assertContains('meera.retail@example.com', $retailList);
        $this->assertContains('rohan.shopper@example.com', $retailList);
        $this->assertNotContains('kavita.seller@example.com', $retailList);

        $directSellerList = User::directSellers()->pluck('email')->toArray();
        $this->assertContains('kavita.seller@example.com', $directSellerList);
        $this->assertNotContains('meera.retail@example.com', $directSellerList);
        $this->assertNotContains('rohan.shopper@example.com', $directSellerList);

        $this->assertFalse($this->retailCustomer1->isDirectSeller());
        $this->assertTrue($this->directSellerUser->isDirectSeller());
    }

    public function test_vanriti_admin_customers_page_displays_only_retail_customers(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.customers.index'));

        $response->assertStatus(200);

        // Assert retail customers are present
        $response->assertSee('Meera Retail Storefront');
        $response->assertSee('meera.retail@example.com');
        $response->assertSee('Rohan Shopper');
        $response->assertSee('rohan.shopper@example.com');

        // Assert direct seller is NOT listed in the retail customer directory table
        $response->assertDontSee('Kavita DirectSeller');
        $response->assertDontSee('kavita.seller@example.com');
        $response->assertDontSee('TFC998877');
    }

    public function test_tfc_admin_customers_page_displays_only_direct_sellers(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.tfc.customers.index'));

        $response->assertStatus(200);

        // Assert direct seller is present with TFC Member ID
        $response->assertSee('Kavita DirectSeller');
        $response->assertSee('kavita.seller@example.com');
        $response->assertSee('TFC998877');

        // Assert retail storefront customers are NOT present
        $response->assertDontSee('Meera Retail Storefront');
        $response->assertDontSee('meera.retail@example.com');
        $response->assertDontSee('Rohan Shopper');
        $response->assertDontSee('rohan.shopper@example.com');
    }

    public function test_vanriti_dashboard_counts_only_retail_customers(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // Total users is 3 (2 retail + 1 direct seller), but dashboard customer count must be 2
        $dashboardData = $response->viewData('stats');
        $this->assertEquals(2, $dashboardData['customers']);
    }

    public function test_customer_detail_shows_tfc_banner_only_for_direct_sellers(): void
    {
        // Retail customer profile should NOT show the TFC banner
        $retailResponse = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.customers.show', $this->retailCustomer1));

        $retailResponse->assertStatus(200);
        $retailResponse->assertDontSee('TFC Independent Direct Seller Account');

        // Direct seller profile viewed in admin should show the TFC banner and member link
        $sellerResponse = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.customers.show', $this->directSellerUser));

        $sellerResponse->assertStatus(200);
        $sellerResponse->assertSee('TFC Independent Direct Seller Account');
        $sellerResponse->assertSee('TFC998877');
    }

    public function test_tfc_customer_detail_page_loads_successfully(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.tfc.customers.show', $this->directSellerUser));

        $response->assertStatus(200);
        $response->assertSee('Kavita DirectSeller');
        $response->assertSee('TFC998877');
    }
}
