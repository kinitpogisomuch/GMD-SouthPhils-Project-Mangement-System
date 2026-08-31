{{-- Shared materials catalog — single source of truth used by both the Project Materials (BOM)
     page and the Add Project / Project Template materials picker. --}}
<script>
    var MATERIAL_CATALOG = {
        'Steel & Metal Stock':  ['MS Plates','Angular Bar'],
        'Welding Supplies':     ['Electrode (6011)','Electrode (7018)','Welding Gloves'],
        'Cutting & Grinding':   ['Grinding Disc #4','Grinding Disc #5','Grinding Disc #7','Cutting Disc #4','Cutting Disc #5','Cutting Disc #7'],
        'Gas & Fuel':           ['Industrial Oxygen','Acetylene'],
        'Paint & Coating':      ['Epoxy Primer Gray','QDE Medium Gray','Lacquer Thinner','Paint Thinner','Polituff Putty'],
        'Brushes & Tools':      ['Paint Brush','Roller Brush'],
        'Safety & PPE':         ['Dark Glass #11','Clear Glass','Cotton Gloves'],
        'Abrasives':            ['Sanding Paper #60','Sanding Paper #100'],
        'Inspection & Testing': ['Penetrant Dye Spray','Pressure Test Kits','Pressure Gauge 60PSI']
    };

    var MATERIAL_UNITS = {
        'MS Plates': 'pcs', 'Angular Bar': 'pcs',
        'Electrode (6011)': 'kilos', 'Electrode (7018)': 'kilos', 'Welding Gloves': 'pcs',
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
</script>
