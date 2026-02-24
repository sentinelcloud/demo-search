import type { Product } from '@/types';
import { ProductCard } from '@/components/ProductCard';
import { ProductSkeleton } from '@/components/ProductSkeleton';
import { SearchX } from 'lucide-react';
import { useImpressionTracking } from '@/hooks/useImpressionTracking';

interface ProductGridProps {
    products: Product[];
    viewMode: 'grid' | 'list';
    isSearching: boolean;
}

export function ProductGrid({ products, viewMode, isSearching }: ProductGridProps) {
    const { observeProduct } = useImpressionTracking();

    if (isSearching) {
        return (
            <div className={
                viewMode === 'grid'
                    ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4'
                    : 'space-y-3'
            }>
                {Array.from({ length: 6 }).map((_, i) => (
                    <ProductSkeleton key={i} viewMode={viewMode} />
                ))}
            </div>
        );
    }

    if (products.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-20 text-center">
                <SearchX className="h-12 w-12 text-muted-foreground mb-4" />
                <h3 className="text-lg font-semibold mb-2">No products found</h3>
                <p className="text-muted-foreground max-w-md">
                    Try adjusting your search terms or filters. Meilisearch handles typos well — try a different spelling!
                </p>
            </div>
        );
    }

    return (
        <div className={
            viewMode === 'grid'
                ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4'
                : 'space-y-3'
        }>
            {products.map((product) => (
                <ProductCard
                    key={product.id}
                    product={product}
                    viewMode={viewMode}
                    impressionRef={observeProduct}
                />
            ))}
        </div>
    );
}
