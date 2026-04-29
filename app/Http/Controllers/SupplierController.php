<?php

namespace App\Http\Controllers;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        $suppliers = Supplier::query()
            ->withCount('batches')
            ->withMax('batches', 'batch_date')
            ->orderByRaw("CASE WHEN status = 'Active' THEN 0 ELSE 1 END")
            ->orderBy('supplier_name')
            ->paginate(10);

        return view('pos.suppliers', [
            'suppliers' => $suppliers,
            'supplierStats' => [
                'total' => Supplier::query()->count(),
                'active' => Supplier::query()
                    ->where('status', 'Active')
                    ->count(),
                'contact_ready' => Supplier::query()
                    ->where(function ($query) {
                        $query->whereNotNull('contact_number')
                            ->where('contact_number', '!=', '')
                            ->orWhere(function ($emailQuery) {
                                $emailQuery->whereNotNull('email_address')
                                    ->where('email_address', '!=', '');
                            });
                    })
                    ->count(),
                'with_delivery_history' => Supplier::query()
                    ->whereHas('batches')
                    ->count(),
            ],
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        Supplier::create($request->validated());

        return redirect()
            ->route('suppliers.index')
            ->with('status', 'Supplier added successfully.');
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

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
