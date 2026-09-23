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
        $projects = Project::with('activeMaterials', 'activeMaterialUsages')->get();

        // Group by client_id when a project is linked (so a client rename or
        // relink is reflected immediately); fall back to the raw name string
        // for the rare project that predates the client_id link.
        $clientGroups = $projects->groupBy(fn ($p) => $p->client_id ? 'id:' . $p->client_id : 'name:' . $p->client)
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'client'          => $first->live_client_name,
                    'client_key'      => $first->client_id ?: $first->client,
                    'project_count'   => $group->count(),
                    'materials_count' => $group->sum(fn ($p) => $p->activeMaterials->count()),
                    'usage_count'     => $group->sum(fn ($p) => $p->activeMaterialUsages->count()),
                    'total_qty_used'  => $group->sum(fn ($p) => $p->activeMaterialUsages->sum('quantity_used')),
                    'last_created_at' => $group->max('created_at'),
                    'has_active'      => $group->contains(fn ($p) => !in_array($p->status, ['completed', 'archived'])),
                    'has_completed'   => $group->contains(fn ($p) => $p->status === 'completed'),
                    'has_archived'    => $group->contains(fn ($p) => $p->status === 'archived'),
                ];
            })->sortBy(fn ($g) => strtolower($g['client']))->values();

        $suppliers = SupplierContact::orderBy('name')->get();

        return view('admin.material_usage', compact('clientGroups', 'suppliers'));
    }

    public function clientIndex($client)
    {
        $decoded = urldecode($client);

        $query = Project::with('activeMaterials', 'activeMaterialUsages');
        if (ctype_digit($decoded)) {
            $query->where('client_id', (int) $decoded);
        } else {
            $query->where('client', $decoded);
        }

        $projects = $query->orderBy('name')->get();

        abort_if($projects->isEmpty(), 404);

        $clientName = $projects->first()->live_client_name;

        return view('admin.material_usage_client', compact('projects', 'clientName'));
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

    /**
     * Logs one or more material usage entries in a single submission — mirrors
     * storePurchase()'s batch pattern so several materials consumed on the
     * same day can be recorded at once instead of one at a time. The date is
     * shared across every row in the batch.
     */
    public function store(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        // strip the "__other__" sentinel (and any blank value) before validation
        $materialIds = collect($request->input('project_material_id', []))
            ->map(fn ($id) => in_array($id, ['__other__', ''], true) ? null : $id)
            ->all();
        $request->merge(['project_material_id' => $materialIds]);

        // Usage can only be logged against materials that actually have
        // purchased stock — every row must be linked to a real BOM material.
        $validated = $request->validate([
            'project_material_id'   => 'required|array|min:1',
            'project_material_id.*' => 'required|exists:project_materials,id',
            'material_name'         => 'required|array|min:1',
            'material_name.*'       => 'required|string|max:255',
            'unit'                  => 'nullable|array',
            'unit.*'                => 'nullable|string|max:50',
            'quantity_used'         => 'required|array|min:1',
            'quantity_used.*'       => 'required|numeric|min:0.01',
            'used_date'             => 'required|date',
        ]);

        // Reject any row that would use more than what's actually in stock
        // (purchased so far minus what's already been logged as used).
        $stockErrors = [];
        $count       = count($validated['material_name']);
        foreach ($validated['project_material_id'] as $i => $bomId) {
            $bought      = MaterialPurchase::where('project_id', $project->id)
                ->where('project_material_id', $bomId)
                ->sum('qty_bought');
            $alreadyUsed = MaterialUsage::where('project_id', $project->id)
                ->where('project_material_id', $bomId)
                ->where('status', 'active')
                ->sum('quantity_used');
            $remaining = $bought - $alreadyUsed;
            $requested = (float) $validated['quantity_used'][$i];

            if ($remaining <= 0) {
                $stockErrors[] = "\"{$validated['material_name'][$i]}\" has no stock purchased yet.";
            } elseif ($requested > $remaining) {
                $stockErrors[] = "\"{$validated['material_name'][$i]}\" only has " . rtrim(rtrim(number_format($remaining, 2), '0'), '.') . " remaining in stock.";
            }
        }

        if (!empty($stockErrors)) {
            return redirect()->route('admin.material_usage.detail', $project->id)
                ->with('error', implode(' ', $stockErrors))
                ->with('active_tab', 'usage');
        }

        $recordedBy = auth()->user()->name ?? 'Admin';

        for ($i = 0; $i < $count; $i++) {
            $bomId = $validated['project_material_id'][$i];

            MaterialUsage::create([
                'project_id'          => $project->id,
                'project_material_id' => $bomId,
                'material_name'       => $validated['material_name'][$i],
                'quantity_used'       => $validated['quantity_used'][$i],
                'unit'                => $validated['unit'][$i] ?? null,
                'used_date'           => $validated['used_date'],
                'recorded_by'         => $recordedBy,
                'status'              => 'active',
            ]);

            $this->checkLowStock($project, $bomId);
        }

        $message = $count === 1
            ? 'Material usage logged successfully.'
            : "{$count} material usage entries logged successfully.";

        return redirect()
            ->route('admin.material_usage.detail', $project->id)
            ->with('success', $message);
    }

    /**
     * Logs one or more material purchases in a single submission — a whole
     * supplier run can be entered at once instead of one purchase at a time.
     * Supplier/date/notes are shared across every row in the batch.
     */
    public function storePurchase(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        // strip the "__other__" sentinel (and any blank value) before validation
        $materialIds = collect($request->input('project_material_id', []))
            ->map(fn ($id) => in_array($id, ['__other__', ''], true) ? null : $id)
            ->all();
        $request->merge(['project_material_id' => $materialIds]);

        $validated = $request->validate([
            'project_material_id'   => 'nullable|array',
            'project_material_id.*' => 'nullable|exists:project_materials,id',
            'material_name'         => 'required|array|min:1',
            'material_name.*'       => 'required|string|max:255',
            'unit'                  => 'nullable|array',
            'unit.*'                => 'nullable|string|max:50',
            'qty_bought'            => 'required|array|min:1',
            'qty_bought.*'          => 'required|numeric|min:0.01',
            'actual_unit_cost'      => 'required|array|min:1',
            'actual_unit_cost.*'    => 'required|numeric|min:0',
            'supplier'              => 'nullable|string|max:255',
            'purchase_date'         => 'required|date',
            'notes'                 => 'nullable|string|max:500',
        ]);

        $count = count($validated['material_name']);

        for ($i = 0; $i < $count; $i++) {
            $qty  = $validated['qty_bought'][$i];
            $cost = $validated['actual_unit_cost'][$i];

            MaterialPurchase::create([
                'project_id'          => $project->id,
                'project_material_id' => $validated['project_material_id'][$i] ?? null,
                'material_name'       => $validated['material_name'][$i],
                'unit'                => $validated['unit'][$i] ?? null,
                'qty_bought'          => $qty,
                'actual_unit_cost'    => $cost,
                'total_paid'          => round($qty * $cost, 2),
                'supplier'            => $validated['supplier'] ?? null,
                'purchase_date'       => $validated['purchase_date'],
                'notes'               => $validated['notes'] ?? null,
            ]);
        }

        $message = $count === 1 ? 'Purchase logged successfully.' : "{$count} purchases logged successfully.";

        return redirect()->route('admin.material_usage.detail', $projectId)
            ->with('success', $message)
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
        $project = Project::findOrFail($projectId);

        if (!$project->assignedEmployees()->where('employees.id', session('user_id'))->exists()) {
            abort(403);
        }

        return view('employee.material_usage_detail', $this->buildDetailData($projectId));
    }

    public function employeeStore(Request $request, $projectId)
    {
        $project  = Project::findOrFail($projectId);

        if (!$project->assignedEmployees()->where('employees.id', session('user_id'))->exists()) {
            abort(403);
        }

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

        // Only notify when an employee logs usage — skip when the admin logs it themselves
        if ($loggedBy) {
            NotificationService::materialUsageLogged(
                $project,
                $request->input('material_name'),
                (float) $request->input('quantity_used'),
                $loggedBy,
                // Stamp the notification with the date the material was actually
                // used (which may be backdated), not the moment it was typed in.
                \Carbon\Carbon::parse($request->input('used_date'))
            );
        }

        $this->checkLowStock($project, $request->input('project_material_id') ?: null);

        return $usage;
    }

    /**
     * Low stock alert: based on ACTUAL purchased stock remaining, not the BOM
     * planned quantity. Shared by both the single-entry (employee) and
     * batch (admin) usage-logging paths.
     */
    private function checkLowStock(Project $project, ?int $bomId): void
    {
        if (!$bomId) {
            return;
        }

        $bomMaterial = \App\Models\ProjectMaterial::find($bomId);
        if (!$bomMaterial) {
            return;
        }

        $stockBought = MaterialPurchase::where('project_id', $project->id)
            ->where('project_material_id', $bomId)
            ->sum('qty_bought');

        if ($stockBought <= 0) {
            return;
        }

        $totalUsed = MaterialUsage::where('project_id', $project->id)
            ->where('project_material_id', $bomId)
            ->where('status', 'active')
            ->sum('quantity_used');

        $usagePct = ($totalUsed / $stockBought) * 100;

        // Alert once remaining stock drops to 25% (half of half) of what was actually purchased
        if ($usagePct >= 75 && $usagePct < 100) {
            NotificationService::lowStockAlert($project, $bomMaterial, $stockBought - $totalUsed);
        } elseif ($usagePct >= 100) {
            NotificationService::notifyAdmins(
                'Material Stock Depleted',
                "\"{$bomMaterial->material_name}\" in {$project->name} has been fully consumed ({$stockBought} units bought, " . number_format($totalUsed, 2) . " used).",
                'warning',
                'red',
                $project->id,
                null,
                "/admin/material-usage/{$project->id}"
            );
        }
    }
}
