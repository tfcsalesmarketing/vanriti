<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ActivityLogger;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected ActivityLogger $logger,
        protected InventoryService $inventoryService,
    ) {}

    public function index(Request $request): View
    {
        $query = Product::query()
            ->with(['categories', 'images'])
            ->withCount('images');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId));
        }

        $products = $query->latest()->paginate(25)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|unique:products,slug',
            'sku' => 'nullable|unique:products,sku',
            'status' => 'required|in:draft,active,inactive',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'highlights' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'benefits' => 'nullable|string',
            'how_to_use' => 'nullable|string',
            'directions' => 'nullable|string',
            'warnings' => 'nullable|string',
            'precautions' => 'nullable|string',
            'disclaimer' => 'nullable|string',
            'net_quantity' => 'nullable|string',
            'unit' => 'nullable|string',
            'mrp' => 'required|numeric|gte:selling_price',
            'selling_price' => 'required|numeric|min:0.01',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'stock' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'hsn_code' => 'nullable|string',
            'manufacturer' => 'nullable|string',
            'manufacturer_address' => 'nullable|string',
            'country_of_origin' => 'nullable|string',
            'shelf_life' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string',
            'search_keywords' => 'nullable|string',
            'is_featured' => 'nullable',
            'is_bestseller' => 'nullable',
            'is_new_arrival' => 'nullable',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'primary_category_id' => 'nullable|exists:categories,id',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['discount_percent'] = ($validated['mrp'] > $validated['selling_price'])
            ? round((($validated['mrp'] - $validated['selling_price']) / $validated['mrp']) * 100, 1)
            : 0;
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_bestseller'] = $request->boolean('is_bestseller');
        $validated['is_new_arrival'] = $request->boolean('is_new_arrival');

        $categoryIds = $validated['category_ids'];
        $primaryCategoryId = $validated['primary_category_id'] ?? $categoryIds[0];
        unset($validated['category_ids'], $validated['primary_category_id']);

        $product = Product::create($validated);

        foreach ($categoryIds as $catId) {
            DB::table('product_category')->insert([
                'product_id' => $product->id,
                'category_id' => $catId,
                'is_primary' => ($catId == $primaryCategoryId),
            ]);
        }

        $this->logger->productCreated(auth('admin')->user(), $product);

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function show(Product $product): View
    {
        $product->load(['categories', 'images', 'variants']);
        $inventory = DB::table('inventories')
            ->where('stockable_type', Product::class)
            ->where('stockable_id', $product->id)
            ->first();

        return view('admin.products.show', compact('product', 'inventory'));
    }

    public function edit(Product $product): View
    {
        $product->load('categories');
        $categories = Category::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|unique:products,slug,' . $product->id,
            'sku' => 'nullable|unique:products,sku,' . $product->id,
            'status' => 'required|in:draft,active,inactive',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'highlights' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'benefits' => 'nullable|string',
            'how_to_use' => 'nullable|string',
            'directions' => 'nullable|string',
            'warnings' => 'nullable|string',
            'precautions' => 'nullable|string',
            'disclaimer' => 'nullable|string',
            'net_quantity' => 'nullable|string',
            'unit' => 'nullable|string',
            'mrp' => 'required|numeric|gte:selling_price',
            'selling_price' => 'required|numeric|min:0.01',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'stock' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'hsn_code' => 'nullable|string',
            'manufacturer' => 'nullable|string',
            'manufacturer_address' => 'nullable|string',
            'country_of_origin' => 'nullable|string',
            'shelf_life' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string',
            'search_keywords' => 'nullable|string',
            'is_featured' => 'nullable',
            'is_bestseller' => 'nullable',
            'is_new_arrival' => 'nullable',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'primary_category_id' => 'nullable|exists:categories,id',
        ]);

        $old = $product->only([
            'name', 'slug', 'sku', 'status',
            'mrp', 'selling_price', 'discount_percent', 'gst_rate', 'stock',
            'is_featured', 'is_bestseller', 'is_new_arrival',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['discount_percent'] = ($validated['mrp'] > $validated['selling_price'])
            ? round((($validated['mrp'] - $validated['selling_price']) / $validated['mrp']) * 100, 1)
            : 0;
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_bestseller'] = $request->boolean('is_bestseller');
        $validated['is_new_arrival'] = $request->boolean('is_new_arrival');

        $categoryIds = $validated['category_ids'];
        $primaryCategoryId = $validated['primary_category_id'] ?? $categoryIds[0];
        unset($validated['category_ids'], $validated['primary_category_id']);

        $product->update($validated);

        DB::table('product_category')->where('product_id', $product->id)->delete();
        foreach ($categoryIds as $catId) {
            DB::table('product_category')->insert([
                'product_id' => $product->id,
                'category_id' => $catId,
                'is_primary' => ($catId == $primaryCategoryId),
            ]);
        }

        $new = $product->only(array_keys($old));
        $this->logger->productUpdated(auth('admin')->user(), $product, $old, $new);

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $this->logger->productDeleted(auth('admin')->user(), $product);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    public function storeImage(Request $request, Product $product)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,webp|max:4096',
            'alt_text' => 'nullable|string|max:255',
            'type' => 'nullable|in:main,gallery,thumbnail',
        ]);

        $type = $validated['type'] ?? 'gallery';
        $file = $request->file('image');
        $path = $file->store('products', 's3');
        $imagePath = $path;

        if ($type === 'main') {
            ProductImage::where('product_id', $product->id)
                ->where('type', 'main')
                ->update(['type' => 'gallery']);
        }

        $maxSort = ProductImage::where('product_id', $product->id)->max('sort_order') ?? 0;

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => $imagePath,
            'alt_text' => $validated['alt_text'] ?? null,
            'type' => $type,
            'sort_order' => $maxSort + 1,
        ]);

        $this->logger->log('product_image_added', $product, 'Image added to "' . $product->name . '".');

        return redirect()->back()->with('success', 'Image uploaded.');
    }

    public function destroyImage(ProductImage $image)
    {
        $product = $image->product;

        if ($image->image_path && ! str_starts_with($image->image_path, 'http') && Storage::disk('s3')->exists($image->image_path)) {
            Storage::disk('s3')->delete($image->image_path);
        }

        $image->delete();

        return redirect()->back()->with('success', 'Image deleted.');
    }

    public function sortImage(Request $request, ProductImage $image)
    {
        $request->validate([
            'sort_order' => 'required|integer|min:0',
        ]);

        $image->update(['sort_order' => $request->input('sort_order')]);

        return redirect()->back()->with('success', 'Sort order updated.');
    }

    public function storeVariant(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'sku' => 'nullable|unique:product_variants,sku',
            'mrp' => 'required|numeric',
            'selling_price' => 'required|numeric|lte:mrp',
            'stock' => 'nullable|integer|min:0',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['product_id'] = $product->id;
        $validated['stock'] = $validated['stock'] ?? 0;
        $validated['status'] = $validated['status'] ?? 'active';

        ProductVariant::create($validated);

        return redirect()->back()->with('success', 'Variant added.');
    }

    public function updateVariant(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'sku' => 'nullable|unique:product_variants,sku,' . $variant->id,
            'mrp' => 'required|numeric',
            'selling_price' => 'required|numeric|lte:mrp',
            'stock' => 'nullable|integer|min:0',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);

        $variant->update($validated);

        return redirect()->back()->with('success', 'Variant updated.');
    }

    public function destroyVariant(ProductVariant $variant)
    {
        $variant->delete();

        return redirect()->back()->with('success', 'Variant deleted.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt|max:3072',
        ]);

        $file = $request->file('import_file');
        $imported = 0;

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, file_get_contents($file->getRealPath()));
        rewind($handle);

        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            try {
                $data = array_combine($header, $row);

                if (empty($data['name'])) {
                    continue;
                }

                $slug = Str::slug($data['name']);

                Product::updateOrCreate(
                    ['sku' => $data['sku'] ?? null],
                    [
                        'name' => $data['name'],
                        'slug' => $slug,
                        'mrp' => $data['mrp'] ?? 0,
                        'selling_price' => $data['selling_price'] ?? 0,
                        'gst_rate' => $data['gst_rate'] ?? 0,
                        'stock' => $data['stock'] ?? 0,
                        'status' => $data['status'] ?? 'draft',
                    ]
                );

                if (! empty($data['category_slug'])) {
                    $category = Category::where('slug', $data['category_slug'])->first();
                    if ($category) {
                        $product = Product::where('sku', $data['sku'])->first();
                        if ($product) {
                            $product->categories()->syncWithoutDetaching([$category->id]);
                        }
                    }
                }

                $imported++;
            } catch (\Exception $e) {
                continue;
            }
        }

        fclose($handle);

        return redirect()->back()->with('success', $imported . ' products imported.');
    }

    public function export()
    {
        $products = Product::select([
            'name', 'slug', 'sku', 'mrp', 'selling_price', 'gst_rate',
            'stock', 'status', 'is_featured', 'barcode',
        ])->get();

        $filename = 'products_export_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['name', 'slug', 'sku', 'mrp', 'selling_price', 'gst_rate', 'stock', 'status', 'is_featured', 'barcode']);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->name,
                    $product->slug,
                    $product->sku,
                    $product->mrp,
                    $product->selling_price,
                    $product->gst_rate,
                    $product->stock,
                    $product->status,
                    $product->is_featured ? '1' : '0',
                    $product->barcode,
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }

    public function updateStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'type' => 'required|in:sale,purchase,adjustment,return,reversal',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required_if:type,adjustment|nullable|string|max:500',
        ]);

        $change = in_array($validated['type'], ['purchase', 'return', 'reversal'])
            ? $validated['quantity']
            : -$validated['quantity'];

        $old = $product->stock;

        $this->inventoryService->adjust(
            $validated['type'],
            $product,
            $change,
            $validated['reason'] ?? $validated['type'] . ' adjustment',
            auth('admin')->user(),
        );

        $this->logger->stockChanged(auth('admin')->user(), $product, $old, $product->fresh()->stock, $validated['reason'] ?? null);

        return redirect()->back()->with('success', 'Stock updated.');
    }
}
