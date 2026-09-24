<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Media;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class MediaExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('s3');
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.media.export'))->assertRedirect(route('admin.login'));
    }

    public function test_export_downloads_xlsx_with_names_and_links(): void
    {
        $first = Media::create([
            'name' => 'Hero Banner',
            'file_name' => 'hero-banner.jpg',
            'path' => 'media/hero-banner.jpg',
            'disk' => 's3',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
        ]);

        Media::create([
            'name' => 'Product Shot & Gown',
            'file_name' => 'product-gown.png',
            'path' => 'media/product-gown.png',
            'disk' => 's3',
            'mime_type' => 'image/png',
            'size' => 4096,
        ]);

        $admin = Admin::factory()->superAdmin()->create();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.media.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString(
            'attachment; filename=media_library_',
            $response->baseResponse->headers->get('Content-Disposition')
        );

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_test_');
        file_put_contents($tmp, $response->streamedContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmp) === true, 'Exported file is not a valid xlsx archive.');
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($tmp);

        $this->assertStringContainsString('Image Name', $sheet);
        $this->assertStringContainsString('Hero Banner', $sheet);
        $this->assertStringContainsString($first->url, $sheet);
        $this->assertStringContainsString('Product Shot &amp; Gown', $sheet);
    }
}
