<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\MarketplaceCustomer;
use App\Scopes\TenantScope;
use App\Support\SalonTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceBookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var MarketplaceCustomer $customer */
        $customer = $request->user();

        $clientIds = Client::withoutGlobalScope(TenantScope::class)
            ->where(function ($q) use ($customer) {
                $q->where('email', $customer->email);
                if ($customer->phone) {
                    $q->orWhere('phone', $customer->phone);
                }
            })
            ->pluck('id');

        if ($clientIds->isEmpty()) {
            return response()->json(['bookings' => []]);
        }

        $appointments = Appointment::withoutGlobalScope(TenantScope::class)
            ->with([
                'salon:id,slug,name,business_type_id,currency',
                'salon.businessType:id,name,slug',
                'staff:id,first_name,last_name',
                'services',
            ])
            ->whereIn('client_id', $clientIds)
            ->whereNull('deleted_at')
            ->orderByDesc('starts_at')
            ->limit(100)
            ->get();

        $bookings = $appointments->map(function (Appointment $appointment) {
            $salon = $appointment->salon;
            $tz = $salon ? SalonTime::timezone($salon) : config('app.timezone');
            $starts = $appointment->starts_at?->timezone($tz);

            return [
                'id' => (string) $appointment->id,
                'reference' => $appointment->reference,
                'status' => $appointment->status,
                'storeSlug' => $salon?->slug,
                'storeName' => $salon?->name,
                'storeCategory' => $salon?->businessType?->name,
                'storeColor' => '#7C3AED',
                'serviceNames' => $appointment->services->pluck('service_name')->filter()->values()->all(),
                'date' => $starts?->format('Y-m-d'),
                'time' => $starts?->format('H:i'),
                'staffName' => $appointment->staff
                    ? trim($appointment->staff->first_name.' '.$appointment->staff->last_name)
                    : 'Any available',
                'totalPrice' => (float) $appointment->total_price,
                'currencySymbol' => \App\Helpers\CurrencyHelper::symbol($salon?->currency ?: \App\Helpers\CurrencyHelper::defaultCode()),
                'createdAt' => $appointment->created_at?->toIso8601String(),
            ];
        })->values();

        return response()->json(['bookings' => $bookings]);
    }
}
