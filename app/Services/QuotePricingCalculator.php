<?php

namespace App\Services;

use App\Models\PricingRateCard;
use App\Models\PricingRateItem;

class QuotePricingCalculator
{
    public function calculate(PricingRateItem $rateItem, float $quantity, ?PricingRateCard $rateCard = null, string $confidence = 'medium'): array
    {
        $rateCard ??= $rateItem->rateCard ?: PricingRateCard::activeOrCreateDefault();

        $quantity = max(round($quantity, 2), 0.01);
        $baseUnitCostPence = (int) $rateItem->base_cost_pence;
        $baseTotalPence = (int) round($baseUnitCostPence * $quantity);

        $regionalAdjustedBasePence = $this->applyPercent($baseTotalPence, (float) $rateCard->regional_adjustment_percent);
        $contingencyPercent = $confidence === 'low'
            ? max((float) $rateCard->contingency_percent, 10)
            : (float) $rateCard->contingency_percent;
        $contingencyPence = $this->percentOf($regionalAdjustedBasePence, $contingencyPercent);

        $costBeforeMarkupPence = $regionalAdjustedBasePence + $contingencyPence;
        $markupPercent = $rateItem->default_markup_percent !== null
            ? (float) $rateItem->default_markup_percent
            : ($confidence === 'low'
                ? (float) $rateCard->high_risk_markup_percent
                : (float) $rateCard->default_markup_percent);

        $markupPence = $this->percentOf($costBeforeMarkupPence, $markupPercent);
        $subtotalPence = $costBeforeMarkupPence + $markupPence;

        $vatPercent = $rateItem->vat_percent !== null
            ? (float) $rateItem->vat_percent
            : (float) $rateCard->vat_percent;
        $vatPence = $this->percentOf($subtotalPence, $vatPercent);

        return [
            'base_unit_cost_pence' => $baseUnitCostPence,
            'base_total_pence' => $baseTotalPence,
            'markup_percent' => $markupPercent,
            'contingency_percent' => $contingencyPercent,
            'vat_percent' => $vatPercent,
            'contingency_pence' => $contingencyPence,
            'markup_pence' => $markupPence,
            'subtotal_pence' => $subtotalPence,
            'vat_pence' => $vatPence,
            'total_pence' => $subtotalPence + $vatPence,
        ];
    }

    private function percentOf(int $amountPence, float $percent): int
    {
        return (int) round($amountPence * ($percent / 100));
    }

    private function applyPercent(int $amountPence, float $percent): int
    {
        return $amountPence + $this->percentOf($amountPence, $percent);
    }
}
