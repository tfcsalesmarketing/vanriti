<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use Illuminate\Database\Seeder;

class FaqInternalLinkingSeeder extends Seeder
{
    private const CATEGORY_MAP = [
        1 => 'Hair Care',
        2 => 'Skin Care',
        3 => 'Health & Wellness',
        4 => 'Hair Care Blends',
        5 => 'Hair Cleansing Powders',
        6 => 'Hair Care Powders',
        7 => 'Face Packs & Masks',
        8 => 'Skin Care Blends',
        9 => 'Skin Care Powders',
        10 => 'Wellness Blends',
        11 => 'Nutritional Powders',
    ];

    private const PRODUCT_MAP = [
        'VNRT001' => 'Amla + Bhringraj Hair Care Kit',
        'VNRT002' => 'Amla + Hibiscus Hair Care Kit',
        'VNRT003' => 'Amla + Reetha Hair Care Kit',
        'VNRT004' => 'Amla + Shikakai Hair Care Kit',
        'VNRT005' => 'Bhringraj + Hibiscus Hair Care Kit',
        'VNRT006' => 'Bhringraj + Reetha Hair Care Kit',
        'VNRT007' => 'Bhringraj + Shikakai Hair Care Kit',
        'VNRT008' => 'Hibiscus + Reetha Hair Care Kit',
        'VNRT009' => 'Hibiscus + Shikakai Hair Care Kit',
        'VNRT010' => 'Reetha + Shikakai Hair Cleanse Kit',
        'VNRT091' => 'Amla Powder',
        'VNRT092' => 'Bhringraj Powder',
        'VNRT093' => 'Reetha Powder',
        'VNRT094' => 'Shikakai Powder',
        'VNRT095' => 'Hibiscus Powder',
        'VNRT096' => 'Moringa Powder',
        'VNRT097' => 'Rose Petal Powder',
        'VNRT098' => 'Neem Powder',
        'VNRT099' => 'Multani Mitti Powder',
        'VNRT100' => 'Beetroot Powder',
        'VNRT090' => 'Moringa + Amla + Beetroot + Hibiscus Wellness Kit',
    ];

    private const FAQ_LINKS = [
        1 => ['categories' => [1, 2, 3], 'products' => []],
        2 => ['categories' => [], 'products' => []],
        3 => ['categories' => [1, 2, 3], 'products' => []],
        4 => ['categories' => [1, 2, 3], 'products' => []],
        5 => ['categories' => [], 'products' => []],
        6 => ['categories' => [], 'products' => []],
        7 => ['categories' => [1, 2, 3], 'products' => []],
        8 => ['categories' => [1, 2, 3], 'products' => []],
        9 => ['categories' => [1, 4, 6], 'products' => ['VNRT001', 'VNRT002', 'VNRT010']],
        10 => ['categories' => [6, 4], 'products' => ['VNRT091', 'VNRT092', 'VNRT095', 'VNRT094']],
        11 => ['categories' => [6, 4], 'products' => ['VNRT091', 'VNRT093', 'VNRT094', 'VNRT095']],
        12 => ['categories' => [6, 4], 'products' => ['VNRT091', 'VNRT093', 'VNRT094', 'VNRT095']],
        13 => ['categories' => [6, 4], 'products' => ['VNRT093', 'VNRT094', 'VNRT091', 'VNRT095']],
        14 => ['categories' => [1, 6, 5], 'products' => ['VNRT091', 'VNRT095', 'VNRT093', 'VNRT094']],
        15 => ['categories' => [2, 9, 7], 'products' => ['VNRT097', 'VNRT098', 'VNRT099', 'VNRT100']],
        16 => ['categories' => [9, 7], 'products' => ['VNRT097', 'VNRT098', 'VNRT099', 'VNRT100']],
        17 => ['categories' => [2, 9, 7], 'products' => ['VNRT097', 'VNRT098', 'VNRT099']],
        18 => ['categories' => [9, 7], 'products' => ['VNRT097', 'VNRT098', 'VNRT099', 'VNRT100']],
        19 => ['categories' => [2, 1], 'products' => []],
        20 => ['categories' => [3, 10, 11], 'products' => ['VNRT090', 'VNRT096', 'VNRT100']],
        21 => ['categories' => [6, 11], 'products' => ['VNRT091']],
        22 => ['categories' => [6, 11], 'products' => ['VNRT091', 'VNRT001', 'VNRT004']],
        23 => ['categories' => [6, 9], 'products' => ['VNRT095']],
        24 => ['categories' => [6], 'products' => ['VNRT095', 'VNRT002', 'VNRT005']],
        25 => ['categories' => [5, 6], 'products' => ['VNRT094']],
        26 => ['categories' => [5, 6], 'products' => ['VNRT094', 'VNRT010', 'VNRT004']],
        27 => ['categories' => [5, 6], 'products' => ['VNRT093']],
        28 => ['categories' => [5, 6], 'products' => ['VNRT093', 'VNRT010', 'VNRT003']],
        29 => ['categories' => [9, 7], 'products' => ['VNRT098']],
        30 => ['categories' => [11, 9, 7], 'products' => ['VNRT100']],
        31 => ['categories' => [6, 9, 11], 'products' => []],
        32 => ['categories' => [6, 9, 11], 'products' => []],
        33 => ['categories' => [6, 9, 11], 'products' => []],
        34 => ['categories' => [6, 9, 11], 'products' => []],
        35 => ['categories' => [1, 2, 3], 'products' => []],
        36 => ['categories' => [2, 1], 'products' => []],
        37 => ['categories' => [1, 2, 3], 'products' => []],
        38 => ['categories' => [1, 2, 3], 'products' => []],
        39 => ['categories' => [1, 2, 3], 'products' => []],
        40 => ['categories' => [1, 2, 3], 'products' => []],
        41 => ['categories' => [1, 2, 3], 'products' => []],
        42 => ['categories' => [1, 2, 3], 'products' => []],
        43 => ['categories' => [], 'products' => []],
        44 => ['categories' => [], 'products' => []],
        45 => ['categories' => [], 'products' => []],
        46 => ['categories' => [], 'products' => []],
        47 => ['categories' => [], 'products' => []],
        48 => ['categories' => [], 'products' => []],
        49 => ['categories' => [], 'products' => []],
    ];

