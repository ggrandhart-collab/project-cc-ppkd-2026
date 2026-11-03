<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    public const UPDATED_AT = null; // tabel hanya punya created_at

    public const PLATFORMS = ['youtube', 'tiktok', 'instagram', 'linkedin'];

    public const PLATFORM_LABELS = [
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
    ];

    protected $fillable = ['user_id', 'platform', 'handle_name'];

    public function platformLabel(): string
    {
        return self::PLATFORM_LABELS[$this->platform] ?? ucfirst($this->platform);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }
}
