@extends('layouts.app')
@section('title', 'Pipeline Konten')
@section('content')
@php
    use App\Models\Channel;
    use App\Models\Content;
    $prioColors = ['high' => 'text-rose-600', 'medium' => 'text-amber-600', 'low' => 'text-slate-500'];
@endphp

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold">{{ auth()->user()->isEditor() ? 'Antrian Editor' : 'Pipeline Konten' }}</h1>
    @can('create', Content::class)
        <a href="{{ route('contents.create') }}" class="btn-primary">+ Ide Baru</a>
    @endcan
</div>

{{-- Filter: status, platform channel, format --}}
<form method="GET" action="{{ route('contents.index') }}" class="card mb-5 grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-5">
    <div>
        <label class="label" for="f-status">Status</label>
        <select id="f-status" name="status" class="input">
            <option value="">Semua</option>
            @foreach (Content::STATUSES as $s)
                <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ Content::STATUS_LABELS[$s] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="f-platform">Platform</label>
        <select id="f-platform" name="platform" class="input">
            <option value="">Semua</option>
            @foreach (Channel::PLATFORMS as $p)
                <option value="{{ $p }}" @selected(($filters['platform'] ?? '') === $p)>{{ Channel::PLATFORM_LABELS[$p] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="f-format">Format</label>
        <select id="f-format" name="format_type" class="input">
            <option value="">Semua</option>
            @foreach (Content::FORMATS as $f)
                <option value="{{ $f }}" @selected(($filters['format_type'] ?? '') === $f)>{{ Content::FORMAT_LABELS[$f] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="f-channel">Channel</label>
        <select id="f-channel" name="channel_id" class="input">
            <option value="">Semua</option>
            @foreach ($channels as $ch)
                <option value="{{ $ch->id }}" @selected((string) ($filters['channel_id'] ?? '') === (string) $ch->id)>{{ $ch->platformLabel() }} — {{ $ch->handle_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex gap-2">
        <button class="btn-primary">Terapkan</button>
        <a href="{{ route('contents.index') }}" class="btn">Reset</a>
    </div>
</form>

{{-- Papan pipeline --}}
<div class="flex gap-4 overflow-x-auto pb-4">
    @foreach ($statuses as $status)
        @php $items = $contents->where('status', $status); @endphp
        <section class="w-72 shrink-0">
            <h2 class="mb-2 flex items-center justify-between text-sm font-semibold">
                <span>{{ Content::STATUS_LABELS[$status] }}</span>
                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs">{{ $items->count() }}</span>
            </h2>
            <div class="space-y-3">
                @forelse ($items as $c)
                    <a href="{{ route('contents.show', $c) }}" class="card block transition hover:border-indigo-400">
                        <p class="font-medium leading-snug">{{ $c->title }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $c->channel->platformLabel() }} · {{ $c->channel->handle_name }}</p>
                        @if ($c->user_id !== auth()->id())
                            <p class="mt-1 text-xs text-slate-500">Kreator: {{ $c->user->name }}</p>
                        @endif
                        <p class="mt-1 text-xs text-slate-500">{{ $c->formatLabel() }}</p>
                        <div class="mt-2 flex items-center justify-between text-xs">
                            <span class="font-medium {{ $prioColors[$c->priority] ?? '' }}">Prioritas: {{ $c->priority }}</span>
                            @if ($c->scheduled_date)
                                <span class="text-slate-500">📅 {{ $c->scheduled_date->format('d M Y') }}</span>
                            @endif
                        </div>
                        @if ($c->checklists_count > 0)
                            <p class="mt-2 text-xs text-slate-500">✔ {{ $c->completed_checklists_count }}/{{ $c->checklists_count }} checklist</p>
                        @endif
                    </a>
                @empty
                    <p class="rounded-md border border-dashed border-slate-300 p-4 text-center text-xs text-slate-400">Kosong</p>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
@endsection
