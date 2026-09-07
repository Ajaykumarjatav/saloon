@php
    $currencyCode = $currentSalon->currency ?? \App\Helpers\CurrencyHelper::defaultCode();
    $currencySymbol = \App\Helpers\CurrencyHelper::symbol($currencyCode);
    $priceGuardCatalog = $servicesPayload ?? [];
    $priceGuardIds = $initialSelectedIds ?? [];
@endphp
<div x-data="packagePriceGuard(@js($priceGuardCatalog), @js($priceGuardIds), @js((string) ($priceValue ?? '')), @js($currencySymbol))"
     @package-services-changed.window="catalogTotal = Number($event.detail.total) || 0; syncValidity($refs.priceEl)"
     x-effect="syncValidity($refs.priceEl)"
     class="space-y-2">
    <label class="form-label">Package price ({{ $currencySymbol }}) <span class="text-red-500">*</span></label>

    <div class="grid gap-2 items-stretch"
         :class="catalogTotal > 0 ? 'grid-cols-2' : 'grid-cols-1'">
        <input type="number" name="price" x-ref="priceEl" x-model="price" value="{{ $priceValue }}" required min="0" step="0.01"
               class="form-input w-full min-w-0 @error('price') form-input-error @enderror"
               :max="catalogTotal > 0 ? catalogTotal : null"
               :class="overLimit() ? 'form-input-error' : ''"
               placeholder="0">

        {{-- Summary: equal half beside price --}}
        <div class="w-full min-w-0 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 px-3 py-2 flex items-center"
             x-show="catalogTotal > 0" x-cloak>
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm leading-snug">
                <span class="text-muted whitespace-nowrap">
                    Services total
                    <span class="font-semibold text-heading tabular-nums" x-text="formatTotal()"></span>
                </span>
                <template x-if="discountPercent() !== null">
                    <span class="inline-flex items-center gap-1.5 flex-wrap">
                        <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">·</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 tabular-nums whitespace-nowrap"
                              x-text="discountPercent() + '% off'"></span>
                        <span class="text-xs text-muted tabular-nums whitespace-nowrap" x-text="'save ' + formatSavings()"></span>
                    </span>
                </template>
                <template x-if="discountPercent() === null && !overLimit() && parsedPrice() !== null">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">·</span>
                        <span class="text-xs text-muted">Same as services total</span>
                    </span>
                </template>
            </div>
        </div>
    </div>

    <p class="text-xs text-muted" x-show="catalogTotal <= 0" x-cloak>
        Add services above to see their total and any package discount.
    </p>

    <p class="form-error" x-show="overLimit()" x-cloak>
        Package price cannot be greater than the selected services total (<span x-text="formatTotal()"></span>).
    </p>
    @error('price')
        <p class="form-error" x-show="!overLimit()">{{ $message }}</p>
    @enderror
</div>

@once
@push('scripts')
<script>
function packagePriceGuard(catalog, initialIds, initialPrice, currencySymbol) {
    const byId = Object.fromEntries((catalog || []).map(s => [s.id, s]));
    const ids = Array.isArray(initialIds) ? initialIds.map(id => parseInt(id, 10)) : [];
    const catalogTotal = ids.reduce((sum, id) => {
        const s = byId[id];
        return sum + (s ? Number(s.price) || 0 : 0);
    }, 0);
    const symbol = currencySymbol || '';

    return {
        price: initialPrice ?? '',
        catalogTotal,
        formatMoney(n) {
            const value = Number(n) || 0;
            const formatted = Number.isInteger(value) ? String(value) : value.toFixed(2);
            return (symbol ? symbol : '') + formatted;
        },
        formatTotal() {
            return this.formatMoney(this.catalogTotal);
        },
        parsedPrice() {
            const p = parseFloat(this.price);
            return Number.isNaN(p) ? null : p;
        },
        discountPercent() {
            const p = this.parsedPrice();
            if (p === null || this.catalogTotal <= 0 || p < 0) {
                return null;
            }
            if (p >= this.catalogTotal - 0.001) {
                return null;
            }
            const pct = ((this.catalogTotal - p) / this.catalogTotal) * 100;
            if (pct < 0.05) {
                return null;
            }
            return Math.round(pct * 10) / 10;
        },
        formatSavings() {
            const p = this.parsedPrice();
            if (p === null || this.catalogTotal <= 0) {
                return this.formatMoney(0);
            }
            return this.formatMoney(Math.max(0, this.catalogTotal - p));
        },
        overLimit() {
            const p = this.parsedPrice();
            if (p === null || this.catalogTotal <= 0) {
                return false;
            }
            return p > this.catalogTotal + 0.001;
        },
        syncValidity(el) {
            if (!el) {
                return;
            }
            if (this.overLimit()) {
                el.setCustomValidity('Package price cannot be greater than the selected services total.');
            } else {
                el.setCustomValidity('');
            }
        },
    };
}
</script>
@endpush
@endonce
