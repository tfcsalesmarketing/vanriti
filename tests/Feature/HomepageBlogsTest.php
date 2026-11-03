<?php

namespace Tests\Feature;

use App\Models\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageBlogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_up_to_5_published_blogs_in_5_col_desktop_and_2_col_mobile_grid(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            Blog::create([
                'title' => "Botanical Care Story $i",
                'slug' => "botanical-care-story-$i",
                'content' => "<p>Handcrafted botanical guide content for story $i.</p>",
                'status' => 'published',
                'published_at' => now()->subDays($i),
            ]);
        }

        $response = $this->get('/');
        $response->assertOk();

        $html = $response->getContent();

        // 1. Grid classes: row-cols-2 for mobile and row-cols-lg-5 for desktop
        $this->assertStringContainsString('row-cols-2', $html);
        $this->assertStringContainsString('row-cols-lg-5', $html);
        $this->assertStringContainsString('vr-home-blogs-grid', $html);

        // 2. Exactly 5 latest articles rendered on homepage
        $this->assertStringContainsString('Botanical Care Story 1', $html);
        $this->assertStringContainsString('Botanical Care Story 2', $html);
        $this->assertStringContainsString('Botanical Care Story 3', $html);
        $this->assertStringContainsString('Botanical Care Story 4', $html);
        $this->assertStringContainsString('Botanical Care Story 5', $html);

        // 3. 6th and 7th articles omitted by limit(5)
        $this->assertStringNotContainsString('Botanical Care Story 6', $html);
        $this->assertStringNotContainsString('Botanical Care Story 7', $html);
    }

    public function test_homepage_omits_blog_section_when_no_published_blogs_exist(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringNotContainsString('vr-section-journal', $html);
        $this->assertStringNotContainsString('vr-home-blogs-grid', $html);
    }
}

