<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupplierContact;

class SupplierContactController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone'   => ['nullable','string','regex:/^09\d{2}-\d{3}-\d{4}$/'],
            'email'   => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'notes'   => 'nullable|string|max:1000',
        ]);

        SupplierContact::create($validated);

        return redirect()->route('admin.material_usage')
            ->with('success', 'Supplier contact added successfully.');
    }

    public function update(Request $request, $id)
    {
        $contact = SupplierContact::findOrFail($id);

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone'   => ['nullable','string','regex:/^09\d{2}-\d{3}-\d{4}$/'],
            'email'   => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'notes'   => 'nullable|string|max:1000',
        ]);

        $contact->update($validated);

        return redirect()->route('admin.material_usage')
            ->with('success', 'Supplier contact updated successfully.');
    }

    public function destroy($id)
    {
        SupplierContact::findOrFail($id)->delete();

        return redirect()->route('admin.material_usage')
            ->with('success', 'Supplier contact deleted.');
    }
}
