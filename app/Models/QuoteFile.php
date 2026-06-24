<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteFile extends Model
{
    protected $fillable = [
        'quote_id',
        'uploaded_by_user_id',
        'type',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'caption',
        'room_or_area',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function getUrlAttribute(): string
    {
        return asset($this->path);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}