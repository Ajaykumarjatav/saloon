<?php

namespace App\Support;

use App\Helpers\CurrencyHelper;
use App\Models\Salon;
use App\Models\SalonResource;
use App\Models\Service;
use App\Models\Staff;
use App\Scopes\TenantScope;
use Illuminate\Support\Collection;

/**
 * Maps tenant salons into the EasyGrox marketplace app payload.
 * Does not mutate salon records or booking rules.
 */
final class MarketplaceStorePresenter
{
    private const IMAGE_COLORS = [
        '#1F2937', '#7C3AED', '#EC4899', '#059669', '#3B82F6', '#D97706', '#0F766E', '#B45309',
    ];

    private const CATEGORY_META = [
        'womens' => ['emoji' => '💇', 'accent' => '#F9E8FF', 'label' => "Women's"],
        'mans' => ['emoji' => '💈', 'accent' => '#E8F0FF', 'label' => "Men's"],
        'unisex' => ['emoji' => '✨', 'accent' => '#EEE9FF', 'label' => 'Unisex'],
        'pet' => ['emoji' => '🐾', 'accent' => '#FFF4E5', 'label' => 'Pet'],
    ];

    public static function categoryMeta(string $slug, ?string $name = null): array
    {
        $meta = self::CATEGORY_META[$slug] ?? [
            'emoji' => '🏪',
            'accent' => '#F5F3FF',
            'label' => $name ?: ucfirst(str_replace('-', ' ', $slug)),
        ];

        if ($name) {
            $meta['label'] = $name;
        }

        return $meta;
    }

    public static function distanceKm(?float $fromLat, ?float $fromLng, Salon $salon): ?float
    {
        if ($fromLat === null || $fromLng === null || $salon->latitude === null || $salon->longitude === null) {
            return null;
        }

        $earthKm = 6371;
        $dLat = deg2rad((float) $salon->latitude - $fromLat);
        $dLng = deg2rad((float) $salon->longitude - $fromLng);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad((float) $salon->latitude)) * sin($dLng / 2) ** 2;

        return round($earthKm * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }

    /**
     * @param  Collection<int, Service>  $services
     */
    public static function card(Salon $salon, Collection $services, ?float $fromLat = null, ?float $fromLng = null): array
    {
        $currency = $salon->currency ?: CurrencyHelper::defaultCode();
        $category = $salon->businessType;
        $categorySlug = $category?->slug ?? 'unisex';
        $meta = self::categoryMeta($categorySlug, $category?->name);
        $starting = $services->min(fn (Service $s) => (float) $s->price);
        $reviews = ($salon->relationLoaded('reviews') ? $salon->reviews : collect())
            ->filter(fn ($review) => ($review->is_public ?? true) !== false);
        $avg = $reviews->avg('rating');

        return [
            'id' => $salon->id,
            'slug' => $salon->slug,
            'name' => $salon->name,
            'tagline' => $salon->description ?: ($salon->city ? 'Trusted services in '.$salon->city : 'Book online'),
            'category_id' => $categorySlug,
            'category_label' => $meta['label'],
            'address' => trim(implode(', ', array_filter([
                $salon->address_line1,
                $salon->address_line2,
            ]))) ?: ($salon->city ?? ''),
            'city' => $salon->city,
            'rating' => round((float) ($avg ?? 0), 1),
            'review_count' => $reviews->count(),
            'distance_km' => self::distanceKm($fromLat, $fromLng, $salon) ?? 0,
            'starting_price' => $starting !== null ? (float) $starting : 0,
            'currency' => $currency,
            'currency_symbol' => CurrencyHelper::symbol($currency),
            'image_color' => self::IMAGE_COLORS[$salon->id % count(self::IMAGE_COLORS)],
            'logo_url' => PublicStorage::url($salon->logo),
            'cover_url' => PublicStorage::url($salon->cover_image),
            'online_booking_enabled' => (bool) $salon->online_booking_enabled,
            'home_services_enabled' => (bool) $salon->home_services_enabled,
        ];
    }

