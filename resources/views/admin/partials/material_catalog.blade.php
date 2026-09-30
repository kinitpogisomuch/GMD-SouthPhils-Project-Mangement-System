{{-- Shared materials catalog — single source of truth used by both the Project Materials (BOM)
     page and the Add Project / Project Template materials picker. --}}
<script>
    var MATERIAL_CATALOG = {
        'Steel & Metal Stock':  [
            'MS Plates - 6mm x 4ft. x 8ft.',
            'MS Plates - 6mm x 4ft. x 20ft.',
            'MS Plates - 8mm x 4ft. x 8ft.',
            'MS Plates - 9mm x 4ft. x 8ft.',
            'MS Plates - 9mm x 4ft. x 20ft.',
            'MS Plates - 10mm x 4ft. x 8ft.',
            'Angular Bar - 2"x 6mm thick',
            'Wide Flange - 4 x 4'
        ],
        'Welding Supplies':     [
            'Electrode - 3.2mm (6011)',
            'Electrode - 3.2mm (7018)',
            'Electrode - 2.5mm (6011)',
            'Electrode - 2.5mm (7018)',
            'Welding Gloves'
        ],
        'Cutting & Grinding':   ['Grinding Disc #4','Grinding Disc #5','Grinding Disc #7','Cutting Disc #4','Cutting Disc #5','Cutting Disc #7'],
        'Gas & Fuel':           ['Industrial Oxygen','Acetylene'],
        'Paint & Coating':      ['Epoxy Primer Gray','QDE Medium Gray','Lacquer Thinner','Paint Thinner','Polituff Putty'],
        'Brushes & Tools':      ['Paint Brush','Roller Brush'],
        'Safety & PPE':         ['Dark Glass #11','Clear Glass','Cotton Gloves'],
        'Abrasives':            ['Sanding Paper #60','Sanding Paper #100'],
        'Inspection & Testing': ['Penetrant Dye Spray','Pressure Test Kits','Pressure Gauge 60PSI']
    };

    var MATERIAL_UNITS = {
        'MS Plates - 6mm x 4ft. x 8ft.': 'pcs',
        'MS Plates - 6mm x 4ft. x 20ft.': 'pcs',
        'MS Plates - 8mm x 4ft. x 8ft.': 'pcs',
        'MS Plates - 9mm x 4ft. x 8ft.': 'pcs',
        'MS Plates - 9mm x 4ft. x 20ft.': 'pcs',
        'MS Plates - 10mm x 4ft. x 8ft.': 'pcs',
        'Angular Bar - 2"x 6mm thick': 'pcs',
        'Wide Flange - 4 x 4': 'pcs',
        'Electrode - 3.2mm (6011)': 'kilos', 'Electrode - 3.2mm (7018)': 'kilos',
        'Electrode - 2.5mm (6011)': 'kilos', 'Electrode - 2.5mm (7018)': 'kilos',
        'Welding Gloves': 'pcs',
        'Grinding Disc #4': 'pcs', 'Grinding Disc #5': 'pcs', 'Grinding Disc #7': 'pcs',
        'Cutting Disc #4': 'pcs', 'Cutting Disc #5': 'pcs', 'Cutting Disc #7': 'pcs',
        'Industrial Oxygen': 'cylinders', 'Acetylene': 'cylinders',
        'Epoxy Primer Gray': 'galons', 'QDE Medium Gray': 'galons',
        'Lacquer Thinner': 'galons', 'Paint Thinner': 'galons', 'Polituff Putty': 'galons',
        'Paint Brush': 'pcs', 'Roller Brush': 'pcs',
        'Dark Glass #11': 'pcs', 'Clear Glass': 'pcs', 'Cotton Gloves': 'pcs',
        'Sanding Paper #60': 'pcs', 'Sanding Paper #100': 'pcs',
        'Penetrant Dye Spray': 'pairs', 'Pressure Test Kits': 'set', 'Pressure Gauge 60PSI': 'pcs'
    };

    // Materials the admin typed in that are not on the list above join the catalog automatically:
    // each goes under the type its name points to (e.g. anything with "Plate" or "Flange" is
    // Steel & Metal Stock), or under "Others" when the name gives no clue.
    (function () {
        var custom = @json(\App\Models\ProjectMaterial::query()->select('material_name', 'unit')->whereNotNull('material_name')->distinct()->orderBy('material_name')->get());

        var TYPE_KEYWORDS = [
            ['Inspection & Testing', ['penetrant', 'pressure', 'gauge', 'test']],
            ['Abrasives',            ['sanding', 'sandpaper', 'sand paper', 'abrasive']],
            ['Cutting & Grinding',   ['grinding', 'cutting', 'disc', 'disk', 'blade']],
            ['Welding Supplies',     ['electrode', 'welding', 'weld', 'flux']],
            ['Gas & Fuel',           ['oxygen', 'acetylene', 'gas', 'lpg', 'argon', 'diesel', 'gasoline', 'fuel']],
            ['Brushes & Tools',      ['brush', 'roller']],
            ['Paint & Coating',      ['paint', 'primer', 'thinner', 'putty', 'epoxy', 'enamel', 'qde', 'lacquer', 'coating']],
            ['Safety & PPE',         ['glove', 'glass', 'goggle', 'mask', 'helmet', 'apron', 'ppe']],
            ['Steel & Metal Stock',  ['plate', 'bar', 'flange', 'beam', 'pipe', 'tube', 'tubing', 'channel', 'angle', 'sheet', 'steel', 'rod', 'purlin', 'elbow', 'bolt', 'nut']]
        ];

        function guessType(name) {
            var lower = name.toLowerCase();
            for (var i = 0; i < TYPE_KEYWORDS.length; i++) {
                for (var k = 0; k < TYPE_KEYWORDS[i][1].length; k++) {
                    // whole words only (plurals allowed), so "Gasket" is not read as "gas"
                    if (new RegExp('\\b' + TYPE_KEYWORDS[i][1][k] + '(s|es)?\\b').test(lower)) return TYPE_KEYWORDS[i][0];
                }
            }
            return 'Others';
        }

        var known = {};
        Object.keys(MATERIAL_CATALOG).forEach(function (type) {
            MATERIAL_CATALOG[type].forEach(function (name) { known[name.toLowerCase()] = true; });
        });

        var others = [];
        custom.forEach(function (row) {
            var name = (row.material_name || '').trim();
            if (!name || known[name.toLowerCase()]) return;
            known[name.toLowerCase()] = true;
            var type = guessType(name);
            if (type === 'Others') others.push(name); else MATERIAL_CATALOG[type].push(name);
            if (row.unit) MATERIAL_UNITS[name] = row.unit;
        });
        // added last so "Others" always sits at the bottom of the list
        if (others.length) MATERIAL_CATALOG['Others'] = others;
    })();
</script>
