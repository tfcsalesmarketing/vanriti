<?php

namespace App\Http\Controllers\Admin;

use App\Dadi\Enums\Concern;
use App\Dadi\Enums\ProductProfileStatus;
use App\Dadi\Enums\Section;
use App\Dadi\Exceptions\DadiException;
use App\Dadi\Persistence\DadiProductProfileStore;
use App\Http\Controllers\Controller;
use App\Models\DadiProductProfile;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Narrow admin review surface for Dadi product intelligence.
 *
 * Only lists, inspects, authors and reviews profiles. It guards every mutation
 * through the DadiProductProfileStore, which requires an authorized admin and
 * performs the same validation the AI-facing boundary would. Human approval is
 * the only way intelligence becomes usable — there is no path here for
 * customer or AI code.
 */
class DadiProductProfileController extends Controller
{
    public function __construct(
        private readonly DadiProductProfileStore $store,
    ) {}

    public function index(Request $request): View
    {
        $query = DadiProductProfile::query()
            ->with(['product', 'creator', 'reviewer']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($q = $request->input('q')) {
            $query->whereHas('product', function ($product) use ($q) {
                $product->where('name', 'like', "%{$q}%");
            });
        }

        $profiles = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.dadi-product-profiles.index', [
            'profiles' => $profiles,
            'statuses' => ProductProfileStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $products = Product::query()
            ->whereDoesntHave('dadiProductProfile')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'status']);

        return view('admin.dadi-product-profiles.create', [
            'products' => $products,
            'sections' => Section::cases(),
            'concerns' => Concern::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        $product = Product::query()->findOrFail((int) $request->input('product_id'));

        try {
            $profile = $this->store->create($product, $this->intelligenceInput($request), $request->user('admin'));
        } catch (DadiException $e) {
            return back()->withInput()->withErrors(['content' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.dadi.product-profiles.edit', $profile)
            ->with('success', 'Dadi profile created as draft.');
    }

    public function edit(DadiProductProfile $profile): View
    {
        return view('admin.dadi-product-profiles.edit', [
            'profile' => $profile->load(['product', 'creator', 'reviewer']),
            'sections' => Section::cases(),
            'concerns' => Concern::cases(),
            'statuses' => ProductProfileStatus::cases(),
        ]);
    }

    public function update(Request $request, DadiProductProfile $profile): RedirectResponse
    {
        try {
            $this->store->updateIntelligence($profile, $this->intelligenceInput($request), $request->user('admin'));
        } catch (DadiException $e) {
            return back()->withInput()->withErrors(['content' => $e->getMessage()]);
        }

        return back()->with('success', 'Profile intelligence updated.');
    }

    public function submitReview(Request $request, DadiProductProfile $profile): RedirectResponse
    {
        return $this->runLifecycle($request, $profile, 'Profile submitted for review.', function () use ($profile, $request): void {
            $this->store->submitForReview($profile, $request->user('admin'));
        });
    }

    public function approve(Request $request, DadiProductProfile $profile): RedirectResponse
    {
        return $this->runLifecycle($request, $profile, 'Profile approved. Availability still depends on the live product status.', function () use ($profile, $request): void {
            $this->store->approve($profile, $request->user('admin'), $request->input('notes'));
        });
    }

    public function reject(Request $request, DadiProductProfile $profile): RedirectResponse
    {
        return $this->runLifecycle($request, $profile, 'Profile rejected.', function () use ($profile, $request): void {
            $this->store->reject($profile, $request->user('admin'), $request->input('notes'));
        });
    }

    /**
     * @return array<string,mixed>
     */
    private function intelligenceInput(Request $request): array
    {
        return [
            'sections' => $request->input('sections', []),
            'concerns' => $request->input('concerns', []),
            'positioning' => $request->input('positioning'),
            'approved_benefits' => $this->lines($request->input('approved_benefits')),
            'approved_usage_context' => $this->lines($request->input('approved_usage_context')),
            'approved_precautions' => $this->lines($request->input('approved_precautions')),
            'suitability_notes' => $this->lines($request->input('suitability_notes')),
        ];
    }

    /**
     * @return array<int,string>
     */
    private function lines(mixed $value): array
    {
        $out = [];

        if (! is_string($value) || trim($value) === '') {
            return $out;
        }

        foreach (preg_split('/\r\n|\r|\n/', $value) ?? [] as $line) {
            $trimmed = trim($line);

            if ($trimmed !== '') {
                $out[] = $trimmed;
            }
        }

        return $out;
    }

    private function runLifecycle(Request $request, DadiProductProfile $profile, string $success, callable $transition): RedirectResponse
    {
        try {
            $transition();
        } catch (DadiException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }
}
