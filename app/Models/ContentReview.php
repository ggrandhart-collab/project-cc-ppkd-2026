<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentReview extends Model
{
    public const UPDATED_AT = null; // tabel hanya punya created_at

    public const APPROVED = 'approved';

    public const REVISION_REQUESTED = 'revision_requested';

    public const DECISIONS = [self::APPROVED, self::REVISION_REQUESTED];

    protected $fillable = ['content_id', 'reviewer_id', 'decision', 'note'];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function isApproved(): bool
    {
        return $this->decision === self::APPROVED;
    }
}
