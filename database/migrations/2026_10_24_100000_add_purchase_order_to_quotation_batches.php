<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The client's Purchase Order: after approving the quotation the client sends GMD their PO (number + file).
     * Only once it is received can the quotation be converted into a project — which starts the down payment.
     */
    public function up(): void
    {
        Schema::table('quotation_batches', function (Blueprint $table) {
            $table->string('po_number', 80)->nullable();
            $table->json('po_files')->nullable();
            $table->text('po_notes')->nullable();
            $table->timestamp('po_submitted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quotation_batches', function (Blueprint $table) {
            $table->dropColumn(['po_number', 'po_files', 'po_notes', 'po_submitted_at']);
        });
    }
};
