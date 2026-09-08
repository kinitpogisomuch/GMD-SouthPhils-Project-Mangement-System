<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PerformanceTestReport;
use App\Models\Project;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;

class PerformanceTestReportController extends Controller
{
    protected $storage;

    public function __construct(SupabaseStorageService $storage)
    {
        $this->storage = $storage;
    }

    public function store(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        $validated = $request->validate([
            'client_name'                  => 'nullable|string|max:255',
            'project_location'             => 'nullable|string|max:500',
            'subject'                      => 'nullable|string|max:255',
            'report_date'                  => 'required|date',
            'test_items'                   => 'required|array|min:1',
            'test_items.*.item'            => 'nullable|string|max:100',
            'test_items.*.tank_capacity'   => 'nullable|string|max:100',
            'test_items.*.applied_pressure'=> 'nullable|string|max:100',
            'test_items.*.tested_at'       => 'nullable|string|max:255',
            'test_items.*.hours_observed'  => 'nullable|string|max:100',
            'test_items.*.remarks'         => 'nullable|string|max:255',
            'conducted_by_name'            => 'nullable|string|max:255',
            'conducted_by_role'            => 'nullable|string|max:255',
            'noted_by_name'                => 'nullable|string|max:255',
            'noted_by_role'                => 'nullable|string|max:255',
            'test_photos'                  => 'nullable|array|max:10',
            'test_photos.*'                => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $photoUrls = $this->storage->uploadMultiple(
            $request->file('test_photos', []),
            'performance-test-reports/' . $project->id
        );

        $report = $project->performanceTestReports()->create([
            'client_name'       => $validated['client_name'] ?? $project->client,
            'project_location'  => $validated['project_location'] ?? $project->address,
            'subject'           => $validated['subject'] ?? null,
            'report_date'       => $validated['report_date'],
            'test_items'        => array_values($validated['test_items']),
            'test_photos'       => !empty($photoUrls) ? $photoUrls : null,
            'conducted_by_name' => $validated['conducted_by_name'] ?? null,
            'conducted_by_role' => $validated['conducted_by_role'] ?? null,
            'noted_by_name'     => $validated['noted_by_name'] ?? null,
            'noted_by_role'     => $validated['noted_by_role'] ?? null,
        ]);

        return redirect()->route('admin.performance_test_reports.show', [$project->id, $report->id])
            ->with('success', 'Performance test report generated.');
    }

    public function show($projectId, $reportId)
    {
        $project = Project::findOrFail($projectId);
        $report  = $project->performanceTestReports()->findOrFail($reportId);

        return view('admin.performance_test_report', compact('project', 'report'));
    }

    public function clientShow($projectId, $reportId)
    {
        $clientEmail = session('email');
        $clientName  = $clientEmail
            ? Client::where('email', $clientEmail)->value('name')
            : null;

        $project = Project::findOrFail($projectId);

        if (!$clientName || $project->client !== $clientName) {
            abort(403);
        }

        $report = $project->performanceTestReports()->findOrFail($reportId);

        return view('client.performance_test_report', compact('project', 'report'));
    }
}
