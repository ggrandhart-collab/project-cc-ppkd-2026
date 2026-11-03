@extends('layouts.app')
@section('title', $content->title)
@section('content')
@php
    use App\Models\Content;

    $statuses = Content::STATUSES;
    $currentIdx = array_search($content->status, $statuses, true);
    $latest = $content->metrics->first();

    // Transisi yang boleh dilakukan user ini (beda per peran), dipisah mundur / maju.
    $allowed = $content->allowedTransitionsFor(auth()->user());
    $idx = fn ($s) => array_search($s, $statuses, true);
    $backTargets = array_values(array_filter($allowed, fn ($s) => $idx($s) < $currentIdx));
    $forwardTargets = array_values(array_filter($allowed, fn ($s) => $idx($s) > $currentIdx));

    $lastReview = $content->reviews->first();
    $isOwner = auth()->id() === $content->user_id;

    $checklistItems = $content->checklists->map(fn ($i) => [
        'id' => $i->id,
        'task_name' => $i->task_name,
        'is_completed' => (bool) $i->is_completed,
    ])->values();

    $checklistUrls = [
        'store' => route('contents.checklists.store', $content),
        'toggle' => route('checklists.toggle', ['checklist' => '__ID__']),
        'destroy' => route('checklists.destroy', ['checklist' => '__ID__']),
    ];
@endphp

{{-- Header --}}
<div class="mb-4 flex flex-wrap items-start justify-between gap-3">
    <div>
        <a href="{{ route('contents.index') }}" class="text-sm text-indigo-600 hover:underline">← Kembali ke pipeline</a>
        <h1 class="mt-1 text-2xl font-semibold">{{ $content->title }}</h1>
        <p class="text-sm text-slate-500">
            @unless ($isOwner) Kreator: {{ $content->user->name }} · @endunless
            {{ $content->channel->platformLabel() }} · {{ $content->channel->handle_name }} ·
            {{ $content->formatLabel() }} · Prioritas {{ $content->priority }}
            @if ($content->scheduled_date) · Rilis target {{ $content->scheduled_date->format('d M Y') }} @endif
        </p>
    </div>
    <div class="flex gap-2">
        @can('update', $content)
            <a href="{{ route('contents.edit', $content) }}" class="btn">Edit</a>
        @endcan
        @can('delete', $content)
            <form method="POST" action="{{ route('contents.destroy', $content) }}"
                  onsubmit="return confirm('Hapus konten ini beserta checklist dan metriknya?')">
                @csrf @method('DELETE')
                <button class="btn-danger">Hapus</button>
            </form>
        @endcan
    </div>
</div>

