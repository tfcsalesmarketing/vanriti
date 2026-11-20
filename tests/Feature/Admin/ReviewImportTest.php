<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReviewImportTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $superAdmin;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->superAdmin = Admin::factory()->superAdmin()->create();

        $category = Category::factory()->create(['name' => 'Hair Care']);
        $this->product = Product::factory()->create([
            'name' => 'Ayurvedic Hibiscus Powder',
            'sku' => 'VAN-HIB-001',
            'status' => 'active',
            'review_count' => 0,
            'review_rating' => 0.0,
        ]);
        $this->product->categories()->attach($category->id, ['is_primary' => true]);
    }

    public function test_can_create_review_without_user_id_using_user_name(): void
    {
        $review = Review::create([
            'product_id' => $this->product->id,
            'user_id' => null,
            'user_name' => 'Aarav Sharma',
            'rating' => 5,
            'title' => 'Incredible natural shine',
            'comment' => 'Transformed my hair texture completely in just three washes.',
            'status' => 'approved',
            'is_verified_purchase' => true,
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => null,
            'user_name' => 'Aarav Sharma',
            'rating' => 5,
        ]);

        $this->assertEquals('Aarav Sharma', $review->reviewer_name);

        // Storefront rendering check
        $response = $this->get(route('product.show', $this->product->slug));
        $response->assertOk();
        $response->assertSee('Aarav Sharma');
        $response->assertSee('Incredible natural shine');
        $response->assertSee('Transformed my hair texture completely in just three washes.');
    }

    public function test_admin_can_import_reviews_via_csv(): void
    {
        $csvContent = implode("\n", [
            'sku,user_name,rating,title,comment,is_verified_purchase,status',
            'VAN-HIB-001,Pooja Patel,5,Pure botanical magic,Loved the packaging and authentic fragrance,1,approved',
            'VAN-HIB-001,Deepak Verma,4,Great conditioning,Very finely sieved powder easy to mix into mask,1,approved',
        ]);

        $file = UploadedFile::fake()->createWithContent('reviews.csv', $csvContent);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->post(route('admin.reviews.import'), [
                'import_file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'product_id' => $this->product->id,
            'user_id' => null,
            'user_name' => 'Pooja Patel',
            'rating' => 5,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('reviews', [
            'product_id' => $this->product->id,
            'user_id' => null,
            'user_name' => 'Deepak Verma',
            'rating' => 4,
            'status' => 'approved',
        ]);

        // Product review rating and count recomputed automatically
        $this->product->refresh();
        $this->assertEquals(2, $this->product->review_count);
        $this->assertEquals(4.5, (float) $this->product->review_rating);
    }
}

