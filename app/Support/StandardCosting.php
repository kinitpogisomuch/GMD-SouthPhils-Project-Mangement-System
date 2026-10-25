<?php

namespace App\Support;

use App\Models\QuotationBatch;

/**
 * The default costing of every standard tank size — the materials and labor a new costing starts with, so the
 * admin doesn't build it from scratch. Each one is worked out backwards from GMD's estimated price for that size
 * (TankCatalog sizes), so with the default pricing the unit price lands on the estimate:
 *
 *   estimated price  ÷ (1 + 10% markup) ÷ (1 + 10% contingency)  = direct cost per tank
 *   direct cost      − default crew's daily rates × working days  = materials incl. the 10% material factor
 *   materials        ÷ (1 + 10% factor)                            = the materials' own cost
 *
 * That cost is filled with GMD's usual consumables (quantities grow with the tank size) at the average prices
 * GMD paid on past projects; MS plates make up the rest, and a few angle bars / sanding papers absorb what's left,
 * so every quantity stays a whole number and the price comes within a few pesos of the estimate.
 * The admin can still change any row before saving.
 */
class StandardCosting
{
    /**
     * GMD's default crew on every standard costing — [first name, last name, role, daily rate]. Name, role and rate
     * are read from the employee's record when it exists (so a rate change carries over); these are the fallback.
     */
    private const CREW = [
        ['Andres',  'Cabanban',     'Welder',       750],
        ['Terence', 'Almanza',      'Fabricator',   550],
        ['Robert',  'Olan',         'Helper/Labor', 550],
        ['Raven',   'Manansalatus', 'Helper/Labor', 480],
        ['Sammy',   'Maceda',       'Helper/Labor', 450],
    ];

    private static ?array $crew = null;

    /** The default crew as labor rows: the employee's display name (as the costing page lists them), role and daily rate */
    private static function crew(): array
    {
        if (self::$crew !== null) {
            return self::$crew;
        }

        $employees = \App\Models\Employee::whereIn('last_name', array_column(self::CREW, 1))->get();

        return self::$crew = array_map(function ($c) use ($employees) {
            [$first, $last, $role, $rate] = $c;
            $emp = $employees->first(fn ($e) => strcasecmp($e->first_name, $first) === 0 && strcasecmp($e->last_name, $last) === 0);

            return [
                'name' => $emp->name ?? "{$last}, {$first}",
                'role' => $emp->role ?? $role,
                'rate' => (float) ($emp->daily_rate ?? $rate),
            ];
        }, self::CREW);
    }

    /** [name, unit, price] — average prices from GMD's past purchases */
    private const PLATE_6MM  = ['MS Plates - 6mm x 4ft. x 8ft.', 'pcs', 4474];
    private const PLATE_10MM = ['MS Plates - 10mm x 4ft. x 8ft.', 'pcs', 10825];
    private const ANGLE_BAR  = ['Angular Bar - 2"x 6mm thick', 'pcs', 1141];
    private const SANDING    = ['Sanding Paper #60', 'pcs', 45];

    /** The consumables every tank uses, for a 10,000-liter tank — scaled to the tank's size */
    private const CONSUMABLES = [
        [['Electrode - 3.2mm (6011)', 'kilos', 100], 10],
        [['Electrode - 3.2mm (7018)', 'kilos', 109], 10],
        [['Grinding Disc #4', 'pcs', 68], 10],
        [['Cutting Disc #4', 'pcs', 65], 6],
        [['Industrial Oxygen', 'cylinders', 804], 2],
        [['Acetylene', 'cylinders', 1379], 2],
        [['Epoxy Primer Gray', 'galons', 2113], 3],
        [['QDE Medium Gray', 'galons', 1107], 3],
        [['Lacquer Thinner', 'galons', 559], 2],
        [['Paint Brush', 'pcs', 124], 4],
        [['Roller Brush', 'pcs', 200], 2],
        [['Welding Gloves', 'pcs', 209], 3],
        [['Cotton Gloves', 'pcs', 83], 6],
        [['Penetrant Dye Spray', 'pairs', 1453], 2],
        [['Pressure Test Kits', 'set', 3562], 1],
    ];

