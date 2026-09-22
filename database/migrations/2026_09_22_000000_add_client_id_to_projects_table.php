<?php

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('client')
                ->constrained('clients')->nullOnDelete();
        });

        // Backfill: match each project to its real client row so admin/employee
        // views can read live client info instead of the frozen snapshot columns.
        // Prefer matching by email (more reliable), fall back to name.
        Project::whereNull('client_id')->get()->each(function (Project $project) {
            $client = null;

            if ($project->email) {
                $client = Client::whereRaw('LOWER(email) = LOWER(?)', [trim($project->email)])->first();
            }

            if (!$client && $project->client) {
                $client = Client::whereRaw('LOWER(name) = LOWER(?)', [trim($project->client)])->first();
            }

            if ($client) {
                $project->forceFill(['client_id' => $client->id])->save();
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
