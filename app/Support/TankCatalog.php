<?php

namespace App\Support;

use App\Models\ProjectTankItem;

/**
 * Everything the system knows about each tank type GMD builds, in one place:
 *  - the card shown on the landing page ("Our Work") and the client portal (icon, blurb, image)
 *  - its details page (/tanks/{slug}): the standard sizes GMD builds, what is included, what the form asks
 *  - the quotation request form for that type: how capacity is asked, and the extra fields that only
 *    make sense for it (wall construction for fuel tanks, pipe diameter for pipelines, …)
 *  - the specification lines and general notes the generated quotation starts from.
 *
 * Types are keyed by a URL slug; `type` is the exact value stored in quotation_requests / project_tank_items
 * (ProjectTankItem::TANK_TYPES), so existing data and reports keep working.
 */
class TankCatalog
{
    private const THICKNESS = ['Recommend for me', '4.5 mm', '6.0 mm', '8.0 mm', '10.0 mm'];
    private const FUEL      = ['Diesel', 'Gasoline', 'Kerosene', 'Bunker fuel', 'Other'];
    private const PIPE_DIA  = ['1"', '1-1/2"', '2"', '3"', '4"', 'Other / not sure'];
    /** The only type-specific questions the client's form asks: the service (Fuel Pipeline) and, for "Other size", dimensions */
    private const CLIENT_ASKS = ['service', 'dimensions'];

    private const PIPE_INSTALL = 'Fuel Pipe Line Installation';
    private const PIPE_REPIPE  = 'Re-piping of Fuel Pipe Line';

    /** Construction details GMD applies to every steel fuel tank unless the quotation says otherwise */
    private const FUEL_TANK_SPECS = [
        ['Construction Process', 'Precise roller bending using heavy-duty roller machine. Double-welded square groove butt joint, full penetration / complete fusion (root pass and capping).'],
        ['Stub-outs (Couplings)', '3" Fill, 3" Suction, 2" Air vent, 2" Spare'],
        ['External Paint', 'Steel treated with anti-rust; 2 coats epoxy metal primer and 2 coats black coal tar top coat finish'],
        ['Accessories', '6 mm thk. Ø600 mm hi-tensile bolted manhole with sealed rubber gasket and 8 mm lifting lugs'],
        ['Pneumatic Test', 'Tank tested at an applied pressure of 5 PSI held steady for 6 hours of observation. All weld joints bubble-tested using the soap-suds method.'],
    ];

    private const DIMENSIONS_FIELD = [
        'key' => 'dimensions', 'label' => 'Preferred Dimensions', 'type' => 'text', 'required' => false,
        'placeholder' => 'e.g. 2,200 mm Ø × 4,850 mm L (leave blank and we will design it)',
    ];

    /** Request-lifetime cache of the admin's card rows (cleared whenever a TankCard is saved or deleted) */
    private static ?\Illuminate\Support\Collection $edits = null;

    public static function flush(): void
    {
        self::$edits = null;
    }

    /** The admin's TankCard rows keyed by slug — empty if the table isn't there yet (e.g. mid-migration) */
    private static function edits(): \Illuminate\Support\Collection
    {
        if (self::$edits === null) {
            try {
                self::$edits = \App\Models\TankCard::all()->keyBy('slug');
            } catch (\Illuminate\Database\QueryException $e) {
                return collect();
            }
        }

        return self::$edits;
    }

    /** Every tank type: the built-in ones, then the cards the admin added in Settings */
    public static function all(): array
    {
        $types = self::builtIn();

        // standard sizes edited in Settings replace the defaults (null = keep the defaults)
        foreach ($types as $slug => &$def) {
            $def['default_sizes'] = $def['sizes'];
            $edit = self::edits()->get($slug);
            if ($edit && $edit->sizes !== null) {
                $def['sizes'] = self::cleanSizes($edit->sizes);
            }
        }
        unset($def);

        foreach (self::edits()->filter(fn ($c) => $c->is_custom && $c->type) as $slug => $card) {
            $types[$slug] = [
                'slug'     => $slug,
                'type'     => $card->type,
                'icon'     => 'package',
                'blurb'    => $card->blurb ?: 'Tell us what you need and we will quote it.',
                'keywords' => [],
                'capacity' => ['label' => $card->capacity_label ?: 'Capacity / Size', 'placeholder' => 'e.g. 5,000 liters'],
                'unit'     => 'unit',
                // added cards use a general form: what the client needs, plus optional dimensions
                'fields'   => [
                    ['key' => 'description', 'label' => 'Requirements', 'type' => 'textarea', 'required' => true, 'placeholder' => 'Describe what you need — use, material, fittings, anything that matters'],
                    self::DIMENSIONS_FIELD,
                ],
                'scope'    => 'fabrication of ' . mb_strtolower($card->type),
                'specs'    => [],
                'sizes'    => self::cleanSizes($card->sizes ?? []),
                'default_sizes' => [],
                'custom'   => true,
            ];
        }

        return $types;
    }

