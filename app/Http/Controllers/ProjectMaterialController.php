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
use App\Http\Controllers\Concerns\BackdatesRecords;

class ProjectMaterialController extends Controller
{
    use BackdatesRecords;

    // -----------------------------------------------------------------------
    // Admin
    // -----------------------------------------------------------------------

    public function adminClient($client)
    {
        $decoded = urldecode($client);

        $query = Project::with('activeMaterials', 'activeLabor', 'payments', 'assignedEmployees');
        if (ctype_digit($decoded)) {
            $query->where('client_id', (int) $decoded);
        } else {
            $query->where('client', $decoded);
        }

        $projects = $query->orderBy('created_at', 'desc')->get();

        abort_if($projects->isEmpty(), 404);

        $client = $projects->first()->live_client_name;

        return view('admin.project_quotation_client', compact('client', 'projects'));
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
        $materialFactor  = $materials->first()->factor ?? 0;

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
        if ($blocked = $this->completedGuard($project)) {
            return $blocked;
        }

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
            'factor'             => 'required|numeric|min:0|max:100',
            'notes'              => 'nullable|array',
            'notes.*'            => 'nullable|string',
            'entry_date'         => 'nullable|date',
            'entry_time'         => 'nullable|date_format:H:i',
        ]);

        [$createdCount, $updatedCount, $deletedCount] = $this->syncMaterials($request, $project);

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

    /**
     * A completed project's quotation is view only. Returns a redirect (with an error)
     * when the project is completed, or null when editing is allowed.
     */
    private function completedGuard(Project $project)
    {
        if ($project->status !== 'completed') {
            return null;
        }

        return redirect()
            ->route('admin.project_materials.detail', $project->id)
            ->with('error', 'This project is completed, so its quotation is view only.');
    }

    /**
     * Apply the material rows/deletions in the request to a project and keep every
     * material's factor in sync. Rows without a name are skipped so a blank row left in
     * the form never fails the whole save.
     *
     * @return array{0:int,1:int,2:int} created, updated, deleted counts
     */
    private function syncMaterials(Request $request, Project $project): array
    {
        $projectId = $project->id;
        $ids       = $request->input('material_id', []);
        $deleteIds = $request->input('delete_material_id', []);
        $names     = $request->input('material_name', []);
        $qtys      = $request->input('quantity', []);
        $prices    = $request->input('price_per_unit', []);
        $units     = $request->input('unit', []);
        $notes     = $request->input('notes', []);
        $factor    = (float) $request->input('factor');

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
            if (trim((string) $name) === '') {
                continue;
            }

            $qty   = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
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

            $material = new ProjectMaterial([
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
            $this->applyBackdate($material, $request->entry_date, $request->entry_time);
            $material->save();

            $createdCount++;
        }

        // The Material Factor applies to the whole project — keep every material's factor in sync.
        ProjectMaterial::where('project_id', $projectId)->update(['factor' => $factor]);

        return [$createdCount, $updatedCount, $deletedCount];
    }

    /**
     * The project quotation page's single Save: materials (add / edit / delete) and labor
     * (add / archive-restore) plus the working days are submitted together and applied in
     * one transaction, and only when the quotation is complete — every row fully filled in,
     * at least one material and one labor entry.
     */
    public function saveAll(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        if ($blocked = $this->completedGuard($project)) {
            return $blocked;
        }

        // Pricing (markup + payment terms) lives in the project's Payment record. It is only
        // editable when that record exists, and the terms are frozen once a payment is recorded.
        $pay          = $project->getPaymentRecord();
        $termsLocked  = $pay && $pay->transactions()->exists();

        $request->validate([
            'markup_percent'         => $pay ? 'required|numeric|min:0|max:100' : 'nullable',
            'payment_term_type'      => ($pay && !$termsLocked) ? 'required|in:big_project,small_project' : 'nullable',
            'factor'                 => 'required|numeric|min:0|max:100',
            'estimated_working_days' => 'required|numeric|gt:0',
            'entry_date'             => 'nullable|date',
            'entry_time'             => 'nullable|date_format:H:i',

            'material_id'            => 'nullable|array',
            'delete_material_id'     => 'nullable|array',
            'material_name'          => 'nullable|array',
            'material_name.*'        => 'nullable|string|max:255',
            'quantity'               => 'nullable|array',
            'quantity.*'             => 'nullable|numeric|min:0',
            'price_per_unit'         => 'nullable|array',
            'price_per_unit.*'       => 'nullable|numeric|min:0',
            'unit'                   => 'nullable|array',
            'unit.*'                 => 'nullable|string|max:50',
            'notes'                  => 'nullable|array',
            'notes.*'                => 'nullable|string',

            'employee_name'          => 'nullable|array',
            'employee_name.*'        => 'required|string|max:255',
            'role'                   => 'nullable|array',
            'role.*'                 => 'nullable|string|max:255',
            'daily_rate'             => 'nullable|array',
            'daily_rate.*'           => 'nullable|numeric|min:0',
            'labor_toggle_id'        => 'nullable|array',
            'labor_toggle_id.*'      => 'integer',
        ], [], [
            'markup_percent'         => 'markup',
            'payment_term_type'      => 'payment terms',
            'factor'                 => 'material factor',
            'estimated_working_days' => 'estimated working days',
        ]);

        $incomplete = [];
        foreach ($request->input('material_name', []) as $i => $name) {
            if (trim((string) $name) === '') {
                continue;
            }
            $row = $i + 1;
            if (trim((string) $request->input("unit.$i")) === '') {
                $incomplete["unit.$i"] = "Materials row {$row}: enter a unit.";
            }
            if ((float) ($request->input("quantity.$i") ?? 0) <= 0) {
                $incomplete["quantity.$i"] = "Materials row {$row}: quantity must be greater than 0.";
            }
            if (trim((string) $request->input("price_per_unit.$i")) === '') {
                $incomplete["price_per_unit.$i"] = "Materials row {$row}: enter the price per unit.";
            }
        }
        foreach ($request->input('employee_name', []) as $i => $name) {
            $row = $i + 1;
            if (trim((string) $request->input("role.$i")) === '') {
                $incomplete["role.$i"] = "Labor row {$row}: choose a role.";
            }
            if (trim((string) $request->input("daily_rate.$i")) === '') {
                $incomplete["daily_rate.$i"] = "Labor row {$row}: enter the daily rate.";
            }
        }
        if ($incomplete) {
            throw \Illuminate\Validation\ValidationException::withMessages($incomplete);
        }

        $summary = [];

        \DB::transaction(function () use ($request, $project, $projectId, $pay, $termsLocked, &$summary) {
            [$created, $updated, $deleted] = $this->syncMaterials($request, $project);
            if ($created) { $summary[] = $created === 1 ? '1 material added' : "{$created} materials added"; }
            if ($updated) { $summary[] = $updated === 1 ? '1 material updated' : "{$updated} materials updated"; }
            if ($deleted) { $summary[] = $deleted === 1 ? '1 material deleted' : "{$deleted} materials deleted"; }

            // Labor — working days first, so new rows and existing totals both use the final value
            $project->update(['estimated_working_days' => $request->input('estimated_working_days')]);
            $days = (float) $project->estimated_working_days;

            $roles = $request->input('role', []);
            $rates = $request->input('daily_rate', []);
            $added = 0;
            foreach ($request->input('employee_name', []) as $i => $name) {
                $rate        = (float) ($rates[$i] ?? 0);
                $role        = trim($roles[$i] ?? '');
                $description = $role ? "{$name} ({$role})" : $name;

                $labor = new ProjectLabor([
                    'project_id'  => (int) $projectId,
                    'description' => $description,
                    'daily_rate'  => $rate,
                    'total_cost'  => round($rate * $days, 2),
                    'status'      => 'active',
                ]);
                $this->applyBackdate($labor, $request->entry_date, $request->entry_time);
                $labor->save();
                $added++;
            }
            if ($added) { $summary[] = $added === 1 ? '1 labor entry added' : "{$added} labor entries added"; }

            foreach ((array) $request->input('labor_toggle_id', []) as $laborId) {
                $entry = ProjectLabor::where('project_id', $projectId)->find((int) $laborId);
                if ($entry) {
                    $entry->status = $entry->status === 'archived' ? 'active' : 'archived';
                    $entry->save();
                }
            }

            \DB::statement(
                'UPDATE project_labor SET total_cost = ROUND((daily_rate * ?)::numeric, 2) WHERE project_id = ?',
                [$days, $projectId]
            );

            // Pricing — only touch the Payment record when the markup or the terms actually changed.
            // Markup is a percentage of the FROZEN project budget (the fixed target Budget Adherence
            // measures against), so editing materials never moves the agreed contract by itself.
            if ($pay) {
                $newPct    = round((float) $request->input('markup_percent'), 2);
                $newTerms  = $termsLocked ? $pay->payment_term_type : $request->input('payment_term_type');
                $oldBudget = (float) $pay->project_budget;
                $oldPct    = $oldBudget > 0 ? round((float) $pay->markup / $oldBudget * 100, 2) : null;

                if ($oldPct === null || abs($newPct - $oldPct) > 0.004 || $newTerms !== $pay->payment_term_type) {
                    $budget   = $oldBudget > 0 ? $oldBudget : (float) $project->fresh()->estimatedBudget()['total'];
                    $markup   = round($budget * $newPct / 100, 2);
                    $contract = round($budget + $markup, 2);
                    $paid     = $pay->totalPaid();

                    if ($contract + 0.01 < $paid) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'markup_percent' => 'The contract value can\'t be lower than the ₱' . number_format($paid, 2) . ' already paid.',
                        ]);
                    }

                    $pay->update([
                        'project_budget'    => $budget,
                        'markup'            => $markup,
                        'contract_amount'   => $contract,
                        'payment_term_type' => $newTerms,
                        'payment_terms'     => $newTerms === 'big_project' ? '3 Phases (50% / 30% / 20%)' : '2 Phases (50% / 50%)',
                        'down_payment'      => round($contract * 0.5, 2),
                        'balance'           => max(0, round($contract - $paid, 2)),
                    ]);
                    $pay->update(['status' => $pay->fresh()->computeStatus()]);
                    $summary[] = 'pricing updated';
                }
            }

            // The finished quotation must contain at least one material and one labor entry.
            // Throwing here rolls the whole transaction back, so nothing is half-saved.
            if (!ProjectMaterial::where('project_id', $projectId)->where('status', 'active')->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['material_name' => 'Add at least one material before saving.']);
            }
            if (!ProjectLabor::where('project_id', $projectId)->where('status', 'active')->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['employee_name' => 'Add at least one labor entry before saving.']);
            }
        });

        return redirect()
            ->route('admin.project_materials.detail', $projectId)
            ->with('success', $summary ? 'Quotation saved — ' . implode(', ', $summary) . '.' : 'Quotation saved.');
    }

    public function storeLabor(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        if ($blocked = $this->completedGuard($project)) {
            return $blocked;
        }

        $request->validate([
            'estimated_working_days' => 'required|numeric|min:0',
            'employee_name'    => 'required|array|min:1',
            'employee_name.*'  => 'required|string|max:255',
            'role'             => 'nullable|array',
            'role.*'           => 'nullable|string|max:255',
            'daily_rate'       => 'required|array|min:1',
            'daily_rate.*'     => 'required|numeric|min:0',
            'entry_date'       => 'nullable|date',
            'entry_time'       => 'nullable|date_format:H:i',
        ]);

        $project->update(['estimated_working_days' => $request->input('estimated_working_days')]);

        $names = $request->input('employee_name');
        $roles = $request->input('role', []);
        $rates = $request->input('daily_rate');

        foreach ($names as $i => $name) {
            $rate        = (float) $rates[$i];
            $role        = trim($roles[$i] ?? '');
            $description = $role ? "{$name} ({$role})" : $name;

            $labor = new ProjectLabor([
                'project_id'  => (int) $projectId,
                'description' => $description,
                'daily_rate'  => $rate,
                'total_cost'  => round($rate * $project->estimated_working_days, 2),
                'status'      => 'active',
            ]);
            $this->applyBackdate($labor, $request->entry_date, $request->entry_time);
            $labor->save();
        }

        // Keep all existing entries' totals in sync with the (possibly updated) estimated working days
        \DB::statement(
            'UPDATE project_labor SET total_cost = ROUND((daily_rate * ?)::numeric, 2) WHERE project_id = ?',
            [$project->estimated_working_days, $projectId]
        );

        $count = count($names);
        $label = $count === 1 ? "1 labor entry" : "{$count} labor entries";

        return redirect()
            ->route('admin.project_materials.detail', $projectId)
            ->with('success', "Successfully added {$label} to the project.");
    }

    public function updateLabor(Request $request, $projectId, $laborId)
    {
        $project = Project::findOrFail($projectId);
        if ($blocked = $this->completedGuard($project)) {
            return $blocked;
        }
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
        if ($blocked = $this->completedGuard($project)) {
            return $blocked;
        }

        $validated = $request->validate([
            'estimated_working_days' => 'required|numeric|min:0',
        ]);

        $project->update($validated);

        \DB::statement(
            'UPDATE project_labor SET total_cost = ROUND((daily_rate * ?)::numeric, 2) WHERE project_id = ?',
            [$project->estimated_working_days, $projectId]
        );

        return redirect()
            ->route('admin.project_materials.detail', $projectId)
            ->with('success', "Estimated working days updated successfully.");
    }

    public function archiveLabor($projectId, $laborId)
    {
        if ($blocked = $this->completedGuard(Project::findOrFail($projectId))) {
            return $blocked;
        }
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
        $employee = Employee::findOrFail(session('user_id'));

        $projects = $employee->assignedProjects()
            ->with('activeMaterials', 'activeMaterialUsages')
            ->where('status', '!=', 'archived')
            ->orderByDesc('projects.created_at')
            ->get();

        $totalProjects = $projects->count();
        $totalMaterials = $projects->sum(fn ($project) => $project->activeMaterials->count());
        $totalEstimatedCost = $projects->sum(fn ($project) => $project->activeMaterials->sum('total_cost'));

        return view('employee.project_materials', compact('projects', 'totalProjects', 'totalMaterials', 'totalEstimatedCost'));
    }

    public function employeeDetail($projectId)
    {
        $project   = Project::findOrFail($projectId);

        if (!$project->assignedEmployees()->where('employees.id', session('user_id'))->exists()) {
            abort(403);
        }

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

        if (!$project->assignedEmployees()->where('employees.id', session('user_id'))->exists()) {
            abort(403);
        }

        $validated = $request->validate([
            'project_material_id' => 'nullable|exists:project_materials,id',
            'material_name'       => 'required|string|max:255',
            'quantity'            => 'required|integer|min:1',
            'unit'                => 'nullable|string|max:50',
            'notes'               => 'nullable|string|max:500',
            'requested_date'      => 'nullable|date',
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
            'requested_date'      => $validated['requested_date'] ?? now()->toDateString(),
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
