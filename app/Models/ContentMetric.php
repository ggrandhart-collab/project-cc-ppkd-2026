<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentMetric extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'content_id', 'recorded_at',
        'views_count', 'likes_count', 'comments_count', 'shares_count',
    ];

    protected function casts(): array
    {
        return ['recorded_at' => 'date'];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
