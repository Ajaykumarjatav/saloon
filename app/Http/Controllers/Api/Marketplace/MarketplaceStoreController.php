<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Models\SalonResource;
use App\Models\Staff;
use App\Scopes\TenantScope;
use App\Support\MarketplaceStorePresenter;
use App\Support\PublicSalonAccess;
use App\Support\StorefrontTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceStoreController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json([
            'categories' => MarketplaceStorePresenter::marketplaceCategories(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'nullable|string|max:80',
            'q' => 'nullable|string|max:120',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $lat = isset($validated['lat']) ? (float) $validated['lat'] : null;
        $lng = isset($validated['lng']) ? (float) $validated['lng'] : null;
        $limit = (int) ($validated['limit'] ?? 50);

        $query = PublicSalonAccess::query()
            ->with(['businessType', 'reviews:id,salon_id,rating,is_public'])
            ->where('online_booking_enabled', true);

        if (! empty($validated['category'])) {
            $themeSlug = StorefrontTheme::normalizeSlug($validated['category']);
            $rawValues = MarketplaceStorePresenter::themeSettingValues($themeSlug);
            $defaultTheme = StorefrontTheme::default();

            $query->where(function ($q) use ($rawValues, $themeSlug, $defaultTheme) {
                $q->whereHas('settings', function ($settings) use ($rawValues) {
                    $settings->withoutGlobalScopes()
                        ->where('key', 'website_theme')
                        ->whereIn('value', $rawValues);
                });

                // Salons without a theme setting use the default website theme.
                if ($themeSlug === $defaultTheme) {
                    $q->orWhereDoesntHave('settings', function ($settings) {
                        $settings->withoutGlobalScopes()
                            ->where('key', 'website_theme')
                            ->whereNotNull('value')
                            ->where('value', '!=', '');
                    });
                }
            });
        }

        if (! empty($validated['q'])) {
            $term = '%'.$validated['q'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        $salons = $query->orderBy('name')->limit($limit)->get();

        $stores = $salons->map(function (Salon $salon) use ($lat, $lng) {
            $services = MarketplaceStorePresenter::bookableServicesQuery($salon)->get();

            return MarketplaceStorePresenter::profile(
                $salon,
                $services,
                $this->bookableStaff($salon),
                $this->resources($salon),
                $lat,
                $lng,
            );
        })->values();

        if ($lat !== null && $lng !== null) {
            $stores = $stores->sortBy('distance_km')->values();
        }

        return response()->json(['stores' => $stores]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $salon = PublicSalonAccess::findBySlugOrFail($slug);

        if (! $salon->online_booking_enabled) {
            return response()->json(['message' => 'Online booking is currently unavailable.'], 503);
        }

        $lat = $request->filled('lat') ? (float) $request->query('lat') : null;
        $lng = $request->filled('lng') ? (float) $request->query('lng') : null;

        $store = MarketplaceStorePresenter::profile(
            $salon->load(['businessType', 'reviews']),
            MarketplaceStorePresenter::bookableServicesQuery($salon)->get(),
            $this->bookableStaff($salon),
            $this->resources($salon),
            $lat,
            $lng,
        );

        return response()->json(['store' => $store]);
    }

    private function bookableStaff(Salon $salon)
    {
        return Staff::withoutGlobalScope(TenantScope::class)
            ->where('salon_id', $salon->id)
            ->onlineBookable()
            ->orderBy('sort_order')
            ->get();
    }

    private function resources(Salon $salon)
    {
        return SalonResource::withoutGlobalScope(TenantScope::class)
            ->where('salon_id', $salon->id)
            ->where('bookable', true)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
