<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteAiGeneration extends Model
{
    protected $fillable = [
        'quote_id',
        'created_by_user_id',
        'provider',
        'model',
        'input_payload',
        'output_payload',
        'status',
        'error_message',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}