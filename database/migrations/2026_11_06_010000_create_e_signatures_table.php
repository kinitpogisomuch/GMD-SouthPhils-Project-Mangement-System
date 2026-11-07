<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every uploaded e-signature, by where it signs (quotation, billing_prepared_by, billing_approved_by).
        // The most recent upload is the default for the next document — set the moment it's uploaded.
        Schema::create('e_signatures', function (Blueprint $table) {
            $table->id();
            $table->string('context', 40);
            $table->string('url', 1000);
            $table->string('uploaded_by')->nullable();
            $table->timestamps();
            $table->index(['context', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_signatures');
    }
};
