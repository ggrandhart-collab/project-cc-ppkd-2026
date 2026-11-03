<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentChecklist extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['content_id', 'task_name', 'is_completed'];

    protected function casts(): array
    {
        return ['is_completed' => 'boolean'];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
