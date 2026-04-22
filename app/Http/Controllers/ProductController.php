<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $products = Product::query()
            ->leftJoin('inventory', 'inventory.product_id', '=', 'product.product_id')
            ->select('product.*', 'inventory.current_stock_kg', 'inventory.last_updated_at')
            ->orderBy('product.product_id')
            ->paginate(10)
            ->withQueryString();

        return view('pos.products', [
            'products' => $products,
            'categories' => $this->categories(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', 'Product added successfully.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function show(Product $product)
    {
        abort(404);
    }

    /**
     * Display the specified resource.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        try {
            DB::transaction(function () use ($product) {
                Inventory::where('product_id', $product->product_id)->delete();
                $product->delete();
            });
        } catch (QueryException $exception) {
            return redirect()
                ->route('products.index')
                ->withErrors([
                    'product_delete' => 'This product cannot be deleted while it is still used by inventory, stock-in, or sales records.',
                ]);
        }

        return redirect()
            ->route('products.index')
            ->with('status', 'Product deleted successfully.');
    }

    /**
     * @return array<int, string>
     */
    private function categories(): array
    {
        return [
            'Premium Cuts',
            'Ground Meat',
            'Standard Cuts',
            'Offal',
        ];
    }
}
