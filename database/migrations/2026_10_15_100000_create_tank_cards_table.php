<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The landing page's "Our Work" tank cards (Choose Your Tank, Get a Quotation), as edited by the admin in
     * Settings: picture, badge, description, order and visibility. One row per tank type (slug from
     * App\Support\TankCatalog); a type without a row — or a blank field — uses the catalog's default.
     */
    public function up(): void
    {
        Schema::create('tank_cards', function (Blueprint $table) {
            $table->string('slug', 80)->primary();
            $table->text('image_url')->nullable();
            $table->string('badge', 50)->nullable();
            $table->string('blurb', 500)->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tank_cards');
    }
};