    /**
     * Per tank type: the plate it's built from, extra items, and working days for each standard size (in the order
     * the sizes are listed in TankCatalog). Every type uses the same default crew (see CREW).
     */
    private static function recipes(): array
    {
        $stand = [[['Wide Flange - 4 x 4', 'pcs', 5010], 2]];   // saddles / stand

        return [
            'Underground Fuel Storage Tanks'  => ['plate' => self::PLATE_6MM,  'extras' => [],     'days' => [20, 25, 30]],
            'Aboveground Fuel Storage Tanks'  => ['plate' => self::PLATE_6MM,  'extras' => $stand, 'days' => [20, 25, 30]],
            'Fuel Day Tanks'                  => ['plate' => self::PLATE_6MM,  'extras' => [[['Pressure Gauge 60PSI', 'pcs', 947], 1]], 'days' => [12, 13, 14]],
            'Cooking Oil Storage Tank'        => ['plate' => self::PLATE_6MM,  'extras' => $stand, 'days' => [20]],
            'Chemical Tank'                   => ['plate' => self::PLATE_10MM, 'extras' => [],     'days' => [15, 18]],
            'Polymer Tank'                    => ['plate' => self::PLATE_10MM, 'extras' => [],     'days' => [45]],
            'Aboveground Water Storage Tanks' => ['plate' => self::PLATE_6MM,  'extras' => $stand, 'days' => [20]],
            'Tetrapod'                        => ['plate' => self::PLATE_6MM,  'extras' => [],     'days' => [12, 10]],
        ];
    }

    /** Every standard size's default costing, keyed "type|capacity" (for the costing page) */
    public static function all(): array
    {
        $out = [];
        foreach (self::recipes() as $type => $recipe) {
            foreach (TankCatalog::forType($type)['sizes'] ?? [] as $i => $size) {
                if ($costing = self::build($type, $size, $recipe, $i)) {
                    $out[$type . '|' . $size['capacity']] = $costing;
                }
            }
        }

        return $out;
    }

    /** The default costing for this tank type and capacity, or null when it isn't a standard size with a price */
    public static function forTank(?string $type, ?string $capacity): ?array
    {
        $type   = \App\Models\ProjectTankItem::canonicalType($type);
        $recipe = self::recipes()[$type] ?? null;
        if (!$recipe || !$capacity) {
            return null;
        }
        $num = fn ($s) => (float) preg_replace('/[^0-9.]/', '', str_replace(',', '', (string) $s));
        foreach (TankCatalog::forType($type)['sizes'] ?? [] as $i => $size) {
            if ($num($size['capacity']) === $num($capacity)) {
                return self::build($type, $size, $recipe, $i);
            }
        }

        return null;
    }

