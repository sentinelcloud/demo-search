<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Display the admin product listing.
     */
    public function index(Request $request): Response
    {
        $products = Product::latest()
            ->paginate(20)
            ->through(fn ($product) => [
                'id' => $product->id,
                'title' => $product->title,
                'category' => $product->category,
                'brand' => $product->brand,
                'price' => $product->price,
                'rating' => $product->rating,
                'in_stock' => $product->in_stock,
                'created_at' => $product->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Index', [
            'products' => $products,
        ]);
    }

    /**
     * Show the form to create a new product.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Form', [
            'product' => null,
            'categories' => Product::distinct()->pluck('category'),
            'brands' => Product::distinct()->pluck('brand'),
        ]);
    }

    /**
     * Store a new product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:100',
            'brand' => 'required|string|max:100',
            'tags' => 'array',
            'tags.*' => 'string',
            'price' => 'required|numeric|min:0',
            'rating' => 'required|numeric|min:0|max:5',
            'in_stock' => 'boolean',
        ]);

        $validated['in_stock'] = $validated['in_stock'] ?? true;

        Product::create($validated);

        return redirect()->route('admin.index')->with('success', 'Product created and indexed!');
    }

    /**
     * Show the form to edit a product.
     */
    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Form', [
            'product' => $product,
            'categories' => Product::distinct()->pluck('category'),
            'brands' => Product::distinct()->pluck('brand'),
        ]);
    }

    /**
     * Update a product.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:100',
            'brand' => 'required|string|max:100',
            'tags' => 'array',
            'tags.*' => 'string',
            'price' => 'required|numeric|min:0',
            'rating' => 'required|numeric|min:0|max:5',
            'in_stock' => 'boolean',
        ]);

        $product->update($validated);

        return redirect()->route('admin.index')->with('success', 'Product updated and re-indexed!');
    }

    /**
     * Delete a product.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.index')->with('success', 'Product deleted and removed from index!');
    }
}
