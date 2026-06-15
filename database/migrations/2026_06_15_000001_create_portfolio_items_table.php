<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->string('image_url')->nullable();
            $table->string('icon')->nullable();
            $table->string('spec');
            $table->string('tag');
            $table->string('title');
            $table->text('description');
            $table->integer('sort_order')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });

        $now = now();

        DB::table('portfolio_items')->insert([
            [
                'image_url'   => 'images/portfolio/fuel-tank-1.jpg',
                'icon'        => 'flame',
                'spec'        => '10,000 L',
                'tag'         => 'Fuel Storage',
                'title'       => 'Diesel Storage Tank — Distribution Depot',
                'description' => 'Cylindrical carbon steel tank with internal baffles, fitted with vents, gauges, and dike containment for a fuel distribution site.',
                'sort_order'  => 0,
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'image_url'   => null,
                'icon'        => 'droplets',
                'spec'        => '5,000 L',
                'tag'         => 'Water Storage',
                'title'       => 'Elevated Water Tank — Municipal Supply',
                'description' => 'Steel elevated water reservoir with support tower, designed for consistent pressure delivery to a community water system.',
                'sort_order'  => 1,
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'image_url'   => null,
                'icon'        => 'wheat',
                'spec'        => 'Food-grade',
                'tag'         => 'Cooking Oil Tank',
                'title'       => 'Stainless Storage Vessel — Food Plant',
                'description' => 'Hygiene-compliant stainless interior with polished welds, built for edible oil storage in a food manufacturing facility.',
                'sort_order'  => 2,
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'image_url'   => null,
                'icon'        => 'flask-conical',
                'spec'        => 'Corrosion-resist.',
                'tag'         => 'Chemical Storage',
                'title'       => 'Acid-Resistant Tank — Chemical Plant',
                'description' => 'Reinforced shell with corrosion-resistant lining and secondary containment, fabricated for industrial chemical storage.',
                'sort_order'  => 3,
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'image_url'   => null,
                'icon'        => 'flame-kindling',
                'spec'        => 'Pressurized',
                'tag'         => 'LPG / Gas Vessel',
                'title'       => 'Pressure Vessel — LPG Storage',
                'description' => 'ASME-style pressure vessel with full weld inspection and pneumatic pressure testing prior to client handover.',
                'sort_order'  => 4,
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'image_url'   => null,
                'icon'        => 'container',
                'spec'        => 'Custom',
                'tag'         => 'Custom Fabrication',
                'title'       => 'Custom Tank — On-Site Installation',
                'description' => 'Engineered to a client\'s exact dimensions and capacity, fabricated in our shop and delivered with on-site installation support.',
                'sort_order'  => 5,
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_items');
    }
};
