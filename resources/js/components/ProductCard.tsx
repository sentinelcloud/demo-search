import type { Product } from '@/types';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Star, Package, PackageX } from 'lucide-react';
import { tracker } from '@/lib/tracker';

interface ProductCardProps {
    product: Product;
    viewMode: 'grid' | 'list';
    impressionRef?: (el: HTMLElement | null) => void;
}

function StarRating({ rating }: { rating: number }) {
    return (
        <div className="flex items-center gap-1">
            {[1, 2, 3, 4, 5].map((star) => (
                <Star
                    key={star}
                    className={`h-3.5 w-3.5 ${
                        star <= Math.round(rating)
                            ? 'fill-amber-400 text-amber-400'
                            : 'text-muted-foreground/30'
                    }`}
                />
            ))}
            <span className="text-xs text-muted-foreground ml-1">{rating.toFixed(1)}</span>
        </div>
    );
}

function HighlightedText({ html, fallback }: { html?: string; fallback: string }) {
    if (html && html !== fallback) {
        return <span dangerouslySetInnerHTML={{ __html: html }} />;
    }
    return <>{fallback}</>;
}

export function ProductCard({ product, viewMode, impressionRef }: ProductCardProps) {
    const formatted = product._formatted;

    const handleClick = () => {
        tracker.trackClick(product.id, { title: product.title, price: product.price });
    };

    if (viewMode === 'list') {
        return (
            <Card
                className="overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
                data-product-id={product.id}
                ref={impressionRef}
                onClick={handleClick}
            >
                <CardContent className="flex gap-4 p-4">
                    {product.image_url && (
                        <div className="w-20 h-20 shrink-0 rounded-md overflow-hidden bg-muted">
                            <img
                                src={product.image_url}
                                alt={product.title}
                                className="w-full h-full object-cover"
                                loading="lazy"
                            />
                        </div>
                    )}
                    <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between gap-2">
                            <div>
                                <h3 className="font-semibold text-sm leading-tight">
                                    <HighlightedText
                                        html={formatted?.title as string}
                                        fallback={product.title}
                                    />
                                </h3>
                                <p className="text-xs text-muted-foreground mt-1">
                                    {product.brand} · {product.category}
                                </p>
                            </div>
                            <div className="text-right shrink-0">
                                <p className="font-bold text-lg">${product.price.toFixed(2)}</p>
                                {product.in_stock ? (
                                    <span className="text-xs text-green-600 flex items-center gap-1 justify-end">
                                        <Package className="h-3 w-3" /> In Stock
                                    </span>
                                ) : (
                                    <span className="text-xs text-red-500 flex items-center gap-1 justify-end">
                                        <PackageX className="h-3 w-3" /> Out of Stock
                                    </span>
                                )}
                            </div>
                        </div>
                        <p className="text-xs text-muted-foreground mt-2 line-clamp-2">
                            <HighlightedText
                                html={formatted?.description as string}
                                fallback={product.description}
                            />
                        </p>
                        <div className="flex items-center gap-2 mt-2">
                            <StarRating rating={product.rating} />
                            <div className="flex gap-1 flex-wrap">
                                {product.tags?.slice(0, 3).map((tag) => (
                                    <Badge key={tag} variant="outline" className="text-[10px] px-1.5 py-0">
                                        {tag}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        );
    }

    // Grid view
    return (
        <Card
            className="overflow-hidden hover:shadow-md transition-shadow group cursor-pointer"
            data-product-id={product.id}
            ref={impressionRef}
            onClick={handleClick}
        >
            {product.image_url && (
                <div className="aspect-[4/3] w-full overflow-hidden bg-muted">
                    <img
                        src={product.image_url}
                        alt={product.title}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        loading="lazy"
                    />
                </div>
            )}
            <CardContent className="p-4">
                <div className="flex items-start justify-between gap-2 mb-2">
                    <Badge variant="secondary" className="text-[10px]">
                        {product.category}
                    </Badge>
                    {product.in_stock ? (
                        <Badge variant="success" className="text-[10px]">In Stock</Badge>
                    ) : (
                        <Badge variant="destructive" className="text-[10px]">Out of Stock</Badge>
                    )}
                </div>

                <h3 className="font-semibold text-sm leading-tight mb-1 group-hover:text-primary transition-colors">
                    <HighlightedText
                        html={formatted?.title as string}
                        fallback={product.title}
                    />
                </h3>

                <p className="text-xs text-muted-foreground mb-2">{product.brand}</p>

                <p className="text-xs text-muted-foreground line-clamp-2 mb-3">
                    <HighlightedText
                        html={formatted?.description as string}
                        fallback={product.description}
                    />
                </p>

                <div className="flex items-center justify-between">
                    <span className="text-lg font-bold">${product.price.toFixed(2)}</span>
                    <StarRating rating={product.rating} />
                </div>

                {product.tags && product.tags.length > 0 && (
                    <div className="flex gap-1 flex-wrap mt-3">
                        {product.tags.slice(0, 3).map((tag) => (
                            <Badge key={tag} variant="outline" className="text-[10px] px-1.5 py-0">
                                {tag}
                            </Badge>
                        ))}
                        {product.tags.length > 3 && (
                            <span className="text-[10px] text-muted-foreground">
                                +{product.tags.length - 3} more
                            </span>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
