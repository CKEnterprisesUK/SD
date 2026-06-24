<?php

namespace Database\Seeders;

use App\Models\PricingJobTemplate;
use App\Models\PricingRateCard;
use App\Models\PricingRateItem;
use Illuminate\Database\Seeder;

class PricingDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $rateCard = PricingRateCard::activeOrCreateDefault();

        $items = [
            ['labour', 'labour_day_general', 'General labour day rate', 'General labour for agreed works', 'day', 22000, 'labourer, general labour, strip out, helper', 'Use for non-specialist labour and support work.'],
            ['labour', 'labour_day_skilled', 'Skilled tradesperson day rate', 'Skilled labour for agreed works', 'day', 30000, 'builder, carpenter, plumber, tiler, roofer, skilled trade', 'Use for qualified or skilled trade work.'],
            ['labour', 'labour_day_supervisor', 'Supervisor day rate', 'Site supervision and coordination', 'day', 35000, 'supervisor, project manager, foreman', 'Use where coordination or supervision is required.'],
            ['waste_disposal', 'skip_6_yard', '6-yard skip', 'Waste removal allowance', 'each', 28000, 'skip, waste, rubbish, disposal', 'Use for typical refurbishment strip-out waste.'],
            ['waste_disposal', 'waste_bag', 'Waste bag collection', 'Waste bag removal allowance', 'each', 12000, 'hippo bag, waste bag, small waste', 'Use for smaller repair jobs where a full skip is excessive.'],
            ['plant_equipment', 'plant_day_small_tools', 'Small tools and plant allowance', 'Small tools and plant allowance', 'day', 3500, 'plant, tools, mixer, cutter, hire', 'Use for small tools, mixers, cutters or basic hire requirements.'],
            ['preliminaries', 'prelim_site_setup', 'Site setup allowance', 'Site setup and protection allowance', 'item', 15000, 'site setup, protection, dust sheets, parking, admin', 'Use for setup, protection, parking/admin and job preparation.'],
            ['materials', 'materials_general_allowance', 'General materials allowance', 'Materials allowance for agreed works', 'item', 25000, 'materials, sundries, fixings, adhesive, sealant', 'Use where exact materials are not yet specified. Mark low confidence if specification is unclear.'],
            ['materials', 'materials_bathroom_prep_allowance', 'Bathroom preparation materials allowance', 'Bathroom preparation materials allowance', 'item', 45000, 'bathroom prep, board, adhesive, grout, tanking, sealant', 'Use for typical bathroom preparation sundries where exact spec is not known.'],
            ['provisional_sum', 'provisional_sum_general', 'General provisional sum', 'Provisional allowance for items subject to confirmation', 'item', 50000, 'provisional, allowance, unknown, subject to confirmation', 'Use sparingly where a cost is likely but scope is not confirmed. Always warning low confidence.'],
        ];

        foreach ($items as [$category, $code, $name, $customerDescription, $unit, $baseCostPence, $aliases, $notes]) {
            PricingRateItem::updateOrCreate(
                ['code' => $code],
                [
                    'pricing_rate_card_id' => $rateCard->id,
                    'category' => $category,
                    'name' => $name,
                    'customer_description' => $customerDescription,
                    'unit' => $unit,
                    'base_cost_pence' => $baseCostPence,
                    'aliases' => $aliases,
                    'quantity_rules' => null,
                    'internal_notes' => $notes,
                    'is_active' => true,
                ]
            );
        }

        $templates = [
            [
                'code' => 'bathroom_refurbishment',
                'name' => 'Bathroom refurbishment',
                'category' => 'bathroom',
                'description' => 'Typical bathroom strip-out, preparation, installation, tiling support and waste removal.',
                'typical_scope' => 'Strip-out, wall/floor preparation, plumbing adjustments, installation support, tiling support, finishing and waste removal.',
                'typical_labour' => 'Skilled labour 5-12 days, general labour 1-3 days depending on size and access.',
                'typical_materials' => 'Preparation boards, adhesive, grout, sealants, fixings and sundries unless customer-supplied.',
                'typical_waste' => 'Usually one 6-yard skip or waste bag depending on strip-out volume.',
                'required_information' => 'Room size, tile area, suite specification, customer-supplied items, access, parking, electrics, plumbing changes.',
                'default_exclusions' => 'Hidden defects, asbestos, structural alterations, electrical upgrades, major plumbing reroutes unless stated.',
                'risk_notes' => 'Low confidence if tile area, suite specification or wall/floor condition is unclear.',
                'suggested_rate_item_codes' => "labour_day_skilled\nlabour_day_general\nskip_6_yard\nmaterials_bathroom_prep_allowance\nprelim_site_setup",
            ],
            [
                'code' => 'kitchen_refurbishment',
                'name' => 'Kitchen refurbishment',
                'category' => 'kitchen',
                'description' => 'Kitchen preparation, fitting support, finishing and waste removal.',
                'typical_scope' => 'Removal, preparation, fitting support, coordination, making good and waste removal.',
                'typical_labour' => 'Skilled labour 4-10 days, general labour 1-3 days.',
                'typical_materials' => 'Fixings, trims, adhesives, making-good materials and sundries unless specified.',
                'typical_waste' => 'One skip is common where units/worktops are removed.',
                'required_information' => 'Kitchen layout, number of units, worktop type, who supplies kitchen, electrics, plumbing, flooring, splashbacks.',
                'default_exclusions' => 'Electrical certification, gas work, structural alterations and hidden defects unless stated.',
                'risk_notes' => 'Low confidence if layout, supplier responsibilities or services are unclear.',
                'suggested_rate_item_codes' => "labour_day_skilled\nlabour_day_general\nskip_6_yard\nmaterials_general_allowance\nprelim_site_setup",
            ],
            [
                'code' => 'general_repair',
                'name' => 'General repair',
                'category' => 'repairs',
                'description' => 'Small repair works and making good.',
                'typical_scope' => 'Inspection, preparation, repair, making good, finishing and small waste removal.',
                'typical_labour' => 'Usually 0.5-3 skilled days depending on repair complexity.',
                'typical_materials' => 'General materials allowance and small tools allowance.',
                'typical_waste' => 'Waste bag if needed.',
                'required_information' => 'Exact defect, cause, access, dimensions, finish standard and whether matching materials are required.',
                'default_exclusions' => 'Underlying causes, hidden defects and specialist reports unless stated.',
                'risk_notes' => 'Low confidence if the cause of the defect is unknown.',
                'suggested_rate_item_codes' => "labour_day_skilled\nwaste_bag\nmaterials_general_allowance\nplant_day_small_tools",
            ],
        ];

        foreach ($templates as $template) {
            PricingJobTemplate::updateOrCreate(['code' => $template['code']], array_merge($template, ['is_active' => true]));
        }
    }
}
