<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\ProjectLabor;
use App\Models\Employee;
use App\Models\MaterialRequest;
use App\Models\MaterialPurchase;
use App\Services\NotificationService;

class ProjectMaterialController extends Controller
{
    // -----------------------------------------------------------------------
    // Admin
    // -----------------------------------------------------------------------

    public function adminIndex()
    {
        $projects = Project::with('activeMaterials', 'activeLabor', 'payments')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.project_quotation', compact('projects'));
    }

    public function adminDetail($projectId)
    {
        $project   = Project::findOrFail($projectId);
        $materials = ProjectMaterial::where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->get();

        $activeMaterials = $materials->where('status', 'active');
        $totalMaterials  = $activeMaterials->count();
        $totalQuantity   = $activeMaterials->sum('quantity');
        $estimatedCost   = $activeMaterials->sum('total_cost');
        $materialFactor  = $materials->first()->factor ?? 7;

        $regularEmployees = Employee::where('status', 'Active')
            ->where('employee_type', 'Regular')
            ->orderBy('last_name')
            ->get();

        // Employee lookup keyed by full name for rate resolution
        $employeeByName = $regularEmployees->keyBy(fn ($e) => trim($e->first_name . ' ' . $e->last_name));

        // Keep stored totals in sync using the employee's actual daily rate
        ProjectLabor::where('project_id', $projectId)->get()->each(function ($entry) use ($project, $employeeByName) {
            preg_match('/^(.+?)\s*\(/', $entry->description, $m);
            $empName   = isset($m[1]) ? trim($m[1]) : '';
            $emp       = $employeeByName->get($empName);
            $dailyRate = $emp ? (float) $emp->daily_rate : ($entry->rate_per_hour * 8);
            $expected  = round($dailyRate * $project->estimated_working_days, 2);
            if ((float) $entry->total_cost !== $expected) {
                $entry->update(['total_cost' => $expected]);
            }
        });

        $laborEntries = ProjectLabor::where('project_id', $projectId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($entry) use ($employeeByName) {
                preg_match('/^(.+?)\s*\(/', $entry->description, $m);
                $empName       = isset($m[1]) ? trim($m[1]) : '';
                $emp           = $employeeByName->get($empName);
                $entry->daily_rate = $emp ? (float) $emp->daily_rate : ($entry->rate_per_hour * 8);
                return $entry;
            });

        $activeLabor       = $laborEntries->where('status', 'active');
        $totalLaborEntries = $activeLabor->count();
        $totalLaborCost    = $activeLabor->sum('total_cost');

        // Purchases
        $purchases = MaterialPurchase::where('project_id', $projectId)
            ->with('projectMaterial')
            ->orderByDesc('purchase_date')
            ->get();
        $totalPurchased = $purchases->sum('total_paid');

        return view('admin.project_quotation_detail', compact(
            'project', 'materials', 'totalMaterials', 'totalQuantity', 'estimatedCost', 'materialFactor',
            'laborEntries', 'totalLaborEntries', 'totalLaborCost',
            'regularEmployees', 'purchases', 'totalPurchased'
        ));
    }

