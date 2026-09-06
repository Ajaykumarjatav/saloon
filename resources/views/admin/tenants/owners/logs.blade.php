@extends('layouts.admin')
@section('title', $account->name.' — logs')
@section('page-title', 'Tenant logs')

@section('content')
<div class="space-y-5 max-w-5xl">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('admin.tenants') }}" class="text-xs text-gray-500 hover:text-gray-300">← All tenants</a>
            <h1 class="text-xl font-bold text-gray-100 mt-1">{{ $account->name }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $account->email }} · every stored activity and audit event from signup to now · times in {{ \App\Support\SalonTime::defaultTimezone() }}</p>
        </div>
        <a href="{{ route('admin.tenants.owners.show', $account->id) }}"
           class="px-4 py-2 text-sm font-medium rounded-xl border border-gray-700 text-gray-300 hover:bg-gray-800">Account</a>
    </div>

    @php
        $grouped = $logs->getCollection()->groupBy(function ($row) {
            $at = $row->occurred_at ?? null;
            if (! $at) {
                return 'unknown';
            }
            return \App\Support\SalonTime::toDisplay($at)->toDateString();
        });
    @endphp

    @forelse($grouped as $date => $rows)
        <section class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
            <header class="px-4 py-2 border-b border-gray-800 text-sm font-semibold text-gray-200 flex justify-between">
                <span>{{ $date === 'unknown' ? 'Unknown date' : \Carbon\Carbon::parse($date)->format('l, j F Y') }}</span>
                <span class="text-xs text-gray-500">{{ $rows->count() }}</span>
            </header>
            <ul class="divide-y divide-gray-800/80">
                @foreach($rows as $row)
                <li class="px-4 py-3 text-sm flex gap-3">
                    <span class="w-12 shrink-0 font-mono text-xs text-gray-500">{{ $row->occurred_at ? \App\Support\SalonTime::toDisplay($row->occurred_at)->format('H:i') : '—' }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-gray-200">{{ $row->summary ?: $row->kind }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $row->user_name ?: 'System' }}
                            @if($row->user_email) · {{ $row->user_email }} @endif
                            · {{ $row->source === 'audit' ? 'audit' : 'activity' }}
                            @if($row->kind) · {{ $row->kind }} @endif
                            @if($row->ip_address) · {{ $row->ip_address }} @endif
                        </p>
                    </div>
                </li>
                @endforeach
            </ul>
        </section>
    @empty
        <p class="text-center text-gray-500 py-16">No stored logs for this tenant yet.</p>
    @endforelse

    <div>{{ $logs->links() }}</div>
</div>
@endsection
