<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingJobTemplate extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'typical_scope',
        'typical_labour',
        'typical_materials',
        'typical_plant',
        'typical_waste',
        'default_assumptions',
        'default_exclusions',
        'risk_notes',
        'required_information',
        'suggested_rate_item_codes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function toAiContext(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'typical_scope' => $this->typical_scope,
            'typical_labour' => $this->typical_labour,
            'typical_materials' => $this->typical_materials,
            'typical_plant' => $this->typical_plant,
            'typical_waste' => $this->typical_waste,
            'default_assumptions' => $this->default_assumptions,
            'default_exclusions' => $this->default_exclusions,
            'risk_notes' => $this->risk_notes,
            'required_information' => $this->required_information,
            'suggested_rate_item_codes' => $this->suggested_rate_item_codes,
        ];
    }
}
