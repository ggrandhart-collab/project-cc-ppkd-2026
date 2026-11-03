<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Content extends Model
{
    /** Urutan tahapan siklus hidup konten. */
    public const STATUSES = ['idea', 'scripting', 'recording', 'editing', 'ready', 'published'];

    public const STATUS_LABELS = [
        'idea' => 'Idea Backlog',
        'scripting' => 'Scripting / Planning',
        'recording' => 'In Production / Recording',
        'editing' => 'Editing & Review',
        'ready' => 'Ready',
        'published' => 'Published',
    ];

    public const PRIORITIES = ['low', 'medium', 'high'];

    public const FORMATS = ['short_video', 'long_video', 'carousel', 'single_post'];

    public const FORMAT_LABELS = [
        'short_video' => 'Short Video (Shorts/Reels/TikTok)',
        'long_video' => 'Long-form Video',
        'carousel' => 'Carousel',
        'single_post' => 'Single Post',
    ];

    protected $fillable = [
        'user_id', 'channel_id', 'title', 'description', 'script_draft',
        'format_type', 'status', 'priority',
        'scheduled_date', 'published_date', 'published_url',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'published_date' => 'date',
        ];
    }

    // ---- Relasi ----
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(ContentChecklist::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ContentMetric::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ContentReview::class);
    }

    // ---- Helper status ----
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function nextStatus(): ?string
    {
        $i = array_search($this->status, self::STATUSES, true);

        return $i === false ? null : (self::STATUSES[$i + 1] ?? null);
    }

    public function previousStatus(): ?string
    {
        $i = array_search($this->status, self::STATUSES, true);

        return ($i === false || $i === 0) ? null : self::STATUSES[$i - 1];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function formatLabel(): string
    {
        return self::FORMAT_LABELS[$this->format_type] ?? $this->format_type;
    }

    /**
     * Tujuan transisi status yang boleh dilakukan $user lewat tombol pindah status.
     *
     * Pembagian peran:
     *  - Kreator (pemilik): idea → scripting → recording → editing, lalu ready → published.
     *    Saat konten di "editing", kreator tidak bisa memindahkan apa pun (giliran editor).
     *  - Editor: tidak memakai transisi biasa; ia memutuskan lewat review
     *    (editing → ready bila disetujui, editing → recording bila minta revisi).
     *  - Admin: boleh satu tahap maju/mundur di semua tahap.
     */
    public function allowedTransitionsFor(User $user): array
    {
        if ($user->isAdmin()) {
            return array_values(array_filter([$this->previousStatus(), $this->nextStatus()]));
        }

        if ($user->id !== $this->user_id) {
            return [];
        }

        return match ($this->status) {
            'idea' => ['scripting'],
            'scripting' => ['idea', 'recording'],
            'recording' => ['scripting', 'editing'],
            'editing' => [],
            'ready' => ['published'],
            'published' => ['ready'],
            default => [],
        };
    }

    // ---- Scope ----
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        // Editor melihat konten yang sedang menunggu review + konten yang pernah ia review.
        if ($user->isEditor()) {
            return $query->where(function (Builder $q) use ($user) {
                $q->where('contents.status', 'editing')
                    ->orWhereHas('reviews', fn ($r) => $r->where('reviewer_id', $user->id));
            });
        }

        return $query->where('contents.user_id', $user->id);
    }

    /** Filter: status, platform (milik channel), format_type, channel_id. */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['format_type'] ?? null, fn ($q, $v) => $q->where('format_type', $v))
            ->when($filters['channel_id'] ?? null, fn ($q, $v) => $q->where('channel_id', $v))
            ->when($filters['platform'] ?? null, fn ($q, $v) => $q->whereHas('channel', fn ($c) => $c->where('platform', $v)));
    }
}
