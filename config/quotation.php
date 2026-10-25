<?php

/*
 | The fixed parts of GMD's generated quotation (letterhead, signatory, standard terms). Everything that
 | varies per quotation — client, tank, specifications, costs — comes from the quotation's own data.
 */
return [

    'company' => [
        'name'     => 'GMD South Phils Metal Fabrication Works',
        'address'  => 'National Hi-way, Brgy. Masiit, Calauan, Laguna',
        'tin'      => '279-809-827-000',
        'dti'      => '1019791',
        'email'    => env('QUOTATION_COMPANY_EMAIL', 'gmdsouthphils@gmail.com'),
        'phone'    => env('QUOTATION_COMPANY_PHONE', ''),
        'tagline'  => env('QUOTATION_COMPANY_TAGLINE', '2025 GoldenGlobe Best Leading Steel Storage Tank Fabricator'),
        'shop'     => 'Calauan, Laguna Fabrication Shop',
    ],

    // The person who signs the quotation (editable per quotation in the builder)
    'signatory' => [
        'name'  => env('QUOTATION_SIGNATORY_NAME', 'Vince Duetiz'),
        'title' => env('QUOTATION_SIGNATORY_TITLE', 'Operation Manager'),
    ],

    // Days a quotation stays valid after it is sent
    'validity_days' => (int) env('QUOTATION_VALIDITY_DAYS', 30),

    'payment_terms' => [
        'big_project'   => '50% down payment, 30% progress billing, 20% full payment upon pull-out / delivery of tank.',
        'small_project' => '50% down payment, 50% full payment upon pull-out / delivery of tank.',
    ],

    'warranty' => 'Six (6) months warranty on the tank\'s workmanship under normal operating conditions. Not intended for storage of corrosive chemicals.',

    // General notes printed after the validity and warranty lines
    'notes' => [
        'All tanks are shop assembled and fabricated. Shop drawings may be provided upon receipt of the Purchase Order. Test reports / certificates are provided upon completion. Any job / division not mentioned above for tank specification, valves, fittings and other appurtenances shall be subject to variation.',
    ],

    'closing' => 'Thank you for the trust, and we look forward to your valued order.',
];