    /**
     * @param  Collection<int, Service>  $services
     * @param  Collection<int, Staff>  $staff
     * @param  Collection<int, SalonResource>  $resources
     */
    public static function profile(
        Salon $salon,
        Collection $services,
        Collection $staff,
        Collection $resources,
        ?float $fromLat = null,
        ?float $fromLng = null,
    ): array {
        $card = self::card($salon, $services, $fromLat, $fromLng);
        $grouped = $services->groupBy(fn (Service $s) => $s->category_id ?: 0);

        $categories = $grouped->map(function (Collection $items, $categoryId) {
            $first = $items->first();
            $category = $first?->category;

            return [
                'id' => (int) ($category?->id ?: $categoryId ?: 0),
                'name' => $category?->name ?: 'Services',
                'business_type' => $category?->businessType?->name ?: $first?->businessType?->name,
                'services' => $items->map(fn (Service $s) => self::service($s))->values()->all(),
            ];
        })->values()->all();

        $serviceIds = $services->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        $equipment = $resources->map(function (SalonResource $resource) use ($serviceIds) {
            $kit = is_array($resource->equipment) ? array_values(array_filter($resource->equipment, 'is_string')) : [];

            return [
                'id' => $resource->id,
                'name' => $resource->name,
                'type' => in_array($resource->type, ['room', 'chair', 'station'], true) ? $resource->type : 'station',
                'description' => $resource->type ? ucfirst(str_replace('_', ' ', (string) $resource->type)) : 'Bookable station',
                'equipment' => $kit,
                'service_ids' => $serviceIds,
            ];
        })->values()->all();

        $properties = $equipment === []
            ? []
            : [[
                'id' => $salon->id * 10 + 1,
                'name' => $salon->name.' floor',
                'kind' => 'treatment_room',
                'description' => 'Bookable rooms and stations at '.$salon->name.'.',
                'equipment' => $equipment,
            ]];

        return array_merge($card, [
            'phone' => $salon->phone,
            'email' => $salon->email,
            'opening_hours' => self::openingHours($salon),
            'categories' => $categories,
            'staff' => $staff->map(fn (Staff $member) => self::staff($member))->values()->all(),
            'properties' => $properties,
            'is_active' => true,
            'marketplace_category_id' => $card['category_id'],
        ]);
    }

    public static function service(Service $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'price' => (float) $service->price,
            'duration_minutes' => (int) $service->duration_minutes,
            'buffer_minutes' => (int) ($service->buffer_minutes ?? 0),
        ];
    }

    public static function staff(Staff $staff): array
    {
        $days = is_array($staff->working_days) ? $staff->working_days : [];

        return [
            'id' => $staff->id,
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'start_time' => substr((string) ($staff->start_time ?? '09:00'), 0, 5),
            'end_time' => substr((string) ($staff->end_time ?? '18:00'), 0, 5),
            'working_days' => $days,
            'avatar_color' => $staff->color ?: '#8B5CF6',
            'role' => $staff->role,
            'avatar_url' => Staff::resolvePublicAvatarUrl($staff->avatar),
        ];
    }

    public static function openingHours(Salon $salon): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $out = [];

        foreach ($days as $day) {
            $row = $salon->openingHoursForWeekdayKey($day);
            $open = (bool) ($row['open'] ?? false);
            $from = substr((string) ($row['from'] ?? $row['start'] ?? '09:00'), 0, 5);
            $to = substr((string) ($row['to'] ?? $row['end'] ?? '18:00'), 0, 5);
            $out[$day] = [
                'open' => $open,
                'from' => $from,
                'to' => $to,
            ];
        }

        return $out;
    }

    public static function bookableServicesQuery(Salon $salon)
    {
        return Service::withoutGlobalScope(TenantScope::class)
            ->with(['category.businessType', 'businessType'])
            ->where('salon_id', $salon->id)
            ->where('status', 'active')
            ->where('online_bookable', true)
            ->where('show_in_menu', true)
            ->eligibleForPublicBooking($salon)
            ->orderBy('sort_order');
    }
}
