<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingLabelCropperController extends Controller
{
    /**
     * Display the thermal shipping label cropper tool.
     */
    public function index(Request $request): View
    {
        $presets = [
            'flipkart_tight' => [
                'name' => 'Flipkart (Tight Crop - Zero White Space)',
                'badge' => 'Flipkart',
                'badge_class' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                'crop_top' => 3,
                'crop_bottom' => 55,
                'crop_left' => 31,
                'crop_right' => 31,
                'rotation' => 0,
                'paper' => '4x6',
                'margin' => 0,
                'description' => 'Tightly crops the centered Flipkart shipping label, stripping side margins & bottom invoice so the label fills the 4×6″ roll edge-to-edge.',
            ],
            'meesho_90' => [
                'name' => 'Meesho (4×6″ Thermal - Tight 90° Rotated)',
                'badge' => 'Meesho',
                'badge_class' => 'bg-danger-subtle text-danger border-danger-subtle',
                'crop_top' => 2,
                'crop_bottom' => 53,
                'crop_left' => 3,
                'crop_right' => 3,
                'rotation' => 90,
                'paper' => '4x6',
                'margin' => 0,
                'description' => 'Extracts top shipping label, removes bottom tax invoice and margins, and rotates 90° to fill vertical 4×6″ thermal rolls.',
            ],
            'amazon_quad' => [
                'name' => 'Amazon (A4 Top-Left - Tight Crop)',
                'badge' => 'Amazon',
                'badge_class' => 'bg-info-subtle text-info-emphasis border-info-subtle',
                'crop_top' => 3,
                'crop_bottom' => 52,
                'crop_left' => 2,
                'crop_right' => 48,
                'rotation' => 0,
                'paper' => '4x6',
                'margin' => 0,
                'description' => 'Extracts the shipping barcode and courier routing block from Amazon A4 invoice sheets without empty margins.',
            ],
            'meesho_0' => [
                'name' => 'Meesho (4×6″ Thermal - Unrotated)',
                'badge' => 'Meesho',
                'badge_class' => 'bg-danger-subtle text-danger border-danger-subtle',
                'crop_top' => 2,
                'crop_bottom' => 53,
                'crop_left' => 3,
                'crop_right' => 3,
                'rotation' => 0,
                'paper' => '4x6',
                'margin' => 0,
                'description' => 'Extracts top shipping label without rotation (ideal for landscape feed or custom thermal width).',
            ],
            'passthrough_4x6' => [
                'name' => '4×6″ Direct Optimizer (Auto-Margins)',
                'badge' => 'Standard',
                'badge_class' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                'crop_top' => 0,
                'crop_bottom' => 0,
                'crop_left' => 0,
                'crop_right' => 0,
                'rotation' => 0,
                'paper' => '4x6',
                'margin' => 2,
                'description' => 'Applies 2mm safe thermal head margins to prevent courier barcode lines from clipping at roll edges.',
            ],
            'custom' => [
                'name' => 'Custom / Freeform Drag Crop',
                'badge' => 'Custom',
                'badge_class' => 'bg-primary-subtle text-primary border-primary-subtle',
                'crop_top' => 3,
                'crop_bottom' => 55,
                'crop_left' => 31,
                'crop_right' => 31,
                'rotation' => 0,
                'paper' => '4x6',
                'margin' => 0,
                'description' => 'Drag the green crop handles directly on the preview or use sliders to eliminate any white space.',
            ],
        ];

        $paperSizes = [
            '4x6' => [
                'name' => '4″ × 6″ Thermal Roll (100 × 150 mm)',
                'width_mm' => 100,
                'height_mm' => 150,
                'width_pt' => 283.46,
                'height_pt' => 425.20,
                'standard' => true,
            ],
            '3x5' => [
                'name' => '3″ × 5″ Thermal Roll (75 × 125 mm)',
                'width_mm' => 75,
                'height_mm' => 125,
                'width_pt' => 212.60,
                'height_pt' => 354.33,
                'standard' => false,
            ],
            '4x4' => [
                'name' => '4″ × 4″ Square Thermal (100 × 100 mm)',
                'width_mm' => 100,
                'height_mm' => 100,
                'width_pt' => 283.46,
                'height_pt' => 283.46,
                'standard' => false,
            ],
            'a4_2up' => [
                'name' => 'A4 Sheet (2 Labels per Page)',
                'width_mm' => 210,
                'height_mm' => 148.5,
                'width_pt' => 595.28,
                'height_pt' => 420.94,
                'standard' => false,
            ],
            'a4_full' => [
                'name' => 'A4 Full Sheet (210 × 297 mm)',
                'width_mm' => 210,
                'height_mm' => 297,
                'width_pt' => 595.28,
                'height_pt' => 841.89,
                'standard' => false,
            ],
        ];

        return view('admin.orders.label-cropper', compact('presets', 'paperSizes'));
    }
}

