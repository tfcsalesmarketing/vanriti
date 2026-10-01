<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqInternalLinkingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_faq_page_loads_successfully(): void
    {
        Faq::create([
            'question' => 'Test question?',
            'answer' => 'Test answer.',
            'status' => 'active',
        ]);

        $this->get(route('faq.index'))->assertOk();
    }

    public function test_faq_answers_remain_html_escaped(): void
    {
        Faq::create([
            'question' => 'Test question?',
            'answer' => '<b>bold</b>',
            'status' => 'active',
        ]);

        $html = $this->get(route('faq.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('<b>bold</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
    }

    public function test_related_category_links_render_correctly(): void
    {
        $category = Category::factory()->active()->create(['name' => 'Hair Care', 'slug' => 'hair-care']);

        $faq = Faq::create([
            'question' => 'What is shikakai?',
            'answer' => 'Shikakai is a natural cleanser.',
            'status' => 'active',
        ]);

        $faq->categories()->attach($category);

        $html = $this->get(route('faq.index'))->assertOk()->getContent();

        $this->assertStringContainsString(route('shop.category', $category), $html);
        $this->assertStringContainsString('Hair Care', $html);
    }

    public function test_related_product_links_render_correctly(): void
    {
        $product = Product::factory()->active()->create(['name' => 'Shikakai Powder']);

        $faq = Faq::create([
            'question' => 'What is shikakai?',
            'answer' => 'Shikakai is a natural cleanser.',
            'status' => 'active',
        ]);

        $faq->products()->attach($product);

        $html = $this->get(route('faq.index'))->assertOk()->getContent();

        $this->assertStringContainsString(route('product.show', $product), $html);
        $this->assertStringContainsString('Shikakai Powder', $html);
    }

    public function test_inactive_products_are_not_displayed(): void
    {
        $product = Product::factory()->create(['name' => 'Inactive Product', 'status' => 'inactive']);

        $faq = Faq::create([
            'question' => 'Test?',
            'answer' => 'Test answer.',
            'status' => 'active',
        ]);

        $faq->products()->attach($product);

        $html = $this->get(route('faq.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Inactive Product', $html);
    }

    public function test_inactive_categories_are_not_displayed(): void
    {
        $category = Category::factory()->create(['name' => 'Inactive Category', 'status' => 'inactive']);

        $faq = Faq::create([
            'question' => 'Test?',
            'answer' => 'Test answer.',
            'status' => 'active',
        ]);

        $faq->categories()->attach($category);

        $html = $this->get(route('faq.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Inactive Category', $html);
    }

    public function test_duplicate_pivot_relationships_are_prevented(): void
    {
        $product = Product::factory()->active()->create();

        $faq = Faq::create([
            'question' => 'Test?',
            'answer' => 'Test answer.',
            'status' => 'active',
        ]);

        $faq->products()->attach($product);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $faq->products()->attach($product);
    }

    public function test_faq_without_related_content_still_renders(): void
    {
        Faq::create([
            'question' => 'What is VANRITI?',
            'answer' => 'VANRITI is a natural care brand.',
            'status' => 'active',
        ]);

        $html = $this->get(route('faq.index'))->assertOk()->getContent();

        $this->assertStringContainsString('What is VANRITI?', $html);
        $this->assertStringNotContainsString('Related Category:', $html);
        $this->assertStringNotContainsString('Related Products:', $html);
    }

    public function test_faq_page_json_ld_remains_valid(): void
    {
        Faq::create([
            'question' => 'Test question?',
            'answer' => 'Test answer.',
            'status' => 'active',
        ]);

        $html = $this->get(route('faq.index'))->assertOk()->getContent();

        $this->assertStringContainsString('"FAQPage"', $html);
        $this->assertStringContainsString('"Question"', $html);
    }

    public function test_related_products_limited_to_four(): void
    {
        $faq = Faq::create([
            'question' => 'Test?',
            'answer' => 'Test answer.',
            'status' => 'active',
        ]);

        for ($i = 1; $i <= 6; $i++) {
            $product = Product::factory()->active()->create(['name' => "Product $i"]);
            $faq->products()->attach($product);
        }

        $html = $this->get(route('faq.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Product 1', $html);
        $this->assertStringNotContainsString('Product 5', $html);
        $this->assertStringNotContainsString('Product 6', $html);
    }
}
