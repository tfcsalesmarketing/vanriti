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

    public function test_upload_applies_per_image_names(): void
    {
        $response = $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.media.store'), [
                'images' => [
                    UploadedFile::fake()->image('hero.jpg', 100, 100),
                    UploadedFile::fake()->image('about.png', 80, 80),
                ],
                'names' => ['Hero Banner', 'About Us'],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('media', ['name' => 'Hero Banner']);
        $this->assertDatabaseHas('media', ['name' => 'About Us']);
    }

    public function test_blank_name_falls_back_to_file_name(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.media.store'), [
                'images' => [
                    UploadedFile::fake()->image('flower-autumn.jpg', 100, 100),
                ],
                'names' => ['   '],
            ]);

        $this->assertDatabaseHas('media', ['name' => 'flower-autumn']);
    }

    public function test_name_longer_than_150_chars_is_rejected(): void
    {
        $response = $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.media.store'), [
                'images' => [
                    UploadedFile::fake()->image('hero.jpg', 100, 100),
                ],
                'names' => [str_repeat('a', 151)],
            ]);

        $response->assertSessionHasErrors('names.0');
        $this->assertSame(0, Media::count());
    }

    public function test_mismatched_names_length_does_not_break_upload(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.media.store'), [
                'images' => [
                    UploadedFile::fake()->image('first.jpg', 100, 100),
                    UploadedFile::fake()->image('second.jpg', 100, 100),
                ],
                'names' => ['First'],
            ]);

        $this->assertDatabaseHas('media', ['name' => 'First']);
        $this->assertDatabaseHas('media', ['name' => 'second']);
    }
}
