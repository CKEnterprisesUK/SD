<?php

namespace App\Services;

use App\Models\PricingJobTemplate;
use App\Models\PricingRateCard;
use App\Models\Quote;

class QuoteAiPricingContextBuilder
{
    public function build(Quote $quote, ?string $pricingHint = null): array
    {
        $rateCard = PricingRateCard::activeOrCreateDefault()->load('activeItems');

        $templates = PricingJobTemplate::where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return [
            'instruction' => 'Create a draft building estimate with low, likely and high pricing ranges. Use pricing guidance, project context and professional estimating judgement. Do not force a rate-card match.',
            'quote' => [
                'quote_number' => $quote->quote_number,
                'title' => $quote->title,
                'status' => $quote->status,
                'site_address' => $quote->site_address,
                'summary' => $quote->summary,
                'internal_notes' => $quote->internal_notes,
                'pricing_hint_from_user' => $pricingHint,
            ],
            'customer' => [
                'name' => $quote->customer?->display_name,
                'address' => $quote->customer?->address,
                'contacts' => $quote->customer?->contacts?->map(fn ($contact) => [
                    'name' => $contact->name,
                    'role' => $contact->role,
                    'is_primary' => $contact->is_primary,
                ])->values()->all() ?? [],
            ],
            'survey_notes' => $quote->notes->map(fn ($note) => [
                'type' => $note->type,
                'room_or_area' => $note->room_or_area,
                'body' => $note->body,
                'created_at' => $note->created_at?->toDateTimeString(),
            ])->values()->all(),
            'photos_and_files' => $quote->files->map(fn ($file) => [
                'type' => $file->type,
                'room_or_area' => $file->room_or_area,
                'caption' => $file->caption,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
            ])->values()->all(),
            'existing_line_items' => $quote->lineItems->map(fn ($lineItem) => [
                'source' => $lineItem->source,
                'type' => $lineItem->type,
                'description' => $lineItem->description,
                'quantity' => $lineItem->quantity,
                'unit' => $lineItem->unit,
                'unit_amount_pounds' => round($lineItem->unit_amount_pence / 100, 2),
                'total_pounds' => round($lineItem->total_pence / 100, 2),
                'is_optional' => $lineItem->is_optional,
            ])->values()->all(),
            'pricing_policy' => [
                'name' => $rateCard->name,
                'default_markup_percent' => (float) $rateCard->default_markup_percent,
                'high_risk_markup_percent' => (float) $rateCard->high_risk_markup_percent,
                'contingency_percent' => (float) $rateCard->contingency_percent,
                'vat_percent' => (float) $rateCard->vat_percent,
                'regional_adjustment_percent' => (float) $rateCard->regional_adjustment_percent,
                'minimum_job_charge_pounds' => round($rateCard->minimum_job_charge_pence / 100, 2),
                'important' => 'AI may estimate ex-VAT prices, but every estimated item must include low, likely and high values, confidence, pricing basis and warnings where needed.',
            ],
            'pricing_guidance' => $rateCard->activeItems->map(fn ($item) => $item->toAiContext())->values()->all(),
            'job_templates' => $templates->map(fn ($template) => $template->toAiContext())->values()->all(),
            'output_contract' => [
                'Estimate prices in GBP excluding VAT at line-item level.',
                'Provide low, likely and high ex-VAT estimates for every item.',
                'Use pricing guidance where relevant, but do not force a match.',
                'Use professional estimating judgement where guidance is incomplete.',
                'Show uncertainty clearly with confidence, warnings and missing information.',
                'Keep customer descriptions clean and do not include internal reasoning in them.',
                'Do not mention AI, ChatGPT or language models in customer-facing text.',
            ],
        ];
    }
}