    private static function builtIn(): array
    {
        $types = [
            'underground-fuel-storage-tanks' => [
                'type'     => 'Underground Fuel Storage Tanks',
                'product'  => 'Fuel',   // what it stores — automatic, shown on the quotation as Product Stored
                'icon'     => 'fuel',
                'blurb'    => 'Buried steel tanks for gas stations and depots — coated, pressure-tested and built to your capacity.',
                'keywords' => ['underground', 'ufst'],
                'image'    => 'images/portfolio/fuel-tank-1.jpg',
                'capacity' => ['label' => 'Tank Capacity', 'placeholder' => 'e.g. 18,000 liters'],
                'sizes'    => [['10,000 liters', '2.4 m Ø × 2.4 m L', 160000], ['16,000 liters', '2.1 m Ø × 4.8 m L', 210000], ['24,000 liters', '2.3 m Ø × 6.0 m L', 280000]],
                'fields'   => [
                    ['key' => 'product_stored', 'label' => 'Product Stored', 'type' => 'select', 'options' => self::FUEL, 'ask' => false],
                    ['key' => 'wall_type', 'label' => 'Wall Construction', 'type' => 'select', 'options' => ['Single wall', 'Double wall'], 'required' => true],
                    ['key' => 'compartments', 'label' => 'Number of Compartments', 'type' => 'number', 'min' => 1, 'max' => 4, 'default' => 1, 'required' => true],
                    self::DIMENSIONS_FIELD,
                ],
                'scope'    => 'fabrication of an underground fuel storage tank',
                'specs'    => array_merge([['Orientation', 'Underground / Horizontal / Cylindrical / Flat heads with stiffeners']], self::FUEL_TANK_SPECS),
            ],
            'aboveground-fuel-storage-tanks' => [
                'type'     => 'Aboveground Fuel Storage Tanks',
                'product'  => 'Fuel',
                'icon'     => 'container',
                'blurb'    => 'Saddle-mounted or vertical fuel tanks for plants, farms and generator sets.',
                'keywords' => ['aboveground fuel', 'above ground fuel', 'agfst'],
                'capacity' => ['label' => 'Tank Capacity', 'placeholder' => 'e.g. 10,000 liters'],
                'sizes'    => [['10,000 liters', '2.4 m Ø × 2.4 m L', 180000], ['16,000 liters', '2.1 m Ø × 4.8 m L', 230000], ['24,000 liters', '2.3 m Ø × 6.0 m L', 300000]],
                'fields'   => [
                    ['key' => 'product_stored', 'label' => 'Product Stored', 'type' => 'select', 'options' => self::FUEL, 'ask' => false],
                    ['key' => 'orientation', 'label' => 'Orientation', 'type' => 'select', 'options' => ['Horizontal (saddle-mounted)', 'Vertical'], 'required' => true],
                    self::DIMENSIONS_FIELD,
                ],
                'scope'    => 'fabrication of an aboveground fuel storage tank',
                'specs'    => self::FUEL_TANK_SPECS,
            ],
            'fuel-day-tanks' => [
                'type'     => 'Fuel Day Tanks',
                'product'  => 'Fuel',
                'icon'     => 'gauge',
                'blurb'    => 'Compact day tanks that feed generators and boilers, with gauges and stands.',
                'keywords' => ['day tank'],
                'capacity' => ['label' => 'Tank Capacity', 'placeholder' => 'e.g. 1,000 liters'],
                'sizes'    => [['2,000 liters', '1.2 m Ø × 1.8 m L', 110000], ['2,500 liters', '1.2 m Ø × 2.0 m L', 115000], ['3,000 liters', '1.3 m Ø × 2.4 m L', 125000]],
                'fields'   => [
                    ['key' => 'mounting', 'label' => 'Mounting', 'type' => 'select', 'options' => ['Floor-standing with steel stand', 'Skid-mounted', 'Wall-mounted'], 'required' => true],
                    ['key' => 'level_gauge', 'label' => 'Level Gauge', 'type' => 'select', 'options' => ['Sight glass', 'Float gauge', 'None'], 'required' => true],
                    ['key' => 'connected_equipment', 'label' => 'Equipment It Feeds', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. 500 kVA generator set'],
                    self::DIMENSIONS_FIELD,
                ],
                'scope'    => 'fabrication of a fuel day tank',
                'specs'    => [
                    ['Construction Process', 'Continuous welded mild steel construction; all joints leak-tested'],
                    ['Fittings', 'Fill, supply, return, vent and drain connections'],
                    ['External Paint', '2 coats epoxy primer and 2 coats enamel top coat finish'],
                ],
            ],
            'cooking-oil-storage-tank' => [
                'type'     => 'Cooking Oil Storage Tank',
                'product'  => 'Cooking oil',
                'icon'     => 'droplet',
                'blurb'    => 'Food-grade tanks for edible oil storage and processing.',
                'keywords' => ['cooking oil', 'edible oil', 'oil storage'],
                'capacity' => ['label' => 'Tank Capacity', 'placeholder' => 'e.g. 5,000 liters'],
                'sizes'    => [['10,000 liters', '2.4 m Ø × 2.4 m L', 198000]],
                'fields'   => [
                    ['key' => 'material', 'label' => 'Tank Material', 'type' => 'select', 'options' => ['Stainless steel 304', 'Mild steel with food-grade epoxy lining', 'Recommend for me'], 'required' => true],
                    ['key' => 'orientation', 'label' => 'Orientation', 'type' => 'select', 'options' => ['Vertical', 'Horizontal'], 'required' => true],
                    ['key' => 'bottom_type', 'label' => 'Bottom Type', 'type' => 'select', 'options' => ['Flat bottom', 'Cone bottom (for draining)'], 'required' => false],
                    self::DIMENSIONS_FIELD,
                ],
                'scope'    => 'fabrication of a cooking oil storage tank',
                'specs'    => [
                    ['Construction Process', 'Full-penetration welded joints, ground smooth on the product side'],
                    ['Fittings', 'Inlet, outlet, drain valve, vent and top manhole'],
                ],
            ],
            'chemical-tank' => [
                'type'     => 'Chemical Tank',
                'icon'     => 'flask-conical',
                'blurb'    => 'Tanks built for the chemical you store — material and lining matched to it.',
                'keywords' => ['chemical'],
                'capacity' => ['label' => 'Tank Capacity', 'placeholder' => 'e.g. 3,000 liters'],
                'sizes'    => [['1,000 liters', '1.2 m Ø × 1.2 m L', 180000], ['2,000 liters', '1.2 m Ø × 1.8 m L', 230000]],
                'fields'   => [
                    ['key' => 'chemical_stored', 'label' => 'Chemical Stored', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. Sodium hypochlorite'],
                    ['key' => 'concentration', 'label' => 'Concentration / Temperature', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. 12%, ambient'],
                    ['key' => 'material', 'label' => 'Tank Material', 'type' => 'select', 'options' => ['Stainless steel 304', 'Stainless steel 316', 'Mild steel with chemical-resistant lining', 'Recommend for me'], 'required' => true],
                    ['key' => 'orientation', 'label' => 'Orientation', 'type' => 'select', 'options' => ['Vertical', 'Horizontal'], 'required' => true],
                    self::DIMENSIONS_FIELD,
                ],
                'scope'    => 'fabrication of a chemical storage tank',
                'specs'    => [
                    ['Construction Process', 'Full-penetration welded joints; material and lining selected for the stored chemical'],
                    ['Fittings', 'Inlet, outlet, drain, vent and inspection manhole'],
                ],
                'warranty' => 'Six (6) months warranty on workmanship under normal operating conditions, for the chemical and concentration declared above only.',
            ],
            'polymer-tank' => [
                'type'     => 'Polymer Tank',
                'icon'     => 'beaker',
                'blurb'    => 'Mixing and storage tanks for polymer and water-treatment dosing.',
                'keywords' => ['polymer'],
                'capacity' => ['label' => 'Tank Capacity', 'placeholder' => 'e.g. 2,000 liters'],
                'sizes'    => [['60,000 liters', '12 m L × 2.2 m W × 2.2 m H', 525000]],
                'fields'   => [
                    ['key' => 'material', 'label' => 'Tank Material', 'type' => 'select', 'options' => ['Stainless steel 304', 'Mild steel with epoxy lining', 'Recommend for me'], 'required' => true],
                    ['key' => 'agitator', 'label' => 'Agitator / Mixer Mount', 'type' => 'select', 'options' => ['With agitator mounting', 'None'], 'required' => true],
                    self::DIMENSIONS_FIELD,
                ],
                'scope'    => 'fabrication of a polymer tank',
                'specs'    => [
                    ['Construction Process', 'Continuous welded construction, leak-tested'],
                    ['Fittings', 'Inlet, outlet, drain and overflow connections'],
                ],
            ],
            'aboveground-water-storage-tanks' => [
                'type'     => 'Aboveground Water Storage Tanks',
                'product'  => 'Water',
                'icon'     => 'droplets',
                'blurb'    => 'Ground-level or elevated water tanks for buildings, farms and communities.',
                'keywords' => ['water'],
                'capacity' => ['label' => 'Tank Capacity', 'placeholder' => 'e.g. 20,000 liters'],
                'sizes'    => [['10,000 liters', '2.4 m Ø × 2.4 m L', 210000]],
                'fields'   => [
                    ['key' => 'installation', 'label' => 'Installation', 'type' => 'select', 'options' => ['Ground-level', 'Elevated on steel tower'], 'required' => true],
                    ['key' => 'tower_height', 'label' => 'Tower Height (meters)', 'type' => 'number', 'min' => 1, 'max' => 60, 'required' => false, 'show_if' => ['installation', 'Elevated on steel tower']],
                    ['key' => 'material', 'label' => 'Tank Material', 'type' => 'select', 'options' => ['Mild steel, painted', 'Stainless steel 304', 'Recommend for me'], 'required' => true],
                    self::DIMENSIONS_FIELD,
                ],
                'scope'    => 'fabrication of an aboveground water storage tank',
                'specs'    => [
                    ['Construction Process', 'Roller-bent shell plates with continuous welded joints, hydro-tested for leaks'],
                    ['Fittings', 'Inlet, outlet, overflow, drain, vent and access manhole with ladder'],
                    ['Internal Coating', 'Potable-water-safe epoxy lining (mild steel tanks)'],
                ],
            ],
            'tetrapod' => [
                'type'     => 'Tetrapod',
                'icon'     => 'triangle',
                'blurb'    => 'Steel tetrapod molds for coastal protection and breakwater projects.',
                'keywords' => ['tetrapod'],
                'capacity' => ['label' => 'Tetrapod Size', 'placeholder' => 'e.g. 2 tons'],
                'sizes'    => [['1.2 cubic meters', '', 93000], ['0.8 cubic meters', '', 85000]],
                'unit'     => 'set',
                'fields'   => [
                    ['key' => 'plate_thickness', 'label' => 'Mold Plate Thickness', 'type' => 'select', 'options' => self::THICKNESS, 'required' => false],
                    ['key' => 'project_use', 'label' => 'Project / Use', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. seawall, breakwater'],
                ],
                'scope'    => 'fabrication of steel tetrapod molds',
                'specs'    => [
                    ['Construction', 'Bolted steel plate mold sections with stiffeners and lifting points'],
                ],
                'warranty' => 'Six (6) months warranty on workmanship under normal use.',
            ],
            // One card for both pipeline services. The client picks the service first; the request is then
            // stored as the matching type below (stores_as), so projects and reports keep the two types apart.
            'fuel-pipeline' => [
                'type'     => 'Fuel Pipeline',
                'icon'     => 'workflow',
                'blurb'    => 'New fuel line installation, or re-piping of old and leaking lines — from tank to dispenser.',
                'keywords' => ['pipe line', 'pipeline', 're-piping', 'repiping'],
                'capacity' => ['label' => 'Total Pipe Length', 'placeholder' => 'e.g. 120 meters'],
                'project_based' => 'Priced per project — dimensions and total length depend on your layout.',
                'rate'     => [750, 'linear meter'],   // estimated price per meter of pipe line
                'unit'     => 'lot',
                'fields'   => [
                    ['key' => 'service', 'label' => 'Service Needed', 'type' => 'select', 'options' => [self::PIPE_INSTALL, self::PIPE_REPIPE], 'required' => true],
                    ['key' => 'pipe_diameter', 'label' => 'Pipe Diameter', 'type' => 'select', 'options' => self::PIPE_DIA, 'required' => true],
                    ['key' => 'pipe_type', 'label' => 'Pipe Type', 'type' => 'select', 'options' => ['Black iron Sch. 40', 'Black iron Sch. 80', 'Galvanized iron', 'Recommend for me'], 'required' => true],
                    ['key' => 'routing', 'label' => 'Routing', 'type' => 'select', 'options' => ['Underground', 'Aboveground', 'Both'], 'required' => true, 'show_if' => ['service', self::PIPE_INSTALL]],
                    ['key' => 'dispensers', 'label' => 'Number of Dispensers', 'type' => 'number', 'min' => 0, 'max' => 50, 'required' => false, 'show_if' => ['service', self::PIPE_INSTALL]],
                    ['key' => 'remove_existing', 'label' => 'Remove Existing Lines', 'type' => 'select', 'options' => ['Yes', 'No'], 'required' => true, 'show_if' => ['service', self::PIPE_REPIPE]],
                    ['key' => 'site_status', 'label' => 'Site Status During Work', 'type' => 'select', 'options' => ['Station / site operating', 'Station / site closed'], 'required' => false, 'show_if' => ['service', self::PIPE_REPIPE]],
                ],
                'stores_as' => ['service', [self::PIPE_INSTALL => 'fuel-pipe-line-installation', self::PIPE_REPIPE => 're-piping-of-fuel-pipe-line']],
                // shown as the choice cards on the details page
                'choice_help' => [
                    self::PIPE_INSTALL => ['icon' => 'workflow', 'text' => 'Supply and installation of new fuel lines from tank to dispenser.'],
                    self::PIPE_REPIPE  => ['icon' => 'wrench', 'text' => 'Replace old or leaking lines with minimal downtime.'],
                ],
                'scope'    => 'supply and installation, or re-piping, of fuel pipe lines',
                'specs'    => [
                    ['Testing', 'Pipe lines pressure-tested before backfilling / turnover'],
                    ['Coating', 'Anti-corrosion wrapping on buried sections'],
                ],
            ],
            // the two pipeline services as stored on requests and projects — requested through "Fuel Pipeline"
            'fuel-pipe-line-installation' => [
                'card'        => false,
                'merged_into' => 'fuel-pipeline',
                'type'     => 'Fuel Pipe Line Installation',
                'icon'     => 'workflow',
                'blurb'    => 'Supply and installation of fuel lines from tank to dispenser.',
                'keywords' => ['pipe line installation', 'pipeline installation', 'pipe line'],
                'capacity' => ['label' => 'Total Pipe Length', 'placeholder' => 'e.g. 120 meters'],
                'project_based' => 'Priced per project — dimensions and total length depend on your layout.',
                'rate'     => [750, 'linear meter'],   // estimated price per meter of pipe line
                'unit'     => 'lot',
                'fields'   => [
                    ['key' => 'pipe_diameter', 'label' => 'Pipe Diameter', 'type' => 'select', 'options' => self::PIPE_DIA, 'required' => true],
                    ['key' => 'pipe_type', 'label' => 'Pipe Type', 'type' => 'select', 'options' => ['Black iron Sch. 40', 'Black iron Sch. 80', 'Galvanized iron', 'Recommend for me'], 'required' => true],
                    ['key' => 'routing', 'label' => 'Routing', 'type' => 'select', 'options' => ['Underground', 'Aboveground', 'Both'], 'required' => true],
                    ['key' => 'dispensers', 'label' => 'Number of Dispensers', 'type' => 'number', 'min' => 0, 'max' => 50, 'required' => false],
                ],
                'scope'    => 'supply and installation of fuel pipe lines',
                'specs'    => [
                    ['Testing', 'Pipe lines pressure-tested before backfilling / turnover'],
                    ['Coating', 'Anti-corrosion wrapping on buried sections'],
                ],
            ],
            're-piping-of-fuel-pipe-line' => [
                'card'        => false,
                'merged_into' => 'fuel-pipeline',
                'type'     => 'Re-piping of Fuel Pipe Line',
                'icon'     => 'wrench',
                'blurb'    => 'Replacement of old or leaking fuel lines with minimal downtime.',
                'keywords' => ['re-piping', 'repiping'],
                'capacity' => ['label' => 'Total Pipe Length', 'placeholder' => 'e.g. 60 meters'],
                'project_based' => 'Priced per project — dimensions and total length depend on your layout.',
                'rate'     => [750, 'linear meter'],   // estimated price per meter of pipe line
                'unit'     => 'lot',
                'fields'   => [
                    ['key' => 'pipe_diameter', 'label' => 'Pipe Diameter', 'type' => 'select', 'options' => self::PIPE_DIA, 'required' => true],
                    ['key' => 'pipe_type', 'label' => 'Pipe Type', 'type' => 'select', 'options' => ['Black iron Sch. 40', 'Black iron Sch. 80', 'Galvanized iron', 'Recommend for me'], 'required' => true],
                    ['key' => 'remove_existing', 'label' => 'Remove Existing Lines', 'type' => 'select', 'options' => ['Yes', 'No'], 'required' => true],
                    ['key' => 'site_status', 'label' => 'Site Status During Work', 'type' => 'select', 'options' => ['Station / site operating', 'Station / site closed'], 'required' => false],
                ],
                'scope'    => 're-piping of fuel pipe lines',
                'specs'    => [
                    ['Testing', 'New lines pressure-tested before turnover'],
                ],
            ],
            // no card (removed from the landing page and the client's form); still the fallback for older
            // free-text tank types on existing requests and projects
            'others' => [
                'card'     => false,
                'type'     => 'Others',
                'icon'     => 'settings',
                'blurb'    => 'Something else in steel? Describe it and we will quote it.',
                'keywords' => [],
                'capacity' => ['label' => 'Capacity / Size', 'placeholder' => 'e.g. 500 liters, 2 m × 1 m'],
                'fields'   => [
                    ['key' => 'description', 'label' => 'What do you need?', 'type' => 'textarea', 'required' => true, 'placeholder' => 'Describe the tank or structure, its use and any requirements'],
                    self::DIMENSIONS_FIELD,
                ],
                'scope'    => 'fabrication of the item described below',
                'specs'    => [],
            ],
        ];

        foreach ($types as $slug => &$def) {
            $def['slug'] = $slug;
            $def['unit'] = $def['unit'] ?? 'unit';
            // standard sizes GMD builds (clients pick one on the tank's details page or type their own)
            // [capacity, dimensions, estimated price per unit (₱)]
            $def['sizes'] = array_map(fn ($s) => ['capacity' => $s[0], 'dimensions' => $s[1], 'price' => $s[2] ?? null], $def['sizes'] ?? []);
        }

        return $types;
    }

    public static function find(?string $slug): ?array
    {
        return $slug ? (self::all()[$slug] ?? null) : null;
    }

    /** The catalog entry for a stored tank type (canonical spelling); free-text types fall back to "Others" */
    public static function forType(?string $type): array
    {
        $canonical = ProjectTankItem::canonicalType($type);
        foreach (self::all() as $def) {
            if ($def['type'] === $canonical) {
                return $def;
            }
        }

        return self::all()['others'];
    }

    public static function slugFor(?string $type): ?string
    {
        return self::forType($type)['slug'] ?? null;
    }

    /**
     * The fields the form asks. A retired field ('ask' => false, e.g. Product Stored, now automatic from the type)
     * is no longer asked or stored, but describeAnswers() still shows it on requests that have it.
     */
    private static function askedFields(array $def): array
    {
        // Clients often don't know tank construction (panel feedback), so the form asks only the size and quantity
        // — plus, on a combined card, which service they need. GMD decides the rest when it prepares the quotation;
        // the other fields stay defined so answers on earlier requests still show.
        return array_values(array_filter($def['fields'],
            fn ($f) => ($f['ask'] ?? true) !== false && in_array($f['key'], self::CLIENT_ASKS, true)));
    }

    /** A field whose show_if condition isn't met is ignored entirely (neither required nor stored) */
    private static function fieldApplies(array $field, array $answers): bool
    {
        if (empty($field['show_if'])) {
            return true;
        }
        [$key, $value] = $field['show_if'];

        return ($answers[$key] ?? null) === $value;
    }

    /**
     * Validation rules for one tank's type-specific answers, e.g. "tank_items.0.specs.wall_type".
     *
     * @return array{0: array, 1: array} rules, attribute names
     */
    public static function rules(string $slug, string $prefix, array $answers): array
    {
        $def = self::find($slug) ?? self::all()['others'];
        $rules = [];
        $names = [];

        foreach (self::askedFields($def) as $field) {
            $key = "{$prefix}.{$field['key']}";
            $names[$key] = mb_strtolower($field['label']);

            if (!self::fieldApplies($field, $answers)) {
                continue;
            }

            $rule = [($field['required'] ?? false) ? 'required' : 'nullable'];
            $rule[] = match ($field['type']) {
                'number'   => 'numeric',
                'textarea' => 'string',
                default    => 'string',
            };
            if ($field['type'] === 'number') {
                if (isset($field['min'])) { $rule[] = 'min:' . $field['min']; }
                if (isset($field['max'])) { $rule[] = 'max:' . $field['max']; }
            } else {
                $rule[] = 'max:' . ($field['type'] === 'textarea' ? 2000 : 255);
            }
            if ($field['type'] === 'select') {
                $rule[] = \Illuminate\Validation\Rule::in($field['options']);
            }
            $rules[$key] = $rule;
        }

        return [$rules, $names];
    }

    /** Only the answers this type asks for, trimmed — anything else the browser sends is dropped */
    public static function cleanAnswers(string $slug, array $answers): array
    {
        $def   = self::find($slug) ?? self::all()['others'];
        $clean = [];
        foreach (self::askedFields($def) as $field) {
            if (!self::fieldApplies($field, $answers)) {
                continue;
            }
            $value = trim((string) ($answers[$field['key']] ?? ''));
            if ($value !== '') {
                $clean[$field['key']] = $value;
            }
        }

        return $clean;
    }

    /** Stored answers as label → value, in form order (for request views and the builder) */
    public static function describeAnswers(?string $type, array $answers): array
    {
        $lines = [];
        foreach (self::forType($type)['fields'] as $field) {
            $value = $answers[$field['key']] ?? null;
            if ($value !== null && $value !== '') {
                $lines[$field['label']] = (string) $value;
            }
        }

        return $lines;
    }

    /**
     * The specification lines a new quotation starts with: the capacity, everything the client told us,
     * then GMD's standard construction details for this type. The admin edits them in the builder.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function defaultSpecLines(?string $type, ?string $capacity, array $answers): array
    {
        $def   = self::forType($type);
        $lines = [];

        if ($capacity) {
            $lines[] = ['label' => $def['capacity']['label'], 'value' => $capacity];
        }
        // what the tank stores follows from its type (no longer asked) — an older request keeps the client's answer
        if (!empty($def['product']) && empty($answers['product_stored'])) {
            $lines[] = ['label' => 'Product Stored', 'value' => $def['product']];
        }
        foreach (self::describeAnswers($type, $answers) as $label => $value) {
            $lines[] = ['label' => $label, 'value' => $value];
        }
        foreach ($def['specs'] as [$label, $value]) {
            $lines[] = ['label' => $label, 'value' => $value];
        }

        return $lines;
    }

    /**
     * The default card image for a type (when the admin hasn't uploaded one in Settings): an older "Our Work"
     * portfolio photo whose tag/title matches the type, else the bundled photo, else null (the card shows its icon).
     */
    public static function imageFor(array $def, $portfolioItems): ?string
    {
        $needles = array_filter(array_merge([mb_strtolower($def['type'])], $def['keywords'] ?? []));

        foreach ($portfolioItems as $item) {
            if (empty($item->image_url)) {
                continue;
            }
            $haystack = mb_strtolower(($item->tag ?? '') . ' ' . ($item->title ?? ''));
            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($haystack, rtrim($needle, 's'))) {
                    return str_starts_with($item->image_url, 'http') ? $item->image_url : asset($item->image_url);
                }
            }
        }

        if (!empty($def['image']) && file_exists(public_path($def['image']))) {
            return asset($def['image']);
        }

        return null;
    }

    /**
     * Cards for the landing page and client portal, with the admin's edits from Settings (picture, badge,
     * description, order, visibility) applied over the catalog defaults. Hidden cards are left out unless
     * $includeHidden (the Settings page lists them all).
     */
    public static function cards($portfolioItems = null, bool $includeHidden = false): array
    {
        $portfolioItems ??= \App\Models\PortfolioItem::active()->orderBy('sort_order')->get();
        $edits = self::edits();
        $position = 0;

        $cards = array_map(function ($def) use ($portfolioItems, $edits, &$position) {
            $edit = $edits->get($def['slug']);
            $position++;

            return [
                'slug'          => $def['slug'],
                'type'          => $def['type'],
                'icon'          => $def['icon'],
                'blurb'         => $edit?->blurb ?: $def['blurb'],
                'badge'         => $edit?->badge ?: $def['capacity']['label'],
                'image'         => $edit?->image_url ?: self::imageFor($def, $portfolioItems),
                'custom_image'  => (bool) $edit?->image_url,
                'visible'       => $edit ? (bool) $edit->is_visible : true,
                'sort_order'    => $edit?->sort_order ?? $position,
                'order_set'     => $edit?->sort_order !== null,
                'custom'        => !empty($def['custom']),
                'capacity_label'=> $edit?->capacity_label,
                'default_blurb' => $def['blurb'],
                'default_badge' => $def['capacity']['label'],
                'capacity'      => $def['capacity'],
                'unit'          => $def['unit'],
                'fields'        => self::askedFields($def),
                'sizes'         => $def['sizes'] ?? [],
                'default_sizes' => $def['default_sizes'] ?? [],
                'sizes_edited'  => $edit && $edit->sizes !== null,
                'project_based' => $def['project_based'] ?? null,
                'choice'        => self::choice($def),
                'specs'         => array_map(fn ($s) => ['label' => $s[0], 'value' => $s[1]], $def['specs'] ?? []),
            ];
        }, array_filter(self::all(), fn ($def) => ($def['card'] ?? true) !== false));   // stored-only types have no card

        // by order; on a tie, a card the admin placed there comes before one that is there by default
        usort($cards, fn ($a, $b) => [$a['sort_order'], !$a['order_set']] <=> [$b['sort_order'], !$b['order_set']]);

        return array_values($includeHidden ? $cards : array_filter($cards, fn ($c) => $c['visible']));
    }

    /** Tank types clients can currently request (the visible cards) */
    public static function requestableSlugs(): array
    {
        return array_column(self::cards(), 'slug');
    }

    /**
     * The type a request from this card is saved as. Usually the card's own, but a combined card (Fuel Pipeline)
     * saves as the service the client picked, e.g. "Re-piping of Fuel Pipe Line".
     */
    public static function storedSlug(string $slug, array $answers): string
    {
        $def = self::find($slug);
        if (!empty($def['stores_as'])) {
            [$key, $map] = $def['stores_as'];
            return $map[$answers[$key] ?? ''] ?? $slug;
        }

        return $slug;
    }

    /**
     * A combined card's first question (e.g. Fuel Pipeline → Service Needed) as choice cards for the details
     * page: ['key', 'label', 'options' => [['value', 'icon', 'text'], …]], or null for an ordinary card.
     */
    private static function choice(array $def): ?array
    {
        if (empty($def['stores_as'])) {
            return null;
        }
        $key   = $def['stores_as'][0];
        $field = collect($def['fields'])->firstWhere('key', $key);

        return [
            'key'     => $key,
            'label'   => $field['label'],
            'options' => array_map(fn ($o) => ['value' => $o] + ($def['choice_help'][$o] ?? ['icon' => 'circle', 'text' => '']), $field['options']),
        ];
    }

    /** The choice exactly as offered on this card (e.g. "New installation"), or null when it isn't one */
    public static function validChoice(?string $slug, ?string $value): ?string
    {
        $def = self::find($slug);
        $choice = $def ? self::choice($def) : null;
        if (!$choice || $value === null) {
            return null;
        }

        return in_array($value, array_column($choice['options'], 'value'), true) ? $value : null;
    }

    /** The card a stored-only type is requested through (old links to it land there), or null */
    public static function mergedInto(?string $slug): ?string
    {
        return self::find($slug)['merged_into'] ?? null;
    }

    /**
     * GMD's estimated price for ONE unit of this tank type and capacity — the target the costing is compared with:
     *  - a standard size → its estimated price (e.g. Underground 16,000 liters → ₱210,000)
     *  - a priced-per-meter type (pipe lines) → the rate × the meters in the capacity ("120 meters" → 120 × ₱750)
     * Returns ['amount', 'basis'] or null when there is no estimate (a size the client typed, a card without prices).
     */
    public static function estimatedUnitPrice(?string $type, ?string $capacity): ?array
    {
        $def = self::forType($type);
        if (!$capacity || ($def['slug'] === 'others' && ProjectTankItem::canonicalType($type) !== 'Others')) {
            return null;
        }
        $digits = fn ($s) => preg_replace('/[^0-9.]/', '', (string) preg_replace('/(\d),(\d)/', '$1$2', (string) $s));

        // the card a stored-only type is requested through carries the sizes (none today, but kept general)
        $sizes = $def['sizes'] ?? [];
        foreach ($sizes as $size) {
            if (!empty($size['price']) && $digits($size['capacity']) !== '' && (float) $digits($size['capacity']) === (float) $digits($capacity)) {
                return ['amount' => (float) $size['price'], 'basis' => $size['capacity'] . ' standard size'];
            }
        }

        if (!empty($def['rate']) && preg_match('/(\d[\d,]*(?:\.\d+)?)/', $capacity, $m)) {
            [$rate, $per] = $def['rate'];
            $qty = (float) str_replace(',', '', $m[1]);
            if ($qty > 0) {
                return ['amount' => round($rate * $qty, 2), 'basis' => number_format($qty, $qty == (int) $qty ? 0 : 2) . ' × ₱' . number_format($rate) . ' per ' . $per];
            }
        }

        return null;
    }

    /** Sizes as saved in Settings, trimmed, without blank rows: [['capacity' => …, 'dimensions' => …], …] */
    public static function cleanSizes($sizes): array
    {
        $clean = [];
        foreach ((array) $sizes as $size) {
            $capacity = trim((string) ($size['capacity'] ?? ''));
            $price    = str_replace([',', ' ', '₱'], '', (string) ($size['price'] ?? ''));
            if ($capacity !== '') {
                $clean[] = [
                    'capacity'   => $capacity,
                    'dimensions' => trim((string) ($size['dimensions'] ?? '')),
                    'price'      => is_numeric($price) && (float) $price > 0 ? round((float) $price, 2) : null,
                ];
            }
        }

        return $clean;
    }

    /** The visible card for a slug (what the tank's details page shows), or null */
    public static function card(?string $slug): ?array
    {
        foreach (self::cards() as $card) {
            if ($card['slug'] === $slug) {
                return $card;
            }
        }

        return null;
    }

    /** The standard capacity exactly as listed for this type, or null when it isn't one of them */
    public static function standardCapacity(?string $slug, ?string $capacity): ?string
    {
        return self::standardSize($slug, $capacity)['capacity'] ?? null;
    }

    /** The standard size ['capacity', 'dimensions'] matching this capacity exactly, or null for a size the client typed */
    public static function standardSize(?string $slug, ?string $capacity): ?array
    {
        $def = self::find($slug);
        foreach ($def['sizes'] ?? [] as $size) {
            if ($capacity !== null && $size['capacity'] === trim($capacity)) {
                return $size;
            }
        }

        return null;
    }
}
