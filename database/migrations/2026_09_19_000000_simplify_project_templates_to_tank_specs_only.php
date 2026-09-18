<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Templates exist purely to reuse tank/project specs on a new project — the
     * materials, labor, markup, and payment terms all belong to that specific
     * project's own Build Quotation, and don't make sense to carry into an
     * unrelated new one, so they don't belong on the template at all.
     */
    public function up(): void
    {
        Schema::table('project_templates', function (Blueprint $table) {
            $table->dropColumn(['materials', 'labor', 'estimated_working_days', 'markup', 'payment_term_type']);
        });
    }

    public function down(): void
    {
        Schema::table('project_templates', function (Blueprint $table) {
            $table->json('materials')->nullable();
            $table->json('labor')->nullable();
            $table->unsignedInteger('estimated_working_days')->nullable();
            $table->decimal('markup', 15, 2)->nullable();
            $table->string('payment_term_type')->nullable();
        });
    }
};
