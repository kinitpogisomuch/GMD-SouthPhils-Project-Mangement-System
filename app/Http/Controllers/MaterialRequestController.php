<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialRequest;
use App\Models\FundTransaction;
use App\Models\FundSetting;

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
