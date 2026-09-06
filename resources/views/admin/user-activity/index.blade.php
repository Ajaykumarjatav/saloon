@extends('layouts.admin')
@section('title', 'User activity')
@section('page-title', 'User activity')

@section('content')
<div class="space-y-5 max-w-5xl">
    <div>
        <h1 class="text-xl font-bold text-gray-100">User activity</h1>
        <p class="text-sm text-gray-500 mt-1">App usage by every user — retained {{ $retentionDays }} days. Default: last 30 days.</p>
    </div>

    <form method="GET" class="bg-gray-900 border border-gray-800 rounded-2xl p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-[13rem] shrink-0">
                <label class="block text-xs text-gray-400 mb-1.5">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected((string) request('user_id') === (string) $u->id)>{{ $u->name }} — {{ $u->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[10rem]">
                <label class="block text-xs text-gray-400 mb-1.5">Search</label>
                <input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Label / email…">
            </div>
            <div class="w-full sm:w-[15rem] shrink-0">
                <label class="block text-xs text-gray-400 mb-1.5">Date range</label>
                <x-date-range-picker :from-value="$from" :to-value="$to" class="w-full" />
            </div>
            <div class="flex items-center gap-2 shrink-0 pb-px">
                <button type="submit" class="min-h-[2.5rem] px-4 text-sm font-semibold rounded-xl bg-velour-600 hover:bg-velour-700 text-white">Filter</button>
                <a href="{{ route('admin.user-activity.index') }}" class="min-h-[2.5rem] inline-flex items-center px-3 text-sm text-gray-400 hover:text-gray-200">Reset</a>
            </div>
        </div>
    </form>

    @forelse($grouped as $date => $rows)
        <section class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
            <header class="px-4 py-2 border-b border-gray-800 text-sm font-semibold text-gray-200 flex justify-between">
                <span>{{ \Carbon\Carbon::parse($date)->format('l, j F Y') }}</span>
                <span class="text-xs text-gray-500">{{ $rows->count() }}</span>
            </header>
            <ul class="divide-y divide-gray-800/80">
                @foreach($rows as $row)
                <li class="px-4 py-3 text-sm flex gap-3">
                    <span class="w-12 font-mono text-xs text-gray-500">{{ $row->occurred_at->format('H:i') }}</span>
                    <div class="min-w-0">
                        <p class="text-gray-200">{{ $row->label }}</p>
                        <p class="text-xs text-gray-500">{{ $row->user_name }} · {{ $row->user_email }}
                            @if($row->salon) · {{ $row->salon->name }} @endif
                            · {{ $row->action === 'action.write' ? 'change' : 'view' }}
                        </p>
                    </div>
                </li>
                @endforeach
            </ul>
        </section>
    @empty
        <p class="text-center text-gray-500 py-16">No usage activity in this range.</p>
    @endforelse

    <div>{{ $logs->links() }}</div>
</div>
@endsection
