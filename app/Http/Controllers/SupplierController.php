<?php

namespace App\Http\Controllers;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
            'delivery' => (string) $request->query('delivery', ''),
        ];

        $suppliers = Supplier::query()
            ->withCount('batches')
            ->withMax('batches', 'batch_date')
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($searchQuery) use ($filters) {
                    $searchQuery
                        ->where('supplier_name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('contact_person_first_name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('contact_person_last_name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('contact_number', 'like', '%'.$filters['search'].'%')
                        ->orWhere('email_address', 'like', '%'.$filters['search'].'%')
                        ->orWhere('business_address', 'like', '%'.$filters['search'].'%')
                        ->orWhere('supplier_id', (int) $filters['search']);
                });
            })
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['delivery'] === 'with_history', fn ($query) => $query->whereHas('batches'))
            ->when($filters['delivery'] === 'no_history', fn ($query) => $query->whereDoesntHave('batches'))
            ->orderByRaw("CASE WHEN status = 'Active' THEN 0 ELSE 1 END")
            ->orderBy('supplier_name')
            ->paginate(10)
            ->withQueryString();

        return view('pos.suppliers', [
            'suppliers' => $suppliers,
            'filters' => $filters,
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
            // Extract the trigger's MESSAGE_TEXT from the exception
            $message = $exception->getMessage();
            
            // MySQL error message format: "SQLSTATE[45000]: User-defined Exception: <line> <MESSAGE_TEXT>"
            // Extract MESSAGE_TEXT between the last space and end
            if (preg_match('/:\s*(.+)$/', $message, $matches)) {
                $triggerMessage = trim($matches[1]);
            } else {
                $triggerMessage = $message;
            }
            
            return redirect()
                ->route('suppliers.index')
                ->withErrors([
                    'supplier_delete' => $triggerMessage,
                ]);
        }

        return redirect()
            ->route('suppliers.index')
            ->with('status', 'Supplier deleted successfully.');
    }
}
