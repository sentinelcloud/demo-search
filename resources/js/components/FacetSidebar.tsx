import { useState } from 'react';
import type { FacetDistribution, SearchQuery } from '@/types';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Slider } from '@/components/ui/slider';
import { Button } from '@/components/ui/button';
import { ChevronDown, ChevronUp } from 'lucide-react';

interface FacetSidebarProps {
    facets: FacetDistribution;
    query: SearchQuery;
    onFilter: (params: Record<string, unknown>) => void;
}

function FacetSection({
    title,
    children,
    defaultOpen = true,
}: {
    title: string;
    children: React.ReactNode;
    defaultOpen?: boolean;
}) {
    const [isOpen, setIsOpen] = useState(defaultOpen);

    return (
        <div className="border-b pb-4">
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="flex w-full items-center justify-between py-2 text-sm font-semibold"
            >
                {title}
                {isOpen ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
            </button>
            {isOpen && <div className="mt-2">{children}</div>}
        </div>
    );
}

export function FacetSidebar({ facets, query, onFilter }: FacetSidebarProps) {
    const [priceRange, setPriceRange] = useState<[number, number]>([
        query.price_min ? Number(query.price_min) : 0,
        query.price_max ? Number(query.price_max) : 1000,
    ]);

    const sortedCategories = Object.entries(facets.category || {}).sort((a, b) => b[1] - a[1]);
    const sortedBrands = Object.entries(facets.brand || {}).sort((a, b) => b[1] - a[1]);
    const sortedTags = Object.entries(facets.tags || {}).sort((a, b) => b[1] - a[1]);

    return (
        <div className="space-y-1">
            {/* In Stock filter */}
            <FacetSection title="Availability">
                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="in-stock"
                        checked={query.in_stock === '1' || query.in_stock === 'true'}
                        onCheckedChange={(checked) => {
                            onFilter({ in_stock: checked ? '1' : null });
                        }}
                    />
                    <Label htmlFor="in-stock" className="text-sm cursor-pointer">
                        In stock only
                        {facets.in_stock?.['true'] !== undefined && (
                            <span className="text-muted-foreground ml-1">({facets.in_stock['true']})</span>
                        )}
                    </Label>
                </div>
            </FacetSection>

            {/* Category filter */}
            {sortedCategories.length > 0 && (
                <FacetSection title="Category">
                    <div className="space-y-2 max-h-60 overflow-y-auto">
                        {sortedCategories.map(([category, count]) => (
                            <button
                                key={category}
                                onClick={() => onFilter({ category: query.category === category ? null : category })}
                                className={`flex w-full items-center justify-between rounded-md px-2 py-1 text-sm transition-colors hover:bg-accent ${
                                    query.category === category ? 'bg-accent font-medium' : ''
                                }`}
                            >
                                <span>{category}</span>
                                <span className="text-muted-foreground text-xs">{count}</span>
                            </button>
                        ))}
                    </div>
                </FacetSection>
            )}

            {/* Brand filter */}
            {sortedBrands.length > 0 && (
                <FacetSection title="Brand" defaultOpen={false}>
                    <div className="space-y-2 max-h-60 overflow-y-auto">
                        {sortedBrands.map(([brand, count]) => (
                            <button
                                key={brand}
                                onClick={() => onFilter({ brand: query.brand === brand ? null : brand })}
                                className={`flex w-full items-center justify-between rounded-md px-2 py-1 text-sm transition-colors hover:bg-accent ${
                                    query.brand === brand ? 'bg-accent font-medium' : ''
                                }`}
                            >
                                <span>{brand}</span>
                                <span className="text-muted-foreground text-xs">{count}</span>
                            </button>
                        ))}
                    </div>
                </FacetSection>
            )}

            {/* Tag filter */}
            {sortedTags.length > 0 && (
                <FacetSection title="Tags" defaultOpen={false}>
                    <div className="space-y-2 max-h-60 overflow-y-auto">
                        {sortedTags.map(([tag, count]) => (
                            <button
                                key={tag}
                                onClick={() => onFilter({ tag: query.tag === tag ? null : tag })}
                                className={`flex w-full items-center justify-between rounded-md px-2 py-1 text-sm transition-colors hover:bg-accent ${
                                    query.tag === tag ? 'bg-accent font-medium' : ''
                                }`}
                            >
                                <span>{tag}</span>
                                <span className="text-muted-foreground text-xs">{count}</span>
                            </button>
                        ))}
                    </div>
                </FacetSection>
            )}

            {/* Price range filter */}
            <FacetSection title="Price Range">
                <div className="px-2 space-y-4">
                    <Slider
                        value={priceRange}
                        min={0}
                        max={1000}
                        step={10}
                        onValueChange={(value) => setPriceRange(value as [number, number])}
                        onValueCommit={(value) => {
                            const [min, max] = value as [number, number];
                            onFilter({
                                price_min: min > 0 ? String(min) : null,
                                price_max: max < 1000 ? String(max) : null,
                            });
                        }}
                    />
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>${priceRange[0]}</span>
                        <span>${priceRange[1]}{priceRange[1] >= 1000 ? '+' : ''}</span>
                    </div>
                </div>
            </FacetSection>
        </div>
    );
}
