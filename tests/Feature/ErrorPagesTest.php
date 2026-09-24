<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public static function errorPages(): array
    {
        return [
            '401' => ['401', 'Unauthorized'],
            '402' => ['402', 'Payment Required'],
            '403' => ['403', 'Forbidden'],
            '404' => ['404', 'Page Not Found'],
            '419' => ['419', 'Page Expired'],
            '429' => ['429', 'Too Many Requests'],
            '500' => ['500', 'Something Went Wrong'],
            '503' => ['503', 'Service Unavailable'],
        ];
    }

    /**
     * @dataProvider errorPages
     */
    public function test_error_page_renders(string $code, string $title): void
    {
        $html = view("errors.$code")->render();

        $this->assertStringContainsString($code, $html);
        $this->assertStringContainsString($title, $html);
        $this->assertStringContainsString('Go back home', $html);
        $this->assertStringContainsString(route('home'), $html);
        $this->assertStringContainsString('noindex', $html);
    }

    public function test_home_button_present_on_error_pages(): void
    {
        foreach (array_keys(self::errorPages()) as $code) {
            $html = view("errors.$code")->render();
            $this->assertStringContainsString('class="btn btn-vr"', $html, "errors.$code is missing the home button");
        }
    }

    public function test_bogus_url_returns_custom_404_with_home_link(): void
    {
        $response = $this->get('/this-page-does-not-exist');

        $response->assertStatus(404);
        $response->assertSee('Page Not Found', false);
        $response->assertSee('Go back home', false);
        $response->assertSee(route('home'), false);
    }
}