{{-- Stepper status --}}
<ol class="mb-3 flex flex-wrap gap-2">
    @foreach ($statuses as $i => $s)
        <li class="rounded-full px-3 py-1 text-xs font-medium
            {{ $i === $currentIdx ? 'bg-indigo-600 text-white' : ($i < $currentIdx ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500') }}">
            {{ $i + 1 }}. {{ Content::STATUS_LABELS[$s] }}
        </li>
    @endforeach
</ol>

{{-- Banner alur kerja kreator ↔ editor --}}
@if ($lastReview && ! $lastReview->isApproved() && $idx($content->status) < $idx('editing'))
    <div class="mb-4 rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        <p class="font-semibold">Editor meminta revisi</p>
        <p class="mt-1 whitespace-pre-line">{{ $lastReview->note }}</p>
        <p class="mt-2 text-xs text-amber-700">
            — {{ $lastReview->reviewer->name }}, {{ $lastReview->created_at->format('d M Y H:i') }}.
            @if ($isOwner) Perbaiki, lalu kirim lagi ke editor. @endif
        </p>
    </div>
@elseif ($content->status === 'editing' && $isOwner)
    <div class="mb-4 rounded-md border border-violet-200 bg-violet-50 p-4 text-sm text-violet-900">
        Konten sedang direview editor. Kamu akan diberi tahu lewat perubahan status: <strong>Ready</strong> bila disetujui,
        atau kembali ke <strong>Recording</strong> dengan catatan revisi.
    </div>
@elseif ($content->status === 'ready' && $isOwner)
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
        Disetujui editor{{ $lastReview?->isApproved() ? ' ('.$lastReview->reviewer->name.')' : '' }}.
        Isi URL postingan lalu publikasikan.
        @if ($lastReview?->isApproved() && $lastReview->note)
            <span class="mt-1 block whitespace-pre-line text-emerald-800">Catatan editor: {{ $lastReview->note }}</span>
        @endif
    </div>
@endif

{{-- Transisi status --}}
<div class="card mb-6 flex flex-wrap items-end gap-3">
    <div class="mr-2">
        <p class="text-xs text-slate-500">Status saat ini</p>
        @include('partials.status-badge', ['status' => $content->status])
    </div>

    @foreach ($backTargets as $target)
        <form method="POST" action="{{ route('contents.transition', $content) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="{{ $target }}">
            <button class="btn">← Kembali ke {{ Content::STATUS_LABELS[$target] }}</button>
        </form>
    @endforeach

    @foreach ($forwardTargets as $target)
        <form method="POST" action="{{ route('contents.transition', $content) }}" class="flex flex-wrap items-end gap-2">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="{{ $target }}">
            @if ($target === 'published')
                <div>
                    <label class="label" for="published_url">URL postingan (wajib)</label>
                    <input id="published_url" name="published_url" value="{{ old('published_url', $content->published_url) }}"
                           class="input w-72" placeholder="https://www.youtube.com/watch?v=...">
                </div>
            @endif
            <button class="btn-primary">
                {{ $target === 'editing' ? 'Kirim ke Editor untuk review →' : 'Pindah ke '.Content::STATUS_LABELS[$target].' →' }}
            </button>
        </form>
    @endforeach

    @if ($allowed === [] && ! auth()->user()->can('review', $content))
        <p class="text-sm text-slate-500">
            @if ($content->status === 'editing') Menunggu keputusan editor.
            @else Tidak ada aksi status yang tersedia untuk peranmu pada tahap ini. @endif
        </p>
    @endif

    @if ($content->isPublished() && $content->published_url)
        <a href="{{ $content->published_url }}" target="_blank" rel="noopener noreferrer"
           class="ml-auto text-sm text-indigo-600 underline">Buka postingan ↗</a>
    @endif
</div>

{{-- Panel review editor --}}
@can('review', $content)
    <form method="POST" action="{{ route('contents.review', $content) }}" class="card mb-6 space-y-3 border-violet-300">
        @csrf
        <h2 class="font-semibold">Review editor</h2>
        <p class="text-sm text-slate-500">
            Setujui agar konten masuk <strong>Ready</strong>, atau minta revisi agar kembali ke kreator
            (<strong>Recording</strong>) — catatan wajib diisi untuk revisi.
        </p>
        <div>
            <label class="label" for="note">Catatan untuk kreator</label>
            <textarea id="note" name="note" rows="4" maxlength="2000" class="input"
                      placeholder="Mis. Audio di menit 2 masih berisik, tolong rekam ulang bagian intro.">{{ old('note') }}</textarea>
            @error('note') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('decision') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="flex flex-wrap gap-2">
            <button name="decision" value="approved" class="btn-primary">✔ Setujui → Ready</button>
            <button name="decision" value="revision_requested" class="btn-danger">↩ Minta revisi</button>
        </div>
    </form>
@endcan

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Kolom kiri: konsep & naskah --}}
    <div class="space-y-6 lg:col-span-2">
        <section class="card">
            <h2 class="mb-2 font-semibold">Konsep singkat</h2>
            <p class="whitespace-pre-line text-sm">{{ $content->description ?: 'Belum diisi.' }}</p>
        </section>

        <section class="card">
            <h2 class="mb-2 font-semibold">Draf naskah</h2>
            <pre class="whitespace-pre-wrap font-sans text-sm">{{ $content->script_draft ?: 'Belum ada naskah.' }}</pre>
        </section>

        {{-- Riwayat review editor --}}
        @if ($content->reviews->isNotEmpty())
            <section class="card">
                <h2 class="mb-3 font-semibold">Riwayat review</h2>
                <ul class="space-y-3 text-sm">
                    @foreach ($content->reviews as $r)
                        <li class="border-b pb-3 last:border-0 last:pb-0">
                            <p>
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $r->isApproved() ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $r->isApproved() ? 'Disetujui' : 'Revisi diminta' }}
                                </span>
                                <span class="ml-1 text-xs text-slate-500">{{ $r->reviewer->name }} · {{ $r->created_at->format('d M Y H:i') }}</span>
                            </p>
                            @if ($r->note)<p class="mt-1 whitespace-pre-line">{{ $r->note }}</p>@endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Metrik performa --}}
        <section class="card">
            <h2 class="mb-3 font-semibold">Metrik performa</h2>

            @if (! $content->isPublished())
                <p class="rounded-md bg-slate-50 p-3 text-sm text-slate-500">
                    Pencatatan metrik terbuka setelah konten berstatus <strong>Published</strong>.
                </p>
            @else
                @if ($latest)
                    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach (['views_count' => 'Views', 'likes_count' => 'Likes', 'comments_count' => 'Comments', 'shares_count' => 'Shares'] as $col => $label)
                            <div class="rounded-md bg-slate-50 p-3 text-center">
                                <p class="text-xs text-slate-500">{{ $label }} (terakhir)</p>
                                <p class="text-xl font-semibold">{{ number_format($latest->$col) }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @can('update', $content)
                <form method="POST" action="{{ route('contents.metrics.store', $content) }}" class="mb-4 grid items-end gap-3 sm:grid-cols-5">
                    @csrf
                    <div>
                        <label class="label" for="recorded_at">Tanggal</label>
                        <input id="recorded_at" type="date" name="recorded_at" value="{{ old('recorded_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="input">
                    </div>
                    @foreach (['views_count' => 'Views', 'likes_count' => 'Likes', 'comments_count' => 'Comments', 'shares_count' => 'Shares'] as $col => $label)
                        <div>
                            <label class="label" for="{{ $col }}">{{ $label }}</label>
                            <input id="{{ $col }}" type="number" min="0" name="{{ $col }}" value="{{ old($col, 0) }}" class="input">
                        </div>
                    @endforeach
                    <div class="sm:col-span-5"><button class="btn-primary">Catat metrik</button></div>
                </form>
                @endcan

                @if ($content->metrics->isEmpty())
                    <p class="text-sm text-slate-500">Belum ada riwayat metrik.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b text-slate-500">
                                <tr><th class="py-2">Tanggal</th><th>Views</th><th>Likes</th><th>Comments</th><th>Shares</th></tr>
                            </thead>
                            <tbody>
                            @foreach ($content->metrics as $m)
                                <tr class="border-b last:border-0">
                                    <td class="py-2">{{ $m->recorded_at->format('d M Y') }}</td>
                                    <td>{{ number_format($m->views_count) }}</td>
                                    <td>{{ number_format($m->likes_count) }}</td>
                                    <td>{{ number_format($m->comments_count) }}</td>
                                    <td>{{ number_format($m->shares_count) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </section>
    </div>

    {{-- Kolom kanan: checklist (AJAX via fetch + Alpine) --}}
    <aside class="card self-start" x-data="checklist(@js($checklistItems), @js($checklistUrls), @js(auth()->user()->can('update', $content)))" x-cloak>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-semibold">Checklist produksi</h2>
            <span class="text-xs text-slate-500" x-text="done + '/' + items.length + ' selesai'"></span>
        </div>

        <ul class="mb-3 space-y-2">
            <template x-for="item in items" :key="item.id">
                <li class="flex items-start gap-2 text-sm">
                    <input type="checkbox" class="mt-1" :checked="item.is_completed" :disabled="!canEdit" @change="toggle(item)">
                    <span class="flex-1" :class="item.is_completed ? 'text-slate-400 line-through' : ''" x-text="item.task_name"></span>
                    <button type="button" x-show="canEdit" class="text-xs text-rose-600 hover:underline" @click="remove(item)">hapus</button>
                </li>
            </template>
            <li x-show="items.length === 0" class="text-sm text-slate-500">Belum ada item checklist.</li>
        </ul>

        <form x-show="canEdit" @submit.prevent="add" class="space-y-2">
            <input type="text" x-model="newTask" class="input" placeholder="Mis. Rekam B-Roll" maxlength="255">
            <p x-show="error" x-text="error" class="text-xs text-rose-600"></p>
            <button class="btn-primary" :disabled="loading">Tambah item</button>
        </form>
    </aside>
</div>
@endsection

@push('scripts')
<script>
    function checklist(items, urls, canEdit = true) {
        return {
            items, urls, canEdit, newTask: '', error: '', loading: false,

            get done() { return this.items.filter(i => i.is_completed).length; },

            headers() {
                return {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                };
            },

            async add() {
                this.error = '';
                if (!this.newTask.trim()) { this.error = 'Nama tugas wajib diisi.'; return; }
                this.loading = true;
                try {
                    const res = await fetch(this.urls.store, {
                        method: 'POST', headers: this.headers(),
                        body: JSON.stringify({ task_name: this.newTask }),
                    });
                    const data = await res.json();
                    if (!res.ok) { this.error = data.errors?.task_name?.[0] ?? data.message ?? 'Gagal menambah item.'; return; }
                    this.items.push({ id: data.id, task_name: data.task_name, is_completed: !!data.is_completed });
                    this.newTask = '';
                } catch (e) { this.error = 'Koneksi bermasalah, coba lagi.'; }
                finally { this.loading = false; }
            },

            async toggle(item) {
                const res = await fetch(this.urls.toggle.replace('__ID__', item.id), { method: 'PATCH', headers: this.headers() });
                if (res.ok) { item.is_completed = (await res.json()).is_completed; }
                else { this.error = 'Gagal mengubah status item.'; }
            },

            async remove(item) {
                if (!confirm('Hapus item checklist ini?')) return;
                const res = await fetch(this.urls.destroy.replace('__ID__', item.id), { method: 'DELETE', headers: this.headers() });
                if (res.ok) { this.items = this.items.filter(i => i.id !== item.id); }
                else { this.error = 'Gagal menghapus item.'; }
            },
        };
    }
</script>
@endpush
