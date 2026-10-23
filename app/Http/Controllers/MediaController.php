<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    /**
     * Serve a guaranteed WhatsApp-compatible JPEG image for any product.
     * Converts S3/WebP images on-the-fly and caches them as crisp JPEGs.
     */
    public function whatsappProductImage(Product $product): BinaryFileResponse|Response
    {
        $cacheDir = storage_path('app/public/whatsapp_cache');
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cacheFile = "{$cacheDir}/product_{$product->id}.jpg";

        // If cached and newer than 7 days, serve directly
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 604800)) {
            return response()->file($cacheFile, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=604800',
            ]);
        }

        $imageRecord = $product->getWhatsAppImage();
        $rawPath = $imageRecord?->image_path;

        if (! empty($rawPath)) {
            $sourceUrl = image_url($rawPath);
            try {
                $response = Http::timeout(6)->get($sourceUrl);
                if ($response->successful()) {
                    $binaryData = $response->body();
                    $im = @imagecreatefromstring($binaryData);

                    if ($im) {
                        $w = imagesx($im);
                        $h = imagesy($im);

                        // Composite onto white background for JPEG
                        $canvas = imagecreatetruecolor($w, $h);
                        $white = imagecolorallocate($canvas, 255, 255, 255);
                        imagefilledrectangle($canvas, 0, 0, $w, $h, $white);
                        imagecopy($canvas, $im, 0, 0, 0, 0, $w, $h);

                        imagejpeg($canvas, $cacheFile, 92);
                        imagedestroy($canvas);
                        imagedestroy($im);

                        return response()->file($cacheFile, [
                            'Content-Type' => 'image/jpeg',
                            'Cache-Control' => 'public, max-age=604800',
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to default brand image below
            }
        }

        // Default brand fallback: logo.jpg or placeholder.png
        $fallback = public_path('images/logo.jpg');
        if (! file_exists($fallback)) {
            $fallback = public_path('images/placeholder.png');
        }

        return response()->file($fallback, [
            'Content-Type' => str_ends_with($fallback, '.png') ? 'image/png' : 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