    private static function build(string $type, array $size, array $recipe, int $index): ?array
    {
        $price = (float) ($size['price'] ?? 0);
        if ($price <= 0) {
            return null;
        }

        $markup = QuotationBatch::DEFAULT_MARKUP / 100;
        $cont   = QuotationBatch::DEFAULT_CONTINGENCY / 100;
        $factor = \App\Models\ProjectMaterial::DEFAULT_FACTOR / 100;

        // labor: GMD's default crew × working days for this size (the materials below absorb the rest, so the
        // price still lands on the estimate whoever is on the crew)
        $days      = $recipe['days'][$index] ?? end($recipe['days']);
        $labor     = self::crew();
        $laborCost = $days * array_sum(array_column($labor, 'rate'));

        // what the materials themselves may cost (before the factor)
        $direct  = $price / (1 + $markup) / (1 + $cont);
        $budget  = ($direct - $laborCost) / (1 + $factor);
        if ($budget <= self::PLATE_6MM[2]) {
            return null;   // the estimate is too low for this recipe — no default
        }

        // consumables scale with the size (√ of the volume against a 10,000-liter tank)
        $volume = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '', $size['capacity']));
        $liters = str_contains(strtolower($size['capacity']), 'cubic') ? $volume * 1000 * 5 : $volume;   // a tetrapod mold ≈ a small tank
        $scale  = max(0.6, min(2.5, sqrt(max(1, $liters) / 10000)));

        $materials = [];
        $spent     = 0;
        foreach (array_merge(self::CONSUMABLES, $recipe['extras']) as [[$name, $unit, $unitPrice], $qty]) {
            $q = max(1, (int) round($qty * $scale));
            $materials[] = ['name' => $name, 'unit' => $unit, 'qty' => $q, 'price' => $unitPrice];
            $spent += $q * $unitPrice;
        }

        // the plates make up most of the rest; angle bars, then sanding papers, take up what's left
        [$plateName, $plateUnit, $platePrice] = $recipe['plate'];
        $left   = $budget - $spent;
        $plates = max(1, (int) floor($left / $platePrice));
        $left  -= $plates * $platePrice;
        $bars   = max(0, (int) floor($left / self::ANGLE_BAR[2]));
        $left  -= $bars * self::ANGLE_BAR[2];
        $papers = max(0, (int) round($left / self::SANDING[2]));

        array_unshift($materials, ['name' => $plateName, 'unit' => $plateUnit, 'qty' => $plates, 'price' => $platePrice]);
        if ($bars > 0) {
            array_splice($materials, 1, 0, [['name' => self::ANGLE_BAR[0], 'unit' => self::ANGLE_BAR[1], 'qty' => $bars, 'price' => self::ANGLE_BAR[2]]]);
        }
        if ($papers > 0) {
            $materials[] = ['name' => self::SANDING[0], 'unit' => self::SANDING[1], 'qty' => $papers, 'price' => self::SANDING[2]];
        }

        return [
            'id'                     => 'std:' . $type . '|' . $size['capacity'],
            'name'                   => $type . ' — ' . $size['capacity'],
            'tank_type'              => $type,
            'capacity'               => $size['capacity'],
            'estimated_price'        => $price,
            'factor'                 => \App\Models\ProjectMaterial::DEFAULT_FACTOR,
            'estimated_working_days' => $days,
            'markup_percent'         => QuotationBatch::DEFAULT_MARKUP,
            'contingency_percent'    => QuotationBatch::DEFAULT_CONTINGENCY,
            'payment_term_type'      => null,   // follows the contract value on the page
            'materials'              => $materials,
            'labor'                  => $labor,
        ];
    }

    /**
     * The standard costing a saved quotation still matches exactly (its tank, materials, crew and pricing are the
     * standard ones), or null. Such a quotation is priced at exactly GMD's estimated price (see QuotationBatch::markupAmount).
     */
    public static function matchedBy(QuotationBatch $batch): ?array
    {
        $std = self::forTank($batch->tank_type, $batch->capacity);
        if (!$std) {
            return null;
        }
        $same = fn ($a, $b) => abs((float) $a - (float) $b) < 0.005;
        $key  = fn ($s) => mb_strtolower(trim((string) $s));

        if (!$same($batch->markup_percent, $std['markup_percent']) || !$same($batch->contingency_percent, $std['contingency_percent'])
            || !$same($batch->estimated_working_days, $std['estimated_working_days'])) {
            return null;
        }

        $materials = $batch->activeMaterials()->get();
        if ($materials->count() !== count($std['materials'])) {
            return null;
        }
        foreach ($std['materials'] as $m) {
            $hit = $materials->first(fn ($row) => $key($row->material_name) === $key($m['name']) && $key($row->unit) === $key($m['unit'])
                && $same($row->quantity, $m['qty']) && $same($row->price_per_unit, $m['price']) && $same($row->factor ?? $std['factor'], $std['factor']));
            if (!$hit) {
                return null;
            }
        }

        // labor is saved as "Name (Role)"
        $labor = $batch->activeLabor()->get();
        if ($labor->count() !== count($std['labor'])) {
            return null;
        }
        foreach ($std['labor'] as $l) {
            $hit = $labor->first(fn ($row) => $key(preg_replace('/\s*\([^()]*\)\s*$/', '', (string) $row->description)) === $key($l['name'])
                && $same($row->daily_rate, $l['rate']));
            if (!$hit) {
                return null;
            }
        }

        return $std;
    }

    /** The unit price this costing gives with the default pricing (no other costs) — for checks */
    public static function unitPrice(array $costing): float
    {
        $materials = array_sum(array_map(fn ($m) => $m['qty'] * $m['price'], $costing['materials'])) * (1 + $costing['factor'] / 100);
        $labor     = $costing['estimated_working_days'] * array_sum(array_column($costing['labor'], 'rate'));
        $direct    = $materials + $labor;

        return round($direct * (1 + $costing['contingency_percent'] / 100) * (1 + $costing['markup_percent'] / 100), 2);
    }
}
