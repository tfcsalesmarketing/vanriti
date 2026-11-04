<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('s3');
        Storage::fake('public');

        $this->admin = Admin::factory()->create();
        $this->admin->makeSuperAdmin();
        $this->actingAs($this->admin, 'admin');
    }

    public function test_can_update_root_category_with_empty_parent_id(): void
    {
        $root = Category::create([
            'name' => 'Hair Care',
            'slug' => 'hair-care',
            'parent_id' => null,
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $response = $this->put(route('admin.categories.update', $root->slug), [
            'name' => 'Hair Care Updated',
            'parent_id' => '',
            'status' => 'active',
            'sort_order' => 2,
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $root->id,
            'name' => 'Hair Care Updated',
            'parent_id' => null,
            'sort_order' => 2,
        ]);
    }

    public function test_can_update_sub_category(): void
    {
        $root = Category::create([
            'name' => 'Hair Care',
            'slug' => 'hair-care',
            'parent_id' => null,
            'status' => 'active',
        ]);

        $child = Category::create([
            'name' => 'Hair Oils',
            'slug' => 'hair-oils',
            'parent_id' => $root->id,
            'status' => 'active',
        ]);

        $response = $this->put(route('admin.categories.update', $child->slug), [
            'name' => 'Organic Hair Oils',
            'parent_id' => (string) $root->id,
            'status' => 'active',
            'sort_order' => 5,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'name' => 'Organic Hair Oils',
            'parent_id' => $root->id,
        ]);
    }

    public function test_can_update_main_category(): void
    {
        $root = Category::create([
            'name' => 'Skin Care',
            'slug' => 'skin-care',
            'parent_id' => null,
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $response = $this->put(route('admin.main-categories.update', $root->slug), [
            'name' => 'Skin Care Premium',
            'status' => 'active',
            'sort_order' => 3,
            'description' => 'Main category desc',
        ]);

        $response->assertRedirect(route('admin.main-categories.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $root->id,
            'name' => 'Skin Care Premium',
            'parent_id' => null,
            'sort_order' => 3,
        ]);
    }

    public function test_update_category_with_image_upload(): void
    {
        $category = Category::create([
            'name' => 'Wellness',
            'slug' => 'wellness',
            'parent_id' => null,
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->image('wellness.jpg', 400, 400);

        $response = $this->put(route('admin.categories.update', $category->slug), [
            'name' => 'Wellness',
            'parent_id' => '',
            'status' => 'active',
            'image' => $file,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $category->refresh();
        $this->assertNotNull($category->image);
    }
}
