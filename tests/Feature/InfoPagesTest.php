<?php

namespace Tests\Feature;

use Database\Seeders\StaticPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfoPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StaticPagesSeeder::class);
    }

    public function test_about_page_returns_200(): void
    {
        $this->get(route('info.about'))->assertOk()->assertSee('The ');
    }

    public function test_privacy_policy_page_returns_200(): void
    {
        $this->get(route('info.privacy'))->assertOk()->assertSee('Privacy Policy');
    }

    public function test_terms_page_returns_200(): void
    {
        $this->get(route('info.terms'))->assertOk()->assertSee('Terms & Conditions');
    }

    public function test_shipping_page_returns_200(): void
    {
        $this->get(route('info.shipping'))->assertOk()->assertSee('Shipping & Delivery');
    }
}