    public function run(): void
    {
        $this->command->info('Validating FAQ internal-linking data...');

        $errors = [];

        $faqs = Faq::orderBy('id')->get();
        $faqIds = $faqs->pluck('id')->toArray();

        foreach (array_keys(self::FAQ_LINKS) as $faqId) {
            if (! in_array($faqId, $faqIds, true)) {
                $errors[] = "FAQ ID {$faqId} not found in database.";
            }
        }

        $categoryIds = Category::whereIn('id', array_merge(...array_map(fn ($l) => $l['categories'], self::FAQ_LINKS)))->pluck('id')->toArray();
        $referencedCategoryIds = array_unique(array_merge(...array_map(fn ($l) => $l['categories'], self::FAQ_LINKS)));

        foreach ($referencedCategoryIds as $catId) {
            if (! in_array($catId, $categoryIds, true)) {
                $errors[] = "Category ID {$catId} ({$this->categoryName($catId)}) not found in database.";
            }
        }

        $referencedSkus = array_unique(array_merge(...array_map(fn ($l) => $l['products'], self::FAQ_LINKS)));
        $productIds = Product::whereIn('sku', $referencedSkus)->pluck('id', 'sku')->toArray();

        foreach ($referencedSkus as $sku) {
            if (! isset($productIds[$sku])) {
                $errors[] = "Product SKU {$sku} ({$this->productName($sku)}) not found in database.";
            }
        }

        if ($errors !== []) {
            $this->command->error('Validation failed. No data was written:');
            foreach ($errors as $error) {
                $this->command->error("  - {$error}");
            }

            return;
        }

        $this->command->info('All 49 FAQs, categories and products validated.');
        $this->command->info('Dry-run report:');
        $this->command->info('');

        foreach (self::FAQ_LINKS as $faqId => $links) {
            $faq = $faqs->firstWhere('id', $faqId);
            $catNames = array_map(fn ($id) => $this->categoryName($id), $links['categories']);
            $productNames = array_map(fn ($sku) => $this->productName($sku), $links['products']);

            $this->command->info("FAQ {$faqId}: {$faq->question}");
            if ($catNames !== []) {
                $this->command->info('  Categories: '.implode(', ', $catNames));
            }
            if ($productNames !== []) {
                $this->command->info('  Products: '.implode(', ', $productNames));
            }
            if ($catNames === [] && $productNames === []) {
                $this->command->info('  (no links)');
            }
        }

        $this->command->info('');
        $this->command->info('Inserting relationships...');

        $categoryCount = 0;
        $productCount = 0;

        foreach (self::FAQ_LINKS as $faqId => $links) {
            $faq = $faqs->firstWhere('id', $faqId);

            $catIds = array_map(fn ($id) => (int) $id, $links['categories']);
            $faq->categories()->sync($catIds);
            $categoryCount += count($catIds);

            $prodIds = array_map(fn ($sku) => $productIds[$sku], $links['products']);
            $faq->products()->sync($prodIds);
            $productCount += count($prodIds);
        }

        $this->command->info('');
        $this->command->info("Done. {$categoryCount} category relationships, {$productCount} product relationships across 49 FAQs.");
    }

    private function categoryName(int $id): string
    {
        return self::CATEGORY_MAP[$id] ?? "Category {$id}";
    }

    private function productName(string $sku): string
    {
        return self::PRODUCT_MAP[$sku] ?? "Product {$sku}";
    }
}
