<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialRequest;
use App\Models\FundTransaction;
use App\Models\FundSetting;
use App\Models\MaterialPurchase;

class MaterialRequestController extends Controller
{
    public function fund(Request $request, $id)
    {
        $materialRequest = MaterialRequest::with('projectMaterial')->findOrFail($id);

        $validated = $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'required|string|max:1000',
        ]);

        if ($validated['amount'] > FundSetting::getCurrentBalance()) {
            return redirect()->route('admin.material_usage')
                ->with('active_tab', 'requests')
                ->with('error', 'Insufficient Revolving Fund Balance.');
        }

        $newBalance = FundSetting::adjustBalance(-$validated['amount']);

        FundTransaction::create([
            'type'                => 'release',
            'amount'              => $validated['amount'],
            'date'                => now()->format('Y-m-d'),
            'project_id'          => $materialRequest->project_id,
            'material_request_id' => $materialRequest->id,
            'purpose'             => $validated['description'],
            'description'         => $validated['description'],
            'status'              => 'Pending Replenishment',
            'balance_after'       => $newBalance,
            'recorded_by'         => auth()->user()->name ?? 'Admin',
        ]);

        // The money bought this material, so log it as a purchase too — otherwise the project's
        // Actual Materials / Total Actual Spend / Net Profit would never see what the fund paid for.
        if ($materialRequest->project_id) {
            $qty = (float) $materialRequest->quantity > 0 ? (float) $materialRequest->quantity : 1;

            MaterialPurchase::create([
                'project_id'          => $materialRequest->project_id,
                'project_material_id' => $materialRequest->project_material_id,
                'material_name'       => $materialRequest->material ?: 'Material request #' . $materialRequest->id,
                'unit'                => $materialRequest->unit,
                'qty_bought'          => $qty,
                'actual_unit_cost'    => round($validated['amount'] / $qty, 2),
                'total_paid'          => $validated['amount'],
                'supplier'            => $materialRequest->supplier,
                'purchase_date'       => now()->format('Y-m-d'),
                'notes'               => 'Paid from the revolving fund — ' . $validated['description'],
            ]);
        }

        $materialRequest->update(['status' => 'fulfilled']);

        return redirect()->route('admin.material_usage')
            ->with('active_tab', 'requests')
            ->with('success', 'Material request funded from revolving fund and marked as fulfilled.');
    }

    public function rerequest($id)
    {
        MaterialRequest::findOrFail($id)->update(['status' => 'pending']);

        return redirect()->route('admin.material_usage')
            ->with('active_tab', 'requests')
            ->with('success', 'Material request re-sent to supplier.');
    }
}
