<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\MaterialUsage;
use App\Models\MaterialRequest;
use App\Models\FundSetting;
use App\Models\Employee;
use App\Services\NotificationService;

class MaterialUsageController extends Controller
{
    // -----------------------------------------------------------------------
    // Admin
    // -----------------------------------------------------------------------

    public function adminIndex()
    {
        $projects = Project::with('activeMaterials', 'activeMaterialUsages')
            ->orderBy('created_at', 'desc')
            ->get();

        $requests = MaterialRequest::with(['project', 'projectMaterial', 'requestedBy'])
            ->orderByDesc('requested_date')
            ->orderByDesc('id')
            ->get();

        $pendingCount   = $requests->where('status', 'pending')->count();
        $fulfilledCount = $requests->where('status', 'fulfilled')->count();
        $shortageCount  = $requests->where('status', 'shortage')->count();

        $fundBalance = FundSetting::getCurrentBalance();

        return view('admin.material_usage', compact(
            'projects',
            'requests',
            'pendingCount',
            'fulfilledCount',
            'shortageCount',
            'fundBalance'
        ));
    }

    public function adminDetail($projectId)
    {
        return view('admin.material_usage_detail', $this->buildDetailData($projectId));
    }

    public function store(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        $this->createUsageEntry($request, $project, auth()->user()->name ?? 'Admin');

        return redirect()
            ->route('admin.material_usage.detail', $project->id)
            ->with('success', 'Material usage logged successfully.');
    }

    public function archive($projectId, $usageId)
    {
        $entry = MaterialUsage::where('project_id', $projectId)->findOrFail($usageId);
        $entry->status = $entry->status === 'archived' ? 'active' : 'archived';
        $entry->save();

        $label = $entry->status === 'archived' ? 'archived' : 'restored';

        return redirect()
            ->route('admin.material_usage.detail', $projectId)
            ->with('success', "Usage entry for \"{$entry->material_name}\" {$label} successfully.");
    }

    // -----------------------------------------------------------------------
    // Employee
    // -----------------------------------------------------------------------

    public function employeeDetail($projectId)
    {
        return view('employee.material_usage_detail', $this->buildDetailData($projectId));
    }

    public function employeeStore(Request $request, $projectId)
    {
        $project  = Project::findOrFail($projectId);
        $employee = Employee::find(session('user_id'));

        $this->createUsageEntry(
            $request,
            $project,
            $employee?->full_name ?? 'Employee',
            $employee?->full_name
        );

        return redirect()
            ->route('employee.material_usage.detail', $project->id)
            ->with('success', 'Material usage logged successfully.');
    }

    // -----------------------------------------------------------------------
    // Shared helpers
    // -----------------------------------------------------------------------

    private function buildDetailData($projectId): array
    {
        $project = Project::findOrFail($projectId);

        $plannedMaterials = $project->activeMaterials;

        $usageEntries = MaterialUsage::where('project_id', $projectId)
            ->orderBy('used_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $activeUsage = $usageEntries->where('status', 'active');

        $materialComparison = $plannedMaterials->map(function ($material) use ($activeUsage) {
            $usedQty = $activeUsage->where('project_material_id', $material->id)->sum('quantity_used');
            $remaining = $material->quantity - $usedQty;

            if ($usedQty <= 0) {
                $statusKey = 'pending';
            } elseif ($usedQty < $material->quantity) {
                $statusKey = 'ongoing';
            } elseif ($usedQty == $material->quantity) {
                $statusKey = 'completed';
            } else {
                $statusKey = 'shortage';
            }

            return [
                'material'  => $material,
                'usedQty'   => $usedQty,
                'remaining' => $remaining,
                'statusKey' => $statusKey,
            ];
        });

        $totalPlanned = $plannedMaterials->count();
        $totalLogged  = $activeUsage->count();
        $totalQtyUsed = $activeUsage->sum('quantity_used');

        return compact(
            'project', 'plannedMaterials', 'usageEntries', 'materialComparison',
            'totalPlanned', 'totalLogged', 'totalQtyUsed'
        );
    }

    private function createUsageEntry(Request $request, Project $project, string $recordedBy, ?string $loggedBy = null): MaterialUsage
    {
        $request->validate([
            'project_material_id' => 'nullable|exists:project_materials,id',
            'material_name'       => 'required|string|max:255',
            'quantity_used'       => 'required|numeric|min:0.01',
            'unit'                => 'nullable|string|max:50',
            'used_date'           => 'required|date',
            'used_for'            => 'nullable|string|max:50',
            'notes'               => 'nullable|string',
        ]);

        $usage = MaterialUsage::create([
            'project_id'          => $project->id,
            'project_material_id' => $request->input('project_material_id') ?: null,
            'material_name'       => $request->input('material_name'),
            'quantity_used'       => $request->input('quantity_used'),
            'unit'                => $request->input('unit'),
            'used_date'           => $request->input('used_date'),
            'used_for'            => $request->input('used_for'),
            'notes'               => $request->input('notes'),
            'recorded_by'         => $recordedBy,
            'status'              => 'active',
        ]);

        NotificationService::materialUsageLogged(
            $project,
            $request->input('material_name'),
            (float) $request->input('quantity_used'),
            $loggedBy
        );

        return $usage;
    }
}
