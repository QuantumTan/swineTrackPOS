<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        $suppliers = Supplier::query()
            ->orderBy('supplier_id')
            ->get();

        return view('pos.suppliers', [
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_name' => ['required', 'string', 'max:100'],
            'supplier_phone_number' => ['nullable', 'string', 'max:15'],
        ]);

        Supplier::create($validated);

        return redirect()
            ->route('suppliers.index')
            ->with('status', 'Supplier added successfully.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_name' => ['required', 'string', 'max:100'],
            'supplier_phone_number' => ['nullable', 'string', 'max:15'],
        ]);

        $supplier->update($validated);

        return redirect()
            ->route('suppliers.index')
            ->with('status', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        try {
            $supplier->delete();
        } catch (QueryException $exception) {
            return redirect()
                ->route('suppliers.index')
                ->withErrors([
                    'supplier_delete' => 'This supplier cannot be deleted while it is still used by other records.',
                ]);
        }

        return redirect()
            ->route('suppliers.index')
            ->with('status', 'Supplier deleted successfully.');
    }
}
