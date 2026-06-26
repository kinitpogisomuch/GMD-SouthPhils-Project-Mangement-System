<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\MaterialUsage;
use App\Models\MaterialRequest;
use App\Models\FundSetting;
use App\Models\Employee;
use App\Models\SupplierContact;
use App\Models\MaterialPurchase;
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

        $suppliers = SupplierContact::orderBy('name')->get();

        return view('admin.material_usage', compact('projects', 'suppliers'));
    }

    public function adminDetail($projectId)
    {
        $data = $this->buildDetailData($projectId);

        $purchases = MaterialPurchase::where('project_id', $projectId)
            ->with('projectMaterial')
            ->orderByDesc('purchase_date')
            ->get();

        $data['purchases']      = $purchases;
        $data['totalPurchased'] = $purchases->sum('total_paid');
        $data['materialFactor'] = $data['project']->activeMaterials->first()?->factor ?? 7;
        $data['suppliers']      = SupplierContact::orderBy('name')->get();

        return view('admin.material_usage_detail', $data);
    }

    public function store(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        $this->createUsageEntry($request, $project, auth()->user()->name ?? 'Admin');

        return redirect()
            ->route('admin.material_usage.detail', $project->id)
            ->with('success', 'Material usage logged successfully.');
    }

    public function storePurchase(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        // strip the "__other__" sentinel before validation
        if ($request->input('project_material_id') === '__other__') {
            $request->merge(['project_material_id' => null]);
        }

        $validated = $request->validate([
            'project_material_id' => 'nullable|exists:project_materials,id',
            'material_name'       => 'required|string|max:255',
            'unit'                => 'nullable|string|max:50',
            'qty_bought'          => 'required|numeric|min:0.01',
            'actual_unit_cost'    => 'required|numeric|min:0',
            'supplier'            => 'nullable|string|max:255',
            'purchase_date'       => 'required|date',
            'notes'               => 'nullable|string|max:500',
        ]);

        MaterialPurchase::create([
            'project_id'          => $project->id,
            'project_material_id' => $validated['project_material_id'] ?? null,
            'material_name'       => $validated['material_name'],
            'unit'                => $validated['unit'] ?? null,
            'qty_bought'          => $validated['qty_bought'],
            'actual_unit_cost'    => $validated['actual_unit_cost'],
            'total_paid'          => round($validated['qty_bought'] * $validated['actual_unit_cost'], 2),
            'supplier'            => $validated['supplier'] ?? null,
            'purchase_date'       => $validated['purchase_date'],
            'notes'               => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.material_usage.detail', $projectId)
            ->with('success', 'Purchase logged successfully.')
            ->with('active_tab', 'purchased');
    }

    public function destroyPurchase($projectId, $purchaseId)
    {
        MaterialPurchase::where('project_id', $projectId)->findOrFail($purchaseId)->delete();

        return redirect()->route('admin.material_usage.detail', $projectId)
            ->with('success', 'Purchase record deleted.')
            ->with('active_tab', 'purchased');
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

        // Low stock alert: if linked to a BOM material, check if total usage >= 80% of planned qty
        $bomId = $request->input('project_material_id') ?: null;
        if ($bomId) {
            $bomMaterial = \App\Models\ProjectMaterial::find($bomId);
            if ($bomMaterial && $bomMaterial->quantity > 0) {
                $totalUsed = MaterialUsage::where('project_id', $project->id)
                    ->where('project_material_id', $bomId)
                    ->where('status', 'active')
                    ->sum('quantity_used');

                $usagePct = ($totalUsed / $bomMaterial->quantity) * 100;

                if ($usagePct >= 80 && $usagePct < 100) {
                    NotificationService::lowStockAlert($project, $bomMaterial);
                } elseif ($usagePct >= 100) {
                    NotificationService::notifyAdmins(
                        'Material Stock Depleted',
                        "\"{$bomMaterial->material_name}\" in {$project->name} has been fully consumed ({$bomMaterial->quantity} units planned, " . number_format($totalUsed, 2) . " used).",
                        'warning',
                        'red',
                        $project->id,
                        null,
                        "/admin/material-usage/{$project->id}"
                    );
                }
            }
        }

        return $usage;
    }
}
