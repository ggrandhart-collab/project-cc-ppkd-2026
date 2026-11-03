@php
    use App\Models\Content;
    $c = $content ?? null;
@endphp
<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="label" for="title">Judul / topik *</label>
        <input id="title" name="title" value="{{ old('title', $c?->title) }}" class="input" maxlength="255" required>
    </div>

    <div>
        <label class="label" for="channel_id">Channel target *</label>
        <select id="channel_id" name="channel_id" class="input" required>
            <option value="">— pilih channel —</option>
            @foreach ($channels as $ch)
                <option value="{{ $ch->id }}" @selected((string) old('channel_id', $c?->channel_id) === (string) $ch->id)>{{ $ch->platformLabel() }} — {{ $ch->handle_name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="label" for="format_type">Format konten *</label>
        <select id="format_type" name="format_type" class="input" required>
            <option value="">— pilih format —</option>
            @foreach (Content::FORMATS as $f)
                <option value="{{ $f }}" @selected(old('format_type', $c?->format_type) === $f)>{{ Content::FORMAT_LABELS[$f] }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="label" for="priority">Prioritas *</label>
        <select id="priority" name="priority" class="input" required>
            @foreach (Content::PRIORITIES as $p)
                <option value="{{ $p }}" @selected(old('priority', $c?->priority ?? 'medium') === $p)>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="label" for="scheduled_date">Tanggal target rilis</label>
        <input id="scheduled_date" type="date" name="scheduled_date" value="{{ old('scheduled_date', $c?->scheduled_date?->format('Y-m-d')) }}" class="input">
    </div>

    <div class="sm:col-span-2">
        <label class="label" for="description">Konsep singkat</label>
        <textarea id="description" name="description" rows="3" class="input">{{ old('description', $c?->description) }}</textarea>
    </div>

    <div class="sm:col-span-2">
        <label class="label" for="script_draft">Draf naskah / alur cerita</label>
        <textarea id="script_draft" name="script_draft" rows="8" class="input font-mono">{{ old('script_draft', $c?->script_draft) }}</textarea>
    </div>

    @if ($c?->isPublished())
        <div class="sm:col-span-2">
            <label class="label" for="published_url">URL postingan * (konten sudah published)</label>
            <input id="published_url" type="url" name="published_url" value="{{ old('published_url', $c->published_url) }}" class="input" placeholder="https://...">
        </div>
        <div>
            <label class="label" for="published_date">Tanggal tayang aktual</label>
            <input id="published_date" type="date" name="published_date" value="{{ old('published_date', $c->published_date?->format('Y-m-d')) }}" class="input">
        </div>
    @endif
</div>
