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
        'path',
        'original_name',
        'mime_type',
        'size',
        'room_or_area',
        'caption',
        'include_in_quote_pack',
        'quote_pack_caption',
        'quote_pack_sort_order',
    ];

    protected $casts = [
        'include_in_quote_pack' => 'boolean',
        'quote_pack_sort_order' => 'integer',
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

    public function getQuotePackCaptionDisplayAttribute(): ?string
    {
        return $this->quote_pack_caption ?: $this->caption;
    }
}