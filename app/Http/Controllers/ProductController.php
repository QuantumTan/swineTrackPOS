<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category_id' => (string) $request->query('category_id', ''),
            'stock_status' => (string) $request->query('stock_status', ''),
        ];

        $products = Product::query()
            ->with('category')
            ->leftJoin('inventory', 'inventory.product_id', '=', 'product.product_id')
            ->select('product.*', 'inventory.current_stock', 'inventory.last_updated_at')
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($searchQuery) use ($filters) {
                    $searchQuery
                        ->where('product.product_name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('product.product_id', (int) $filters['search']);
                });
            })
            ->when($filters['category_id'] !== '', fn ($query) => $query->where('product.category_id', $filters['category_id']))
            ->when($filters['stock_status'] !== '', function ($query) use ($filters) {
                if ($filters['stock_status'] === 'in_stock') {
                    $query->where('inventory.current_stock', '>=', Product::LOW_STOCK_THRESHOLD);
                } elseif ($filters['stock_status'] === 'low_stock') {
                    $query->where('inventory.current_stock', '>', 0)
                        ->where('inventory.current_stock', '<', Product::LOW_STOCK_THRESHOLD);
                } elseif ($filters['stock_status'] === 'out_of_stock') {
                    $query->where(function ($stockQuery) {
                        $stockQuery->whereNull('inventory.current_stock')
                            ->orWhere('inventory.current_stock', '<=', 0);
                    });
                }
            })
            ->orderBy('product.product_id')
            ->paginate(10)
            ->withQueryString();

        return view('pos.products', [
            'products' => $products,
            'categories' => $this->categories(),
            'filters' => $filters,
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
     * @return \Illuminate\Support\Collection<int, Category>
     */
    private function categories()
    {
        return Category::query()
            ->orderBy('category_name')
            ->get();
    }
}
