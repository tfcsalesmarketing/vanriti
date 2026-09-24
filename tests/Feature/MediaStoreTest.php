<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Media;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('s3');
    }

    protected function admin(): Admin
    {
        return Admin::factory()->superAdmin()->create();
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->post(route('admin.media.store'))->assertRedirect(route('admin.login'));
    }

    public function test_shared_name_is_applied_to_all_images(): void
    {
        $response = $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.media.store'), [
                'name' => 'Hero Banner',
                'images' => [
                    UploadedFile::fake()->image('hero.jpg', 100, 100),
                    UploadedFile::fake()->image('about.png', 80, 80),
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('media', ['name' => 'Hero Banner', 'file_name' => 'hero.jpg']);
        $this->assertDatabaseHas('media', ['name' => 'Hero Banner', 'file_name' => 'about.png']);
        $this->assertSame(2, Media::count());
    }

    public function test_secondary_names_are_stored_per_image(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.media.store'), [
                'name' => 'Collection',
                'images' => [
                    UploadedFile::fake()->image('hero.jpg', 100, 100),
                    UploadedFile::fake()->image('about.png', 80, 80),
                ],
                'secondary_names' => ['Hero Side', ''],
            ]);

        $this->assertDatabaseHas('media', ['name' => 'Collection', 'secondary_name' => 'Hero Side']);
        $this->assertDatabaseHas('media', ['name' => 'Collection', 'file_name' => 'about.png', 'secondary_name' => null]);
    }

    public function test_blank_shared_name_falls_back_to_file_name(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.media.store'), [
                'name' => '   ',
                'images' => [
                    UploadedFile::fake()->image('flower-autumn.jpg', 100, 100),
                ],
            ]);

        $this->assertDatabaseHas('media', ['name' => 'flower-autumn']);
    }

    public function test_secondary_name_longer_than_150_chars_is_rejected(): void
    {
        $response = $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.media.store'), [
                'images' => [
                    UploadedFile::fake()->image('hero.jpg', 100, 100),
                ],
                'secondary_names' => [str_repeat('a', 151)],
            ]);

        $response->assertSessionHasErrors('secondary_names.0');
        $this->assertSame(0, Media::count());
    }

    public function test_mismatched_secondary_names_length_does_not_break_upload(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.media.store'), [
                'images' => [
                    UploadedFile::fake()->image('first.jpg', 100, 100),
                    UploadedFile::fake()->image('second.jpg', 100, 100),
                ],
                'secondary_names' => ['First'],
            ]);

        $this->assertDatabaseHas('media', ['name' => 'first', 'secondary_name' => 'First']);
        $this->assertDatabaseHas('media', ['name' => 'second', 'secondary_name' => null]);
    }
}
