<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Book at {{ $salon->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        .slot-btn { transition: all .15s ease; }
        .slot-btn:hover { transform: translateY(-1px); }
        .card { background: white; border-radius: 16px; border: 1.5px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
        .card:hover { border-color: #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,.06); }
        .selected-card { border-color: #7c3aed !important; box-shadow: 0 0 0 3px rgba(124,58,237,.12) !important; }
        .btn-primary { background: #7c3aed; color: white; border-radius: 12px; font-weight: 600; padding: 14px 24px; transition: all .15s; }
        .btn-primary:hover:not(:disabled) { background: #6d28d9; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(124,58,237,.3); }
        .btn-primary:disabled { opacity: .45; cursor: not-allowed; }
        .step-dot { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; transition: all .2s; }
        .step-done { background: #10b981; color: white; }
        .step-active { background: #7c3aed; color: white; }
        .step-idle { background: #f1f5f9; color: #94a3b8; }
        .time-slot { border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 8px; text-align: center; font-size: 13px; font-weight: 500; color: #374151; cursor: pointer; transition: all .15s; }
        .time-slot:hover { border-color: #7c3aed; color: #7c3aed; background: #faf5ff; }
        .time-slot.selected { background: #7c3aed; color: white; border-color: #7c3aed; }
        .input-field { width: 100%; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 11px 14px; font-size: 14px; outline: none; transition: border .15s; }
        .input-field:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
        .summary-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f8fafc; font-size: 14px; }
        .summary-row:last-child { border-bottom: none; }
    </style>
</head>
<body style="background: #f8fafc; min-height: 100vh;">

<div x-data="bookingApp()" x-init="init()">

    {{-- ── HEADER ── --}}
    <header style="background:white; border-bottom: 1px solid #f1f5f9; position: sticky; top:0; z-index:50;">
        <div style="max-width:680px; margin:0 auto; padding:16px 20px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                @if($salon->logo)
                <img src="{{ asset('storage/' . $salon->logo) }}" alt="{{ $salon->name }}"
                     style="width:44px;height:44px;border-radius:12px;object-fit:cover;">
                @else
                <div style="width:44px;height:44px;border-radius:12px;background:#7c3aed;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:18px;">
                    {{ strtoupper(substr($salon->name, 0, 1)) }}
                </div>
                @endif
                <div>
                    <div style="font-weight:700;font-size:16px;color:#111827;">{{ $salon->name }}</div>
                    @if($salon->address_line1)
                    <div style="font-size:12px;color:#9ca3af;">{{ $salon->address_line1 }}{{ $salon->city ? ', '.$salon->city : '' }}</div>
                    @endif
                </div>
            </div>
            {{-- Step bar --}}
            <div style="display:flex;align-items:center;gap:0;">
                @php $steps = ['Service','Staff','Date & Time','Your Details','Confirm']; @endphp
                @foreach($steps as $i => $label)
                <div style="display:flex;align-items:center;flex:1;">
                    <div style="display:flex;flex-direction:column;align-items:center;gap:3px;">
                        <div class="step-dot"
                             :class="step > {{ $i }} ? 'step-done' : (step === {{ $i }} ? 'step-active' : 'step-idle')">
                            <span x-text="step > {{ $i }} ? '✓' : '{{ $i+1 }}'"></span>
                        </div>
                        <span style="font-size:10px;font-weight:500;white-space:nowrap;"
                              :style="step === {{ $i }} ? 'color:#7c3aed' : 'color:#9ca3af'">{{ $label }}</span>
                    </div>
                    @if($i < count($steps)-1)
                    <div style="flex:1;height:2px;margin:0 4px;margin-bottom:14px;"
                         :style="step > {{ $i }} ? 'background:#10b981' : 'background:#e2e8f0'"></div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </header>

    <main style="max-width:680px;margin:0 auto;padding:24px 20px 60px;">

        {{-- Loading --}}
        <div x-show="loading" style="display:flex;justify-content:center;padding:60px 0;">
            <div style="text-align:center;">
                <svg style="width:40px;height:40px;animation:spin 1s linear infinite;color:#7c3aed;margin:0 auto 12px;" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <p style="color:#9ca3af;font-size:14px;">Loading booking info…</p>
            </div>
        </div>

        {{-- Global error --}}
        <div x-show="globalError" x-cloak
             style="background:#fef2f2;border:1.5px solid #fecaca;border-radius:12px;padding:14px 16px;margin-bottom:16px;color:#dc2626;font-size:14px;"
             x-text="globalError"></div>


        {{-- ── STEP 0: Services ── --}}
        <div x-show="step === 0 && !loading" x-cloak>
            <h2 style="font-size:20px;font-weight:700;color:#111827;margin-bottom:6px;">Choose a service</h2>
            <p style="font-size:13px;color:#9ca3af;margin-bottom:20px;">Select the service you'd like to book</p>

            <div x-show="allServices.length === 0" style="text-align:center;padding:40px;color:#9ca3af;font-size:14px;">
                No services available for online booking at this time.
            </div>

            <template x-for="cat in allServices" :key="cat.name">
                <div style="margin-bottom:24px;">
                    <div x-show="cat.name !== 'Uncategorised'"
                         style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;"
                         x-text="cat.name"></div>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <template x-for="svc in cat.services" :key="svc.id">
                            <button @click="selectService(svc)"
                                    class="card"
                                    :class="selected.service?.id === svc.id ? 'selected-card' : ''"
                                    style="width:100%;text-align:left;padding:16px 18px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                                <div style="flex:1;min-width:0;">
                                    <div style="font-weight:600;font-size:15px;color:#111827;" x-text="svc.name"></div>
                                    <div style="font-size:12px;color:#9ca3af;margin-top:3px;"
                                         x-text="(svc.duration_minutes ?? 0) + ' min' + (svc.description ? ' · ' + svc.description.substring(0,60) : '')"></div>
                                </div>
                                <div style="text-align:right;flex-shrink:0;">
                                    <div style="font-weight:700;font-size:16px;color:#111827;"
                                         x-text="'£' + parseFloat(svc.price || 0).toFixed(2)"></div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- ── STEP 1: Staff ── --}}
        <div x-show="step === 1 && !loading" x-cloak>
            <h2 style="font-size:20px;font-weight:700;color:#111827;margin-bottom:6px;">Choose a team member</h2>
            <p style="font-size:13px;color:#9ca3af;margin-bottom:20px;">Pick who you'd like to be seen by</p>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <button @click="selectStaff(null)"
                        class="card"
                        :class="selected.staff === null ? 'selected-card' : ''"
                        style="width:100%;text-align:left;padding:14px 18px;cursor:pointer;display:flex;align-items:center;gap:14px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">🎲</div>
                    <div>
                        <div style="font-weight:600;font-size:15px;color:#111827;">Any available</div>
                        <div style="font-size:12px;color:#9ca3af;margin-top:2px;">We'll assign the best available team member</div>
                    </div>
                </button>
                <template x-for="member in staffList" :key="member.id">
                    <button @click="selectStaff(member)"
                            class="card"
                            :class="selected.staff?.id === member.id ? 'selected-card' : ''"
                            style="width:100%;text-align:left;padding:14px 18px;cursor:pointer;display:flex;align-items:center;gap:14px;">
                        <div style="width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:16px;flex-shrink:0;"
                             :style="`background:${member.color || '#7c3aed'}`"
                             x-text="(member.first_name || '?').charAt(0).toUpperCase()"></div>
                        <div>
                            <div style="font-weight:600;font-size:15px;color:#111827;"
                                 x-text="(member.first_name || '') + ' ' + (member.last_name || '')"></div>
                            <div style="font-size:12px;color:#9ca3af;margin-top:2px;text-transform:capitalize;"
                                 x-text="(member.role || '').replace(/_/g,' ')"></div>
                        </div>
                    </button>
                </template>
            </div>
            <button @click="step = 0" style="margin-top:16px;color:#9ca3af;font-size:13px;background:none;border:none;cursor:pointer;">← Back to services</button>
        </div>


        {{-- ── STEP 2: Date & Time ── --}}
        <div x-show="step === 2 && !loading" x-cloak>
            <h2 style="font-size:20px;font-weight:700;color:#111827;margin-bottom:6px;">Pick a date &amp; time</h2>
            <p style="font-size:13px;color:#9ca3af;margin-bottom:20px;">Choose when you'd like to come in</p>

            {{-- Date selector --}}
            <div class="card" style="padding:16px 18px;margin-bottom:16px;">
                <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:8px;">Select date</label>
                <input type="date" class="input-field"
                       :min="today" :max="maxDate"
                       x-model="selected.date"
                       @change="loadSlots()">
            </div>

            {{-- Slots --}}
            <div x-show="selected.date">
                <div x-show="slotsLoading" style="text-align:center;padding:30px;">
                    <svg style="width:28px;height:28px;animation:spin 1s linear infinite;color:#7c3aed;margin:0 auto;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </div>

                <div x-show="!slotsLoading && slots.length > 0">
                    <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:10px;"
                         x-text="slots.length + ' slots available'"></div>
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
                        <template x-for="slot in slots" :key="slot.time">
                            <button @click="selectSlot(slot)"
                                    class="time-slot"
                                    :class="selected.slot?.time === slot.time ? 'selected' : ''"
                                    x-text="slot.time"></button>
                        </template>
                    </div>
                </div>

                <div x-show="!slotsLoading && slots.length === 0 && selected.date"
                     style="text-align:center;padding:32px;background:white;border-radius:16px;border:1.5px solid #f1f5f9;">
                    <div style="font-size:32px;margin-bottom:8px;">📅</div>
                    <div style="font-weight:600;color:#374151;margin-bottom:4px;">No availability</div>
                    <div style="font-size:13px;color:#9ca3af;">Try selecting a different date</div>
                </div>
            </div>

            <button @click="step = 1" style="margin-top:16px;color:#9ca3af;font-size:13px;background:none;border:none;cursor:pointer;">← Back</button>
        </div>

        {{-- ── STEP 3: Details ── --}}
        <div x-show="step === 3 && !loading" x-cloak>
            <h2 style="font-size:20px;font-weight:700;color:#111827;margin-bottom:6px;">Your details</h2>
            <p style="font-size:13px;color:#9ca3af;margin-bottom:20px;">We'll send your confirmation here</p>

            <div class="card" style="padding:20px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">First name *</label>
                        <input type="text" class="input-field" x-model="client.first_name" placeholder="Jane">
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Last name *</label>
                        <input type="text" class="input-field" x-model="client.last_name" placeholder="Smith">
                    </div>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Email address *</label>
                    <input type="email" class="input-field" x-model="client.email" placeholder="jane@example.com">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Phone number *</label>
                    <input type="tel" class="input-field" x-model="client.phone" placeholder="+44 7700 000000">
                </div>
                <div style="margin-bottom:16px;">
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Notes (optional)</label>
                    <textarea class="input-field" x-model="client.notes" rows="2"
                              placeholder="Any special requests or things we should know…"
                              style="resize:none;"></textarea>
                </div>
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                    <input type="checkbox" x-model="client.marketing_consent" style="margin-top:2px;accent-color:#7c3aed;">
                    <span style="font-size:12px;color:#6b7280;">I agree to receive marketing messages from {{ $salon->name }}</span>
                </label>
            </div>

            <div x-show="detailsError" x-cloak
                 style="background:#fef2f2;border:1.5px solid #fecaca;border-radius:10px;padding:12px 14px;margin-top:12px;color:#dc2626;font-size:13px;"
                 x-text="detailsError"></div>

            <button @click="goToConfirm()"
                    class="btn-primary"
                    style="width:100%;margin-top:16px;display:block;"
                    :disabled="!client.first_name || !client.last_name || !client.email || !client.phone">
                Review booking →
            </button>
            <button @click="step = 2" style="margin-top:10px;color:#9ca3af;font-size:13px;background:none;border:none;cursor:pointer;width:100%;text-align:center;">← Back</button>
        </div>


        {{-- ── STEP 4: Confirm ── --}}
        <div x-show="step === 4 && !loading" x-cloak>
            <h2 style="font-size:20px;font-weight:700;color:#111827;margin-bottom:6px;">Confirm your booking</h2>
            <p style="font-size:13px;color:#9ca3af;margin-bottom:20px;">Please review the details below</p>

            {{-- Summary card --}}
            <div class="card" style="padding:20px;margin-bottom:16px;">
                <div class="summary-row">
                    <span style="color:#6b7280;">Service</span>
                    <span style="font-weight:600;color:#111827;" x-text="selected.service?.name"></span>
                </div>
                <div class="summary-row">
                    <span style="color:#6b7280;">With</span>
                    <span style="font-weight:600;color:#111827;"
                          x-text="selected.staff ? (selected.staff.first_name + ' ' + selected.staff.last_name) : 'Any available'"></span>
                </div>
                <div class="summary-row">
                    <span style="color:#6b7280;">Date</span>
                    <span style="font-weight:600;color:#111827;" x-text="formatDate(selected.date)"></span>
                </div>
                <div class="summary-row">
                    <span style="color:#6b7280;">Time</span>
                    <span style="font-weight:600;color:#111827;" x-text="selected.slot?.time"></span>
                </div>
                <div class="summary-row">
                    <span style="color:#6b7280;">Duration</span>
                    <span style="font-weight:600;color:#111827;" x-text="(selected.service?.duration_minutes ?? 0) + ' minutes'"></span>
                </div>
                <div class="summary-row" style="border-top:2px solid #f1f5f9;margin-top:4px;padding-top:14px;">
                    <span style="font-weight:700;color:#111827;font-size:15px;">Total</span>
                    <span style="font-weight:700;color:#7c3aed;font-size:18px;"
                          x-text="'£' + parseFloat(selected.service?.price || 0).toFixed(2)"></span>
                </div>
            </div>

            {{-- Client summary --}}
            <div class="card" style="padding:16px 18px;margin-bottom:16px;">
                <div style="font-size:12px;font-weight:600;color:#9ca3af;margin-bottom:10px;text-transform:uppercase;letter-spacing:.06em;">Your details</div>
                <div style="font-weight:600;color:#111827;font-size:14px;" x-text="client.first_name + ' ' + client.last_name"></div>
                <div style="font-size:13px;color:#6b7280;margin-top:2px;" x-text="client.email"></div>
                <div style="font-size:13px;color:#6b7280;" x-text="client.phone"></div>
            </div>

            <div x-show="bookingError" x-cloak
                 style="background:#fef2f2;border:1.5px solid #fecaca;border-radius:10px;padding:12px 14px;margin-bottom:12px;color:#dc2626;font-size:13px;"
                 x-text="bookingError"></div>

            <button @click="confirmBooking()"
                    class="btn-primary"
                    style="width:100%;display:flex;align-items:center;justify-content:center;gap:8px;"
                    :disabled="confirming">
                <svg x-show="confirming" style="width:16px;height:16px;animation:spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <span x-text="confirming ? 'Confirming…' : 'Confirm Booking'"></span>
            </button>
            <button @click="step = 3" style="margin-top:10px;color:#9ca3af;font-size:13px;background:none;border:none;cursor:pointer;width:100%;text-align:center;">← Edit details</button>
        </div>

        {{-- ── STEP 5: Success ── --}}
        <div x-show="step === 5" x-cloak style="text-align:center;padding:40px 0;">
            <div style="font-size:64px;margin-bottom:16px;">🎉</div>
            <h2 style="font-size:24px;font-weight:700;color:#111827;margin-bottom:8px;">You're all booked!</h2>
            <p style="font-size:14px;color:#6b7280;margin-bottom:4px;">
                A confirmation has been sent to <strong x-text="client.email"></strong>
            </p>
            <p style="font-size:13px;color:#9ca3af;margin-bottom:28px;" x-text="'Booking ref: ' + bookingRef"></p>

            <div class="card" style="padding:20px;text-align:left;max-width:380px;margin:0 auto 28px;">
                <div class="summary-row">
                    <span style="color:#6b7280;">Service</span>
                    <span style="font-weight:600;" x-text="selected.service?.name"></span>
                </div>
                <div class="summary-row">
                    <span style="color:#6b7280;">Date</span>
                    <span style="font-weight:600;" x-text="formatDate(selected.date)"></span>
                </div>
                <div class="summary-row">
                    <span style="color:#6b7280;">Time</span>
                    <span style="font-weight:600;" x-text="selected.slot?.time"></span>
                </div>
                <div class="summary-row">
                    <span style="color:#6b7280;">With</span>
                    <span style="font-weight:600;"
                          x-text="selected.staff ? (selected.staff.first_name + ' ' + selected.staff.last_name) : 'Any available'"></span>
                </div>
            </div>

            <a href="{{ url('/book/' . $salon->slug) }}"
               class="btn-primary"
               style="display:inline-block;text-decoration:none;">
                Book another appointment
            </a>
        </div>

    </main>

    <footer style="text-align:center;padding:20px;font-size:12px;color:#d1d5db;">
        Powered by <span style="font-weight:700;color:#9ca3af;">velour.</span>
    </footer>

</div>


<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<script>
const SLUG     = '{{ $salon->slug }}';
const BASE_URL = '{{ rtrim(config("app.url"), "/") }}';
const API      = BASE_URL + '/api/v1/book/' + SLUG;
const CSRF     = document.querySelector('meta[name="csrf-token"]').content;

function bookingApp() {
    return {
        step:         0,
        loading:      true,
        slotsLoading: false,
        confirming:   false,
        globalError:  '',
        detailsError: '',
        bookingError: '',
        bookingRef:   '',
        holdToken:    '',

        allServices: [],   // [{name, services:[]}]
        staffList:   [],
        slots:       [],

        today:   new Date().toISOString().split('T')[0],
        maxDate: (() => { const d = new Date(); d.setDate(d.getDate() + {{ $salon->booking_advance_days ?? 60 }}); return d.toISOString().split('T')[0]; })(),

        selected: { service: null, staff: null, date: '', slot: null },
        client:   { first_name: '', last_name: '', email: '', phone: '', notes: '', marketing_consent: false },

        async init() {
            try {
                const res  = await fetch(API + '/services');
                const data = await res.json();
                // API returns { services: { cat_id: [svc, ...] } } grouped by category_id
                const raw = data.services ?? {};
                const cats = [];
                for (const [catId, svcs] of Object.entries(raw)) {
                    const catName = svcs[0]?.category?.name ?? 'Uncategorised';
                    cats.push({ name: catName, services: svcs });
                }
                this.allServices = cats;
            } catch(e) {
                this.globalError = 'Failed to load services. Please refresh the page.';
            }
            this.loading = false;
        },

        async loadStaff() {
            try {
                const res  = await fetch(API + '/staff?service_id=' + this.selected.service.id);
                const data = await res.json();
                this.staffList = data.staff ?? [];
            } catch(e) {
                this.staffList = [];
            }
        },

        selectService(svc) {
            this.selected.service = svc;
            this.loadStaff();
            this.step = 1;
        },

        selectStaff(member) {
            this.selected.staff = member;
            this.step = 2;
            // Auto-load today's slots
            this.selected.date = this.today;
            this.loadSlots();
        },

        async loadSlots() {
            if (!this.selected.date || !this.selected.service) return;
            this.slotsLoading = true;
            this.slots = [];
            try {
                const params = new URLSearchParams({
                    service_id: this.selected.service.id,
                    date:       this.selected.date,
                });
                if (this.selected.staff) params.set('staff_id', this.selected.staff.id);
                const res  = await fetch(API + '/availability?' + params);
                const data = await res.json();
                this.slots = data.slots ?? [];
            } catch(e) {
                this.slots = [];
            }
            this.slotsLoading = false;
        },

        selectSlot(slot) {
            this.selected.slot = slot;
            this.step = 3;
        },

        goToConfirm() {
            this.detailsError = '';
            if (!this.client.first_name || !this.client.last_name) { this.detailsError = 'Please enter your full name.'; return; }
            if (!this.client.email) { this.detailsError = 'Please enter your email address.'; return; }
            if (!this.client.phone) { this.detailsError = 'Please enter your phone number.'; return; }
            this.step = 4;
        },

        async confirmBooking() {
            this.confirming   = true;
            this.bookingError = '';

            try {
                // Step 1: Hold the slot
                const holdRes = await fetch(API + '/hold', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        service_ids: [this.selected.service.id],
                        staff_id:    this.selected.staff?.id ?? null,
                        starts_at:   this.selected.date + ' ' + this.selected.slot.time + ':00',
                    }),
                });
                const holdData = await holdRes.json();
                if (!holdRes.ok) {
                    this.bookingError = holdData.message ?? 'That slot is no longer available. Please choose another time.';
                    this.confirming = false;
                    return;
                }
                this.holdToken = holdData.hold_token;

                // Step 2: Confirm with client details
                const confirmRes = await fetch(API + '/confirm', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        hold_token:         this.holdToken,
                        first_name:         this.client.first_name,
                        last_name:          this.client.last_name,
                        email:              this.client.email,
                        phone:              this.client.phone,
                        notes:              this.client.notes,
                        marketing_consent:  this.client.marketing_consent,
                    }),
                });
                const confirmData = await confirmRes.json();
                if (!confirmRes.ok) {
                    this.bookingError = confirmData.message ?? 'Booking failed. Please try again.';
                } else {
                    this.bookingRef = confirmData.reference ?? confirmData.appointment?.reference ?? '';
                    this.step = 5;
                }
            } catch(e) {
                this.bookingError = 'Something went wrong. Please try again.';
            }
            this.confirming = false;
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr + 'T12:00:00').toLocaleDateString('en-GB', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
            });
        },
    };
}
</script>
</body>
</html>
