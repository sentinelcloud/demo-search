import { router } from '@inertiajs/react';
import type { PaginatedData } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Plus, Pencil, Trash2, ArrowLeft, BarChart3 } from 'lucide-react';

interface AdminProduct {
    id: number;
    title: string;
    category: string;
    brand: string;
    price: number;
    rating: number;
    in_stock: boolean;
    created_at: string;
}

interface AdminIndexProps {
    products: PaginatedData<AdminProduct>;
}

export default function AdminIndex({ products }: AdminIndexProps) {
    const handleDelete = (id: number) => {
        if (confirm('Delete this product? It will also be removed from the search index.')) {
            router.delete(`/admin/${id}`);
        }
    };

    return (
        <div className="min-h-screen bg-background">
            <header className="border-b">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <div className="flex items-center gap-4">
                            <a href="/" className="text-muted-foreground hover:text-foreground transition-colors">
                                <ArrowLeft className="h-5 w-5" />
                            </a>
                            <h1 className="text-xl font-bold">Product Admin</h1>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button variant="outline" onClick={() => router.visit('/admin/analytics')}>
                                <BarChart3 className="h-4 w-4 mr-2" />
                                Analytics
                            </Button>
                            <Button onClick={() => router.visit('/admin/create')}>
                                <Plus className="h-4 w-4 mr-2" />
                                Add Product
                            </Button>
                        </div>
                    </div>
                </div>
            </header>

            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-6">
                <div className="border rounded-lg overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="text-left p-3 font-medium">Product</th>
                                <th className="text-left p-3 font-medium">Category</th>
                                <th className="text-left p-3 font-medium">Brand</th>
                                <th className="text-right p-3 font-medium">Price</th>
                                <th className="text-center p-3 font-medium">Status</th>
                                <th className="text-right p-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.data.map((product) => (
                                <tr key={product.id} className="border-t hover:bg-muted/30 transition-colors">
                                    <td className="p-3">
                                        <p className="font-medium">{product.title}</p>
                                        <p className="text-xs text-muted-foreground">ID: {product.id}</p>
                                    </td>
                                    <td className="p-3">
                                        <Badge variant="secondary">{product.category}</Badge>
                                    </td>
                                    <td className="p-3 text-muted-foreground">{product.brand}</td>
                                    <td className="p-3 text-right font-mono">${product.price.toFixed(2)}</td>
                                    <td className="p-3 text-center">
                                        {product.in_stock ? (
                                            <Badge variant="success">In Stock</Badge>
                                        ) : (
                                            <Badge variant="destructive">Out of Stock</Badge>
                                        )}
                                    </td>
                                    <td className="p-3">
                                        <div className="flex items-center justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8"
                                                onClick={() => router.visit(`/admin/${product.id}/edit`)}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-destructive hover:text-destructive"
                                                onClick={() => handleDelete(product.id)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {products.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2 mt-6">
                        {products.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
