<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Meilisearch\Client;

class SearchController extends Controller
{
    /**
     * Display the search page with results.
     */
    public function index(Request $request): Response
    {
        $q = $request->input('q', '');
        $category = $request->input('category');
        $brand = $request->input('brand');
        $tag = $request->input('tag');
        $inStock = $request->input('in_stock');
        $priceMin = $request->input('price_min');
        $priceMax = $request->input('price_max');
        $sort = $request->input('sort', 'relevance');
        $page = (int) $request->input('page', 1);
        $perPage = 24;

        // Build filter string for Meilisearch
        $filters = [];

        if ($category) {
            $filters[] = 'category = "' . addslashes($category) . '"';
        }

        if ($brand) {
            $filters[] = 'brand = "' . addslashes($brand) . '"';
        }

        if ($tag) {
            $filters[] = 'tags = "' . addslashes($tag) . '"';
        }

        if ($inStock !== null && $inStock !== '') {
            $filters[] = 'in_stock = ' . ($inStock ? 'true' : 'false');
        }

        if ($priceMin !== null && $priceMin !== '') {
            $filters[] = 'price >= ' . (float) $priceMin;
        }

        if ($priceMax !== null && $priceMax !== '') {
            $filters[] = 'price <= ' . (float) $priceMax;
        }

        $filterString = implode(' AND ', $filters);

        // Determine sort
        $sortRules = match ($sort) {
            'price_asc' => ['price:asc'],
            'price_desc' => ['price:desc'],
            'rating' => ['rating:desc'],
            'newest' => ['created_at:desc'],
            default => [],
        };

        // Use Meilisearch client directly for faceted search with highlight
        $searchResults = null;
        $products = [];
        $facetDistribution = [];
        $totalHits = 0;
        $processingTimeMs = 0;

        try {
            $client = app(Client::class);

            $searchParams = [
                'limit' => $perPage,
                'offset' => ($page - 1) * $perPage,
                'attributesToHighlight' => ['title', 'description'],
                'highlightPreTag' => '<mark class="bg-yellow-200 text-yellow-900 rounded px-0.5">',
                'highlightPostTag' => '</mark>',
                'facets' => ['category', 'brand', 'tags', 'in_stock'],
            ];

            if ($filterString) {
                $searchParams['filter'] = $filterString;
            }

            if (!empty($sortRules)) {
                $searchParams['sort'] = $sortRules;
            }

            $searchResults = $client->index('products')->search($q, $searchParams);

            $products = $searchResults->getHits();
            $facetDistribution = $searchResults->getFacetDistribution();
            $totalHits = $searchResults->getEstimatedTotalHits();
            $processingTimeMs = $searchResults->getProcessingTimeMs();

        } catch (\Exception $e) {
            // If Meilisearch is down, fallback to database search
            $query = Product::query();

            if ($q) {
                $query->where(function ($qb) use ($q) {
                    $qb->where('title', 'like', "%{$q}%")
                       ->orWhere('description', 'like', "%{$q}%");
                });
            }

            if ($category) {
                $query->where('category', $category);
            }

            if ($brand) {
                $query->where('brand', $brand);
            }

            if ($tag) {
                $query->whereJsonContains('tags', $tag);
            }

            if ($inStock !== null && $inStock !== '') {
                $query->where('in_stock', (bool) $inStock);
            }

            if ($priceMin !== null && $priceMin !== '') {
                $query->where('price', '>=', (float) $priceMin);
            }

            if ($priceMax !== null && $priceMax !== '') {
                $query->where('price', '<=', (float) $priceMax);
            }

            $totalHits = $query->count();
            $dbProducts = $query->skip(($page - 1) * $perPage)->take($perPage)->get();
            $products = $dbProducts->map(fn ($p) => array_merge($p->toArray(), [
                '_formatted' => $p->toArray(),
            ]))->toArray();

            // Build facet distribution from DB
            $facetDistribution = [
                'category' => Product::select('category')
                    ->selectRaw('count(*) as count')
                    ->groupBy('category')
                    ->pluck('count', 'category')
                    ->toArray(),
                'brand' => Product::select('brand')
                    ->selectRaw('count(*) as count')
                    ->groupBy('brand')
                    ->pluck('count', 'brand')
                    ->toArray(),
            ];
        }

        $totalPages = (int) ceil($totalHits / $perPage);

        return Inertia::render('Search', [
            'products' => $products,
            'facets' => $facetDistribution,
            'query' => [
                'q' => $q,
                'category' => $category,
                'brand' => $brand,
                'tag' => $tag,
                'in_stock' => $inStock,
                'price_min' => $priceMin,
                'price_max' => $priceMax,
                'sort' => $sort,
                'page' => $page,
            ],
            'meta' => [
                'total_hits' => $totalHits,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
                'processing_time_ms' => $processingTimeMs,
            ],
        ]);
    }
}
