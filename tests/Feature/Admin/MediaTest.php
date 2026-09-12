<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Media;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_hitting_admin_media_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.media.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_without_permission_is_forbidden_from_media(): void
    {
        $admin = Admin::factory()->create();

        $role = Role::create([
            'name' => 'Viewer',
            'slug' => 'viewer',
            'description' => 'Read-only',
        ]);
        $role->permissions()->attach(Permission::where('slug', 'view-dashboard')->firstOrFail());
        $admin->roles()->attach($role);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.media.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_upload_and_name_an_image(): void
    {
        Storage::fake('s3');
        $admin = Admin::factory()->superAdmin()->create();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.media.store'), [
                'name' => 'Hero Flower Banner',
                'image' => UploadedFile::fake()->image('hero.jpg', 600, 400),
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('media', [
            'name' => 'Hero Flower Banner',
            'file_name' => 'hero.jpg',
            'disk' => 's3',
            'admin_id' => $admin->id,
        ]);

        $media = Media::firstOrFail();
        Storage::disk('s3')->assertExists($media->path);
        $this->assertNotEmpty($media->url);
    }

    public function test_index_lists_media_and_search_filters_by_name(): void
    {
        Storage::fake('s3');
        $admin = Admin::factory()->superAdmin()->create();

        Media::create([
            'name' => 'Product Shot A',
            'file_name' => 'a.jpg',
            'path' => 'media/a.jpg',
            'disk' => 's3',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'admin_id' => $admin->id,
        ]);
        Media::create([
            'name' => 'Banner Hero',
            'file_name' => 'b.jpg',
            'path' => 'media/b.jpg',
            'disk' => 's3',
            'mime_type' => 'image/jpeg',
            'size' => 4096,
            'admin_id' => $admin->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('Product Shot A')
            ->assertSee('Banner Hero');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.media.index', ['q' => 'Banner']))
            ->assertOk()
            ->assertSee('Banner Hero')
            ->assertDontSee('Product Shot A');
    }

    public function test_destroy_deletes_file_and_row(): void
    {
        Storage::fake('s3');
        $admin = Admin::factory()->superAdmin()->create();

        $path = 'media/gone.jpg';
        Storage::disk('s3')->put($path, 'fake');
        $media = Media::create([
            'name' => 'Temp Image',
            'file_name' => 'gone.jpg',
            'path' => $path,
            'disk' => 's3',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'admin_id' => $admin->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.media.destroy', $media))
            ->assertSessionHas('success');

        Storage::disk('s3')->assertMissing($path);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_upload_requires_name_and_valid_image(): void
    {
        Storage::fake('s3');
        $admin = Admin::factory()->superAdmin()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.media.store'), [
                'name' => '',
                'image' => UploadedFile::fake()->image('hero.jpg'),
            ])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.media.store'), [
                'name' => 'Only Name',
            ])
            ->assertSessionHasErrors('image');
    }
}