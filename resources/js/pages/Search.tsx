import { useCallback, useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import type { SearchPageProps } from '@/types';
import { SearchInput } from '@/components/SearchInput';
import { FacetSidebar } from '@/components/FacetSidebar';
import { SortDropdown } from '@/components/SortDropdown';
import { ProductGrid } from '@/components/ProductGrid';
import { SearchPagination } from '@/components/SearchPagination';
import { SearchMeta } from '@/components/SearchMeta';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { LayoutGrid, List, X } from 'lucide-react';
import { usePageTracking } from '@/hooks/usePageTracking';

export default function Search({ products, facets, query, meta }: SearchPageProps) {
    usePageTracking();
    const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
    const [isSearching, setIsSearching] = useState(false);
    const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);

    // Track Inertia navigation for loading state
    useEffect(() => {
        const startHandler = () => setIsSearching(true);
        const finishHandler = () => setIsSearching(false);

        router.on('start', startHandler);
        router.on('finish', finishHandler);

        return () => {
            // Clean up is handled by Inertia internally
        };
    }, []);

    const navigate = useCallback((params: Record<string, unknown>) => {
        const cleanParams: Record<string, string> = {};

        Object.entries({ ...query, ...params, page: params.page ?? 1 }).forEach(([key, value]) => {
            if (value !== null && value !== '' && value !== undefined) {
                cleanParams[key] = String(value);
            }
        });

        router.get('/', cleanParams, {
            preserveState: true,
            preserveScroll: params.page !== undefined ? false : true,
            only: ['products', 'facets', 'query', 'meta'],
        });
    }, [query]);

    const clearFilters = useCallback(() => {
        router.get('/', query.q ? { q: query.q } : {}, {
            preserveState: true,
        });
    }, [query.q]);

    const hasActiveFilters = query.category || query.brand || query.tag || query.in_stock !== null || query.price_min || query.price_max;

    return (
        <div className="min-h-screen bg-background">
            {/* Header */}
            <header className="sticky top-0 z-40 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center gap-4">
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-bold tracking-tight">
                                <span className="text-primary">Demo</span>Search
                            </h1>
                        </div>
                        <div className="flex-1 max-w-2xl">
                            <SearchInput
                                value={query.q}
                                onChange={(q) => navigate({ q })}
                            />
                        </div>
                        <div className="flex items-center gap-2">
                            <a
                                href="/admin"
                                className="text-sm text-muted-foreground hover:text-foreground transition-colors"
                            >
                                Admin
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
                {/* Toolbar */}
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-2">
                        <SearchMeta meta={meta} isSearching={isSearching} />
                        {hasActiveFilters && (
                            <Button variant="ghost" size="sm" onClick={clearFilters} className="text-muted-foreground">
                                <X className="h-3 w-3 mr-1" />
                                Clear filters
                            </Button>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            className="md:hidden"
                            onClick={() => setMobileSidebarOpen(!mobileSidebarOpen)}
                        >
                            Filters
                        </Button>
                        <SortDropdown
                            value={query.sort}
                            onChange={(sort) => navigate({ sort })}
                        />
                        <div className="hidden sm:flex items-center border rounded-md">
                            <Button
                                variant={viewMode === 'grid' ? 'secondary' : 'ghost'}
                                size="icon"
                                className="h-8 w-8 rounded-r-none"
                                onClick={() => setViewMode('grid')}
                            >
                                <LayoutGrid className="h-4 w-4" />
                            </Button>
                            <Button
                                variant={viewMode === 'list' ? 'secondary' : 'ghost'}
                                size="icon"
                                className="h-8 w-8 rounded-l-none"
                                onClick={() => setViewMode('list')}
                            >
                                <List className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Active filter badges */}
                {hasActiveFilters && (
                    <div className="flex flex-wrap gap-2 mb-4">
                        {query.category && (
                            <Badge variant="secondary" className="gap-1">
                                Category: {query.category}
                                <button onClick={() => navigate({ category: null })} className="ml-1 hover:text-destructive">
                                    <X className="h-3 w-3" />
                                </button>
                            </Badge>
                        )}
                        {query.brand && (
                            <Badge variant="secondary" className="gap-1">
                                Brand: {query.brand}
                                <button onClick={() => navigate({ brand: null })} className="ml-1 hover:text-destructive">
                                    <X className="h-3 w-3" />
                                </button>
                            </Badge>
                        )}
                        {query.tag && (
                            <Badge variant="secondary" className="gap-1">
                                Tag: {query.tag}
                                <button onClick={() => navigate({ tag: null })} className="ml-1 hover:text-destructive">
                                    <X className="h-3 w-3" />
                                </button>
                            </Badge>
                        )}
                        {query.in_stock !== null && query.in_stock !== '' && (
                            <Badge variant="secondary" className="gap-1">
                                In Stock Only
                                <button onClick={() => navigate({ in_stock: null })} className="ml-1 hover:text-destructive">
                                    <X className="h-3 w-3" />
                                </button>
                            </Badge>
                        )}
                        {(query.price_min || query.price_max) && (
                            <Badge variant="secondary" className="gap-1">
                                Price: ${query.price_min || '0'} - ${query.price_max || '∞'}
                                <button onClick={() => navigate({ price_min: null, price_max: null })} className="ml-1 hover:text-destructive">
                                    <X className="h-3 w-3" />
                                </button>
                            </Badge>
                        )}
                    </div>
                )}

                {/* Main content */}
                <div className="flex gap-6">
                    {/* Sidebar */}
                    <aside className={`w-64 shrink-0 ${mobileSidebarOpen ? 'block' : 'hidden'} md:block`}>
                        <FacetSidebar
                            facets={facets}
                            query={query}
                            onFilter={(params) => navigate(params)}
                        />
                    </aside>

                    {/* Results */}
                    <main className="flex-1 min-w-0">
                        <ProductGrid
                            products={products}
                            viewMode={viewMode}
                            isSearching={isSearching}
                        />

                        {meta.total_pages > 1 && (
                            <div className="mt-8">
                                <SearchPagination
                                    currentPage={meta.page}
                                    totalPages={meta.total_pages}
                                    onPageChange={(page) => navigate({ page })}
                                />
                            </div>
                        )}
                    </main>
                </div>
            </div>
        </div>
    );
}
