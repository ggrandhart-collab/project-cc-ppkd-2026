@php
    $colors = [
        'idea' => 'bg-slate-100 text-slate-700',
        'scripting' => 'bg-sky-100 text-sky-700',
        'recording' => 'bg-amber-100 text-amber-800',
        'editing' => 'bg-violet-100 text-violet-700',
        'ready' => 'bg-emerald-100 text-emerald-700',
        'published' => 'bg-indigo-100 text-indigo-700',
    ];
@endphp
<span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $colors[$status] ?? 'bg-slate-100' }}">
    {{ \App\Models\Content::STATUS_LABELS[$status] ?? $status }}
</span>
