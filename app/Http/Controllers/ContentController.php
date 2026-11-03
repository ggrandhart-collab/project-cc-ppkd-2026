<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContentRequest;
use App\Models\Channel;
use App\Models\Content;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(Content::STATUSES)],
            'platform' => ['nullable', Rule::in(Channel::PLATFORMS)],
            'format_type' => ['nullable', Rule::in(Content::FORMATS)],
            'channel_id' => ['nullable', 'integer'],
        ]);

        $contents = Content::query()
            ->visibleTo($user)
            ->filter($filters)
            ->with(['channel', 'user:id,name'])
            ->withCount([
                'checklists',
                'checklists as completed_checklists_count' => fn ($q) => $q->where('is_completed', true),
            ])
            ->orderByRaw("case priority when 'high' then 0 when 'medium' then 1 else 2 end")
            ->orderBy('scheduled_date')
            ->get();

        // Editor melihat channel dari konten yang bisa ia akses; kreator hanya channel miliknya.
        $channels = Channel::query()
            ->when($user->isEditor(), fn ($q) => $q->whereIn('id', Content::visibleTo($user)->select('channel_id')))
            ->when($user->isCreator(), fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('handle_name')
            ->get();

        // Papan editor hanya menampilkan kolom yang relevan baginya.
        $defaultStatuses = $user->isEditor() ? ['editing', 'recording', 'ready', 'published'] : Content::STATUSES;

        return view('contents.index', [
            'contents' => $contents,
            'channels' => $channels,
            'filters' => $filters,
            'statuses' => ! empty($filters['status']) ? [$filters['status']] : $defaultStatuses,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        Gate::authorize('create', Content::class);

        $channels = $request->user()->channels()->orderBy('handle_name')->get();

        if ($channels->isEmpty()) {
            return redirect()->route('channels.index')
                ->with('error', 'Tambahkan minimal satu channel sebelum membuat konten.');
        }

        return view('contents.create', ['channels' => $channels]);
    }

    public function store(StoreContentRequest $request): RedirectResponse
    {
        Gate::authorize('create', Content::class);

        $content = $request->user()->contents()->create([
            ...$request->safe()->except(['published_url', 'published_date']),
            'status' => 'idea', // konten baru selalu dimulai dari idea
        ]);

        return redirect()->route('contents.show', $content)->with('success', 'Ide konten berhasil dibuat.');
    }

    public function show(Content $content): View
    {
        Gate::authorize('view', $content);

        $content->load([
            'channel',
            'user:id,name',
            'reviews' => fn ($q) => $q->with('reviewer:id,name')->orderByDesc('id'),
            'checklists' => fn ($q) => $q->orderBy('id'),
            'metrics' => fn ($q) => $q->orderByDesc('recorded_at')->orderByDesc('id'),
        ]);

        return view('contents.show', ['content' => $content]);
    }

    public function edit(Content $content): View
    {
        Gate::authorize('update', $content);

        return view('contents.edit', [
            'content' => $content,
            'channels' => Channel::where('user_id', $content->user_id)->orderBy('handle_name')->get(),
        ]);
    }

    public function update(StoreContentRequest $request, Content $content): RedirectResponse
    {
        $data = $request->validated();

        // published_url / published_date hanya boleh diubah lewat form jika konten sudah published.
        if (! $content->isPublished()) {
            unset($data['published_url'], $data['published_date']);
        }

        $content->update($data);

        return redirect()->route('contents.show', $content)->with('success', 'Konten berhasil diperbarui.');
    }

    public function destroy(Content $content): RedirectResponse
    {
        Gate::authorize('delete', $content);

        // Checklist & metrik ikut terhapus lewat ON DELETE CASCADE di database.
        $content->delete();

        return redirect()->route('contents.index')->with('success', 'Konten beserta checklist dan metriknya dihapus.');
    }

    /**
     * Transisi status satu tahap sesuai peran (lihat Content::allowedTransitionsFor):
     * kreator: idea → scripting → recording → editing, lalu ready → published.
     * Dari "editing" ke "ready"/"recording" hanya lewat review editor (ReviewController).
     * Ke "published" wajib published_url yang valid.
     */
    public function transition(Request $request, Content $content): RedirectResponse
    {
        Gate::authorize('update', $content);

        $request->validate([
            'status' => ['required', Rule::in(Content::STATUSES)],
        ], [
            'status.required' => 'Status tujuan wajib diisi.',
            'status.in' => 'Status tujuan tidak valid.',
        ]);

        $target = $request->input('status');
        $user = $request->user();

        if (! in_array($target, $content->allowedTransitionsFor($user), true)) {
            throw ValidationException::withMessages(['status' => $this->transitionError($content, $target, $user)]);
        }

        $attributes = ['status' => $target];

        if ($target === 'published') {
            $url = trim((string) ($request->filled('published_url')
                ? $request->input('published_url')
                : $content->published_url));

            Validator::make(['published_url' => $url], [
                'published_url' => ['required', 'url:http,https', 'max:255'],
            ], [
                'published_url.required' => 'Konten tidak bisa dipublikasikan: published_url wajib diisi.',
                'published_url.url' => 'Konten tidak bisa dipublikasikan: published_url harus berupa tautan http(s) yang valid.',
                'published_url.max' => 'published_url maksimal 255 karakter.',
            ])->validate();

            $attributes['published_url'] = $url;
            $attributes['published_date'] = $content->published_date ?? now()->toDateString();
        }

        $content->update($attributes);

        return back()->with('success', 'Status diubah menjadi "'.Content::STATUS_LABELS[$target].'".');
    }

    private function transitionError(Content $content, string $target, User $user): string
    {
        if ($content->status === 'editing' && ! $user->isAdmin()) {
            return 'Konten sedang di tahap editing dan hanya bisa diputuskan oleh editor (setujui atau minta revisi).';
        }

        $adjacent = in_array($target, array_filter([$content->nextStatus(), $content->previousStatus()]), true);

        return $adjacent
            ? "Anda tidak bisa memindahkan konten dari \"{$content->status}\" ke \"{$target}\"."
            : "Status tidak bisa langsung berpindah dari \"{$content->status}\" ke \"{$target}\". Pindahkan satu tahap saja.";
    }
}
