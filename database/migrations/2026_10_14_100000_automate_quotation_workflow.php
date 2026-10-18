<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Automated quotation workflow:
     *  - quotation_requests.specifications: the type-specific answers the client gave on the tank form
     *    (wall type, product stored, pipe diameter, …); statuses gain "in_preparation" (admin is building
     *    the quotation) and "rejected" (client turned the quotation down for good).
     *  - quotation_batches: a unique reference number (GMDSP-2026-061), the specification lines and
     *    document settings printed on the generated quotation, and the project it was converted into.
     *  - quotation_versions: every quotation sent to the client, frozen as a snapshot, so a revision never
     *    overwrites what the client saw before and each approval points at one exact version.
     *  - clients.pending_quote_tank: the tank a visitor picked on the landing page before signing up, kept
     *    until their first login (sign-up → approval → setup can take days and happen on another device).
     *  - audit_logs: who did what to quotations, payments and project exceptions.
     */
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->json('specifications')->nullable();
        });
        DB::statement('ALTER TABLE quotation_requests DROP CONSTRAINT IF EXISTS quotation_requests_status_check');
        DB::statement("ALTER TABLE quotation_requests ADD CONSTRAINT quotation_requests_status_check CHECK (status IN ('pending', 'in_preparation', 'quotation_sent', 'approved', 'rejected', 'converted', 'declined'))");

        Schema::table('quotation_batches', function (Blueprint $table) {
            $table->string('reference_no', 40)->nullable()->unique();
            $table->json('specifications')->nullable();
            $table->json('document')->nullable();
            $table->unsignedInteger('current_version')->default(0);
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->timestamp('converted_at')->nullable();
        });

        Schema::create('quotation_versions', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_batch_id')->index();
            $table->unsignedInteger('version');
            $table->string('quotation_number', 60);
            // sent → (approved | revision_requested | rejected), or superseded once a newer version is sent
            $table->string('status', 30)->default('sent');
            $table->json('snapshot');
            $table->decimal('contract_value', 14, 2)->default(0);
            $table->string('pdf_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('sent_by')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('response_reason')->nullable();
            $table->timestamps();

            $table->unique(['quotation_batch_id', 'version']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('pending_quote_tank', 80)->nullable();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type', 60);
            $table->string('auditable_id', 64);
            $table->string('action', 80);
            $table->string('actor_type', 20)->nullable();   // admin | client | employee | system | provider
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->json('details')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
        });

        // Every request already belongs to a batch id; make sure each has its batch row, then number them
        // in the order they were submitted (GMDSP-<year>-<3-digit sequence per year>).
        $missing = DB::table('quotation_requests')
            ->select('batch_id', DB::raw('MIN(client_id) AS client_id'), DB::raw('MIN(created_at) AS created_at'))
            ->whereNotNull('batch_id')
            ->whereNotIn('batch_id', DB::table('quotation_batches')->select('id'))
            ->groupBy('batch_id')
            ->get();
        foreach ($missing as $row) {
            DB::table('quotation_batches')->insert([
                'id'         => $row->batch_id,
                'client_id'  => $row->client_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->created_at,
            ]);
        }

        $sequence = [];
        $batches  = DB::table('quotation_batches')->orderBy('created_at')->orderBy('id')->get(['id', 'created_at']);
        foreach ($batches as $batch) {
            $year = $batch->created_at ? substr((string) $batch->created_at, 0, 4) : date('Y');
            $sequence[$year] = ($sequence[$year] ?? 0) + 1;
            DB::table('quotation_batches')->where('id', $batch->id)->update([
                'reference_no' => sprintf('GMDSP-%s-%03d', $year, $sequence[$year]),
            ]);
        }

        // Converted requests already know their project — copy it onto the batch.
        DB::statement("
            UPDATE quotation_batches b SET project_id = q.related_project_id
            FROM (SELECT batch_id, MAX(related_project_id) AS related_project_id FROM quotation_requests
                  WHERE related_project_id IS NOT NULL GROUP BY batch_id) q
            WHERE q.batch_id = b.id AND b.project_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('pending_quote_tank');
        });

        Schema::dropIfExists('quotation_versions');

        Schema::table('quotation_batches', function (Blueprint $table) {
            $table->dropUnique(['reference_no']);
            $table->dropColumn(['reference_no', 'specifications', 'document', 'current_version', 'project_id', 'converted_at']);
        });

        DB::statement("UPDATE quotation_requests SET status = 'pending' WHERE status = 'in_preparation'");
        DB::statement("UPDATE quotation_requests SET status = 'declined' WHERE status = 'rejected'");
        DB::statement('ALTER TABLE quotation_requests DROP CONSTRAINT IF EXISTS quotation_requests_status_check');
        DB::statement("ALTER TABLE quotation_requests ADD CONSTRAINT quotation_requests_status_check CHECK (status IN ('pending', 'quotation_sent', 'approved', 'converted', 'declined'))");

        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropColumn('specifications');
        });
    }
};