    public function storePurchase(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

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

        $totalPaid = round($validated['qty_bought'] * $validated['actual_unit_cost'], 2);

        MaterialPurchase::create([
            'project_id'          => $project->id,
            'project_material_id' => $validated['project_material_id'] ?? null,
            'material_name'       => $validated['material_name'],
            'unit'                => $validated['unit'] ?? null,
            'qty_bought'          => $validated['qty_bought'],
            'actual_unit_cost'    => $validated['actual_unit_cost'],
            'total_paid'          => $totalPaid,
            'supplier'            => $validated['supplier'] ?? null,
            'purchase_date'       => $validated['purchase_date'],
            'notes'               => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.project_materials.detail', $projectId)
            ->with('success', 'Purchase logged successfully.')
            ->with('active_tab', 'purchased');
    }

    public function destroyPurchase(Request $request, $projectId, $purchaseId)
    {
        MaterialPurchase::where('project_id', $projectId)->findOrFail($purchaseId)->delete();

        return redirect()->route('admin.project_materials.detail', $projectId)
            ->with('success', 'Purchase record deleted.')
            ->with('active_tab', 'purchased');
    }

    public function store(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        $request->validate([
            'material_id'        => 'nullable|array',
            'delete_material_id' => 'nullable|array',
            'material_name'      => 'required|array|min:1',
            'material_name.*'    => 'required|string|max:255',
            'quantity'           => 'required|array|min:1',
            'quantity.*'         => 'required|numeric|min:0.01',
            'price_per_unit'     => 'required|array|min:1',
            'price_per_unit.*'   => 'required|numeric|min:0',
            'unit'               => 'nullable|array',
            'unit.*'             => 'nullable|string|max:50',
            'factor'             => 'nullable|numeric|min:0|max:100',
            'notes'              => 'nullable|array',
            'notes.*'            => 'nullable|string',
        ]);

        $ids       = $request->input('material_id', []);
        $deleteIds = $request->input('delete_material_id', []);
        $names     = $request->input('material_name');
        $qtys      = $request->input('quantity');
        $prices    = $request->input('price_per_unit');
        $units     = $request->input('unit', []);
        $notes     = $request->input('notes', []);
        $factor    = $request->filled('factor') ? (float) $request->input('factor') : 7;

        $createdCount = 0;
        $updatedCount = 0;
        $deletedCount = 0;

        foreach ($deleteIds as $deleteId) {
            if (empty($deleteId)) {
                continue;
            }

            $material = ProjectMaterial::where('project_id', $projectId)->find((int) $deleteId);

            if ($material) {
                $materialName = $material->material_name;
                $material->delete();
                NotificationService::materialRemoved($project, $materialName);
                $deletedCount++;
            }
        }

        foreach ($names as $i => $name) {
            $qty   = (float) $qtys[$i];
            $price = (float) $prices[$i];
            $id    = !empty($ids[$i]) ? (int) $ids[$i] : null;

            if ($id) {
                $material = ProjectMaterial::where('project_id', $projectId)->find($id);

                if ($material) {
                    $material->update([
                        'material_name'  => $name,
                        'quantity'       => $qty,
                        'unit'           => $units[$i] ?? $material->unit,
                        'price_per_unit' => $price,
                        'total_cost'     => round($qty * $price, 2),
                        'notes'          => $notes[$i] ?? null,
                    ]);

                    NotificationService::materialUpdated($project, $material->material_name);
                    $updatedCount++;
                    continue;
                }
            }

            ProjectMaterial::create([
                'project_id'     => (int) $projectId,
                'material_name'  => $name,
                'quantity'       => $qty,
                'unit'           => $units[$i] ?? '',
                'price_per_unit' => $price,
                'total_cost'     => round($qty * $price, 2),
                'factor'         => $factor,
                'notes'          => $notes[$i] ?? null,
                'status'         => 'active',
            ]);

            $createdCount++;
        }

        // The Material Factor applies to the whole project — keep every material's factor in sync.
        ProjectMaterial::where('project_id', $projectId)->update(['factor' => $factor]);

        $messages = [];
        if ($createdCount > 0) {
            $messages[] = $createdCount === 1 ? "1 material added" : "{$createdCount} materials added";
        }
        if ($updatedCount > 0) {
            $messages[] = $updatedCount === 1 ? "1 material updated" : "{$updatedCount} materials updated";
        }
        if ($deletedCount > 0) {
            $messages[] = $deletedCount === 1 ? "1 material deleted" : "{$deletedCount} materials deleted";
        }
        $message = $messages ? implode(', ', $messages) . '.' : 'No changes were made.';

        return redirect()
            ->route('admin.project_materials.detail', $projectId)
            ->with('success', $message);
    }

    public function storeLabor(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        $request->validate([
            'estimated_working_days' => 'required|numeric|min:0',
            'employee_name'    => 'required|array|min:1',
            'employee_name.*'  => 'required|string|max:255',
            'role'             => 'nullable|array',
            'role.*'           => 'nullable|string|max:255',
            'daily_rate'       => 'required|array|min:1',
            'daily_rate.*'     => 'required|numeric|min:0',
        ]);

        $project->update(['estimated_working_days' => $request->input('estimated_working_days')]);

        $names = $request->input('employee_name');
        $roles = $request->input('role', []);
        $rates = $request->input('daily_rate');

        foreach ($names as $i => $name) {
            $rate        = (float) $rates[$i];
            $role        = trim($roles[$i] ?? '');
            $description = $role ? "{$name} ({$role})" : $name;

            ProjectLabor::create([
                'project_id'  => (int) $projectId,
                'description' => $description,
                'daily_rate'  => $rate,
                'total_cost'  => round($rate * $project->estimated_working_days, 2),
                'status'      => 'active',
            ]);
        }

        // Keep all existing entries' totals in sync with the (possibly updated) estimated working days
        ProjectLabor::where('project_id', $projectId)->get()->each(function ($entry) use ($project) {
            $entry->update([
                'total_cost' => round($entry->daily_rate * $project->estimated_working_days, 2),
            ]);
        });

        $count = count($names);
        $label = $count === 1 ? "1 labor entry" : "{$count} labor entries";

        return redirect()
            ->route('admin.project_materials.detail', $projectId)
            ->with('success', "Successfully added {$label} to the project.");
    }

    public function updateLabor(Request $request, $projectId, $laborId)
    {
        $project = Project::findOrFail($projectId);
        $entry   = ProjectLabor::where('project_id', $projectId)->findOrFail($laborId);

        $validated = $request->validate([
            'daily_rate' => 'required|numeric|min:0',
            'notes'      => 'nullable|string',
        ]);

        $validated['total_cost'] = round($validated['daily_rate'] * $project->estimated_working_days, 2);

        $entry->update($validated);

        NotificationService::laborUpdated($project, $entry->description);

        return redirect()
            ->route('admin.project_materials.detail', $projectId)
            ->with('success', "Labor entry \"{$entry->description}\" updated successfully.");
    }

    public function updateEstimatedDays(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        $validated = $request->validate([
            'estimated_working_days' => 'required|numeric|min:0',
        ]);

        $project->update($validated);

        ProjectLabor::where('project_id', $projectId)->get()->each(function ($entry) use ($project) {
            $entry->update([
                'total_cost' => round($entry->daily_rate * $project->estimated_working_days, 2),
            ]);
        });

        return redirect()
            ->route('admin.project_materials.detail', $projectId)
            ->with('success', "Estimated working days updated successfully.");
    }

    public function archiveLabor($projectId, $laborId)
    {
        $entry         = ProjectLabor::where('project_id', $projectId)->findOrFail($laborId);
        $entry->status = $entry->status === 'archived' ? 'active' : 'archived';
        $entry->save();

        $label = $entry->status === 'archived' ? 'archived' : 'restored';

        return redirect()
            ->route('admin.project_materials.detail', $projectId)
            ->with('success', "Labor entry \"{$entry->description}\" {$label} successfully.");
    }

    public function sendBOM(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        $materials = ProjectMaterial::where('project_id', $projectId)
            ->where('status', 'active')
            ->get();

        $adjCost = $materials->sum(function ($material) {
            $factor = $material->factor ?? 7;
            return round($material->total_cost * (1 + $factor / 100), 2);
        });
        $count = $materials->count();

        NotificationService::notifyProjectClient(
            $project,
            'Bill of Materials Shared',
            "The Bill of Materials for project \"{$project->name}\" has been shared with you.\n" .
            "Materials: {$count}\n" .
            "Estimated Cost: ₱" . number_format($adjCost, 2) . "\n" .
            "View your project for more details.",
            'bom_shared',
            'info',
            null,
            route('client.project_view', $project->id)
        );

        return redirect()
            ->route('admin.project_materials.detail', $projectId)
            ->with('success', "BOM sent to the client for \"{$project->name}\".");
    }

    // -----------------------------------------------------------------------
    // Employee (view only)
    // -----------------------------------------------------------------------

    public function employeeIndex()
    {
        $projects = Project::with('activeMaterials', 'activeMaterialUsages')
            ->where('status', '!=', 'archived')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalProjects = $projects->count();
        $totalMaterials = $projects->sum(fn ($project) => $project->activeMaterials->count());
        $totalEstimatedCost = $projects->sum(fn ($project) => $project->activeMaterials->sum('total_cost'));

        return view('employee.project_materials', compact('projects', 'totalProjects', 'totalMaterials', 'totalEstimatedCost'));
    }

    public function employeeDetail($projectId)
    {
        $project   = Project::findOrFail($projectId);
        $materials = ProjectMaterial::where('project_id', $projectId)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalMaterials = $materials->count();
        $totalQuantity  = $materials->sum('quantity');
        $estimatedCost  = $materials->sum('total_cost');

        $myRequests = MaterialRequest::where('project_id', $projectId)
            ->where('requested_by', session('user_id'))
            ->orderBy('created_at', 'desc')
            ->get();

        // Notify admin if any material's remaining stock (actual purchased stock - used) has dropped to 25% (half of half) or less
        foreach ($materials as $item) {
            $stockBought = MaterialPurchase::where('project_id', $projectId)
                ->where('project_material_id', $item->id)
                ->sum('qty_bought');

            if ($stockBought <= 0) continue;

            $totalUsed = \App\Models\MaterialUsage::where('project_id', $projectId)
                ->where('project_material_id', $item->id)
                ->where('status', 'active')
                ->sum('quantity_used');

            $remainingPct = (($stockBought - $totalUsed) / $stockBought) * 100;

            if ($remainingPct <= 25 && $remainingPct > 0) {
                NotificationService::lowStockAlert($project, $item, $stockBought - $totalUsed);
            }
        }

        return view('employee.project_materials_detail', compact(
            'project', 'materials', 'totalMaterials', 'totalQuantity', 'estimatedCost', 'myRequests'
        ));
    }

    /** Employee flags a material as short and requests more of it */
    public function requestMaterial(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        $validated = $request->validate([
            'project_material_id' => 'nullable|exists:project_materials,id',
            'material_name'       => 'required|string|max:255',
            'quantity'            => 'required|integer|min:1',
            'unit'                => 'nullable|string|max:50',
            'notes'               => 'nullable|string|max:500',
        ]);

        $employee = Employee::find(session('user_id'));

        MaterialRequest::create([
            'project_id'          => $project->id,
            'project_material_id' => $validated['project_material_id'] ?? null,
            'requested_by'        => $employee?->id,
            'material'            => $validated['material_name'],
            'quantity'            => $validated['quantity'],
            'unit'                => $validated['unit'] ?? '',
            'project'             => $project->name,
            'supplier'            => 'Pending Assignment',
            'requested_date'      => now()->toDateString(),
            'status'              => 'pending',
            'notes'               => $validated['notes'] ?? null,
        ]);

        NotificationService::materialRequested(
            $project,
            $employee?->full_name ?? 'An employee',
            $validated['material_name'],
            $validated['quantity'],
            $validated['notes'] ?? null
        );

        return redirect()
            ->route('employee.project_materials.detail', $projectId)
            ->with('success', "Material shortage reported for \"{$validated['material_name']}\". The admin team has been notified.");
    }

}
