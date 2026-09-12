<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'title' => 'Clean Beauty, Rooted in Ayurveda',
                'subtitle' => 'Explore our bestsellers made with herbs, not harshness.',
                'image' => 'https://placehold.co/1600x600/1f3d2b/f7f4ee/png?text=VANRITI+HERBAL+GLOW',
                'mobile_image' => 'https://placehold.co/600x600/1f3d2b/f7f4ee/png?text=VANRITI',
                'link' => '/shop',
                'type' => 'hero',
                'position' => 'home_hero',
                'sort_order' => 1,
                'status' => 'active',
                'starts_at' => null,
                'expires_at' => null,
            ],
            [
                'title' => 'New Arrivals',
                'subtitle' => 'Fresh from our labs to your shelf.',
                'image' => 'https://placehold.co/1600x600/4a7c3f/f7f4ee/png?text=NEW+ARRIVALS',
                'mobile_image' => 'https://placehold.co/600x600/4a7c3f/f7f4ee/png?text=NEW',
                'link' => '/shop?sort=newest',
                'type' => 'hero',
                'position' => 'home_hero',
                'sort_order' => 2,
                'status' => 'active',
                'starts_at' => null,
                'expires_at' => null,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::updateOrCreate(
                ['title' => $banner['title']],
                $banner
            );
        }
    }
}