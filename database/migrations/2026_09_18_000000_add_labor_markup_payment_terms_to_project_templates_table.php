<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_templates', function (Blueprint $table) {
            // Snapshotted from the source project once it's completed, alongside the
            // tank_items/materials it already carries — so reusing a template also
            // carries over its labor cost, markup, and payment terms.
            $table->json('labor')->nullable()->after('materials');
            $table->unsignedInteger('estimated_working_days')->nullable()->after('labor');
            $table->decimal('markup', 15, 2)->nullable()->after('estimated_working_days');
            $table->string('payment_term_type')->nullable()->after('markup');
        });
    }

    public function down(): void
    {
        Schema::table('project_templates', function (Blueprint $table) {
            $table->dropColumn(['labor', 'estimated_working_days', 'markup', 'payment_term_type']);
        });
    }
};
