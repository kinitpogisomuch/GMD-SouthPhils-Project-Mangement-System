<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->uuid('batch_id')->nullable()->after('client_id')->index();
            $table->string('tank_type')->nullable()->after('batch_id');
            $table->string('capacity')->nullable()->after('tank_type');
            $table->unsignedInteger('quantity')->default(1)->after('capacity');
            $table->string('target_timeline')->nullable()->after('quantity');
        });

        // Each existing request may hold several tanks in its tank_items JSON list.
        // Split those into one row per tank, tagging siblings with a shared batch_id
        // so the admin/client UI can still show them as "submitted together" while
        // every tank keeps its own independent status going forward.
        $rows = DB::table('quotation_requests')->get();

        foreach ($rows as $row) {
            $items = json_decode($row->tank_items ?? '[]', true) ?: [];
            $files = json_decode($row->quotation_files ?? 'null', true);
            $batchId = (string) Str::uuid();

            if (empty($items)) {
                DB::table('quotation_requests')->where('id', $row->id)->update(['batch_id' => $batchId]);
                continue;
            }

            foreach ($items as $index => $item) {
                $itemFiles = is_array($files) ? ($files[$index] ?? []) : [];

                $attrs = [
                    'batch_id'        => $batchId,
                    'tank_type'       => $item['tank_type'] ?? 'Others',
                    'capacity'        => $item['capacity'] ?? null,
                    'quantity'        => $item['quantity'] ?? 1,
                    'target_timeline' => $item['target_timeline'] ?? null,
                    'quotation_files' => !empty($itemFiles) ? json_encode($itemFiles) : null,
                ];

                if ($index === 0) {
                    DB::table('quotation_requests')->where('id', $row->id)->update($attrs);
                } else {
                    DB::table('quotation_requests')->insert(array_merge($attrs, [
                        'client_id'          => $row->client_id,
                        'location'           => $row->location,
                        'notes'              => $row->notes,
                        'status'             => $row->status,
                        'quotation_sent_at'  => $row->quotation_sent_at,
                        'approved_at'        => $row->approved_at,
                        'related_project_id' => $row->related_project_id,
                        'decline_reason'     => $row->decline_reason,
                        'created_at'         => $row->created_at,
                        'updated_at'         => $row->updated_at,
                    ]));
                }
            }
        }

        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropColumn('tank_items');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->json('tank_items')->nullable()->after('client_id');
        });

        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropColumn(['batch_id', 'tank_type', 'capacity', 'quantity', 'target_timeline']);
        });
    }
};
