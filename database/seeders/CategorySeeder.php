<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->categories() as $rootData) {
            $root = $this->createCategory($rootData);

            foreach ($rootData['children'] ?? [] as $childData) {
                $child = $this->createCategory($childData, $root->id);

                foreach ($childData['children'] ?? [] as $grandChildData) {
                    $this->createCategory($grandChildData, $child->id);
                }
            }
        }
    }

    protected function createCategory(array $data, ?int $parentId = null): Category
    {
        return Category::updateOrCreate(
            ['slug' => $data['slug']],
            [
                'parent_id' => $parentId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'image' => $data['image'] ?? null,
                'meta_title' => $data['meta_title'] ?? $data['name'].' | VANRITI',
                'meta_description' => $data['meta_description'] ?? $data['description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? Str::slug($data['name']),
                'status' => $data['status'] ?? 'active',
                'sort_order' => $data['sort_order'] ?? 0,
            ]
        );
    }

    protected function categories(): array
    {
        return [
            [
                'name' => 'Beauty & Personal Care',
                'slug' => 'beauty-personal-care',
                'description' => 'Herb-infused skincare, haircare and body care crafted with botanical actives.',
                'sort_order' => 1,
                'children' => [
                    [
                        'name' => 'Skin Care',
                        'slug' => 'skin-care',
                        'description' => 'Face cleansers, serums, moisturizers, masks and sun care powered by plant actives.',
                        'sort_order' => 1,
                        'children' => [
                            ['name' => 'Face Cleansers', 'slug' => 'face-cleansers'],
                            ['name' => 'Moisturizers', 'slug' => 'moisturizers'],
                            ['name' => 'Serums & Treatments', 'slug' => 'serums-treatments'],
                            ['name' => 'Sun Care', 'slug' => 'sun-care'],
                            ['name' => 'Face Masks & Packs', 'slug' => 'face-masks-packs'],
                        ],
                    ],
                    [
                        'name' => 'Hair Care',
                        'slug' => 'hair-care',
                        'description' => 'Shampoos, conditioners and oils enriched with herbs and cold-pressed oils.',
                        'sort_order' => 2,
                        'children' => [
                            ['name' => 'Shampoos', 'slug' => 'shampoos'],
                            ['name' => 'Conditioners', 'slug' => 'conditioners'],
                            ['name' => 'Hair Oils', 'slug' => 'hair-oils'],
                            ['name' => 'Hair Masks & Serums', 'slug' => 'hair-masks-serums'],
                        ],
                    ],
                    [
                        'name' => 'Bath & Body',
                        'slug' => 'bath-body',
                        'description' => 'Shower gels, body lotions, scrubs and lip care for everyday indulgence.',
                        'sort_order' => 3,
                        'children' => [
                            ['name' => 'Body Washes & Shower Gels', 'slug' => 'body-washes-shower-gels'],
                            ['name' => 'Body Lotions & Butters', 'slug' => 'body-lotions-butters'],
                            ['name' => 'Body Scrubs', 'slug' => 'body-scrubs'],
                            ['name' => 'Lip Care', 'slug' => 'lip-care'],
                        ],
                    ],
                    [
                        'name' => 'Fragrance',
                        'slug' => 'fragrance',
                        'description' => 'Floral, woody and fresh fragrance mists for every mood.',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'name' => 'Health & Wellness',
                'slug' => 'health-wellness',
                'description' => 'Ayurvedic classics, supplements and wellness essentials for daily vitality.',
                'sort_order' => 2,
                'children' => [
                    [
                        'name' => 'Ayurvedic & Herbal',
                        'slug' => 'ayurvedic-herbal',
                        'description' => 'Time-honoured ayurvedic formulations and herbal remedies.',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Vitamins & Supplements',
                        'slug' => 'vitamins-supplements',
                        'description' => 'Daily nutrition support in clean, thoughtful formats.',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Immunity Boosters',
                        'slug' => 'immunity-boosters',
                        'description' => 'Herbal support blends for everyday immunity.',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Sleep & Relaxation',
                        'slug' => 'sleep-relaxation',
                        'description' => 'Calming teas and blends for restful evenings.',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'name' => 'Baby & Mother Care',
                'slug' => 'baby-mother-care',
                'description' => 'Gentle, dermatologically-kind care for little ones and new mothers.',
                'sort_order' => 3,
                'children' => [
                    ['name' => 'Baby Bath & Body', 'slug' => 'baby-bath-body'],
                    ['name' => 'Baby Skincare', 'slug' => 'baby-skincare'],
                    ['name' => 'Mom Care', 'slug' => 'mom-care'],
                ],
            ],
            [
                'name' => 'Natural & Living',
                'slug' => 'natural-living',
                'description' => 'Clean, eco-conscious essentials for mindful everyday living.',
                'sort_order' => 4,
                'children' => [
                    ['name' => 'Home Fragrance', 'slug' => 'home-fragrance'],
                    ['name' => 'Eco Essentials', 'slug' => 'eco-essentials'],
                ],
            ],
        ];
    }
}