<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
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

        $products->through(function (Product $product) {
                $stock = (float) ($product->current_stock_kg ?? 0);

                return [
                    'product_id' => $product->product_id,
                    'id' => 'P'.str_pad((string) $product->product_id, 3, '0', STR_PAD_LEFT),
                    'name' => $product->product_name,
                    'category' => $product->product_category,
                    'price_value' => (float) $product->product_price_per_kilo,
                    'price' => 'P'.number_format((float) $product->product_price_per_kilo, 2),
                    'stock' => [
                        'value' => number_format($stock, 3).' kg',
                        'class' => $stock <= 0 ? 'danger' : ($stock < 20 ? 'warning' : 'success'),
                    ],
                    'updated' => $product->last_updated_at
                        ? Carbon::parse($product->last_updated_at)->format('d M Y, h:i A')
                        : '-',
                ];
            });

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
        DB::transaction(function () use ($request) {
            $product = Product::create($request->validated());

            Inventory::create([
                'product_id' => $product->product_id,
                'current_stock_kg' => 0,
                'last_updated_at' => now(),
            ]);
        });

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
