<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_role_check');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE employees ADD CONSTRAINT employees_role_check CHECK (role::text = ANY (ARRAY['Fabricator', 'Welder', 'Helper/Labor', 'Outsourced']::text[]))");
    }
};
