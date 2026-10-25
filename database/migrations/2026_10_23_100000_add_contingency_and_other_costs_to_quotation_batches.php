<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Costing additions:
     *  - contingency_percent: an allowance for the unforeseen, as a % of the direct cost (materials + labor + others)
     *  - onsite_assembly + other_costs: when GMD assembles the tank on site, the job's other costs
     *    (mobilization, boarding / lodging, PPE, shop drawing, deliveries…) as [['label' => …, 'amount' => …], …].
     *    They are for the whole job, so they are NOT multiplied by the tank quantity.
     */
    public function up(): void
    {
        Schema::table('quotation_batches', function (Blueprint $table) {
            $table->decimal('contingency_percent', 5, 2)->nullable();
            $table->boolean('onsite_assembly')->default(false);
            $table->json('other_costs')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quotation_batches', function (Blueprint $table) {
            $table->dropColumn(['contingency_percent', 'onsite_assembly', 'other_costs']);
        });
    }
};
