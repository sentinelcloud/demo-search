import { useForm, router } from '@inertiajs/react';
import type { Product } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Save } from 'lucide-react';

interface AdminFormProps {
    product: Product | null;
    categories: string[];
    brands: string[];
}

export default function AdminForm({ product, categories, brands }: AdminFormProps) {
    const isEditing = !!product;

    const { data, setData, post, put, processing, errors } = useForm({
        title: product?.title || '',
        description: product?.description || '',
        category: product?.category || '',
        brand: product?.brand || '',
        tags: product?.tags?.join(', ') || '',
        price: product?.price?.toString() || '',
        rating: product?.rating?.toString() || '',
        in_stock: product?.in_stock ?? true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const payload = {
            ...data,
            tags: data.tags.split(',').map((t) => t.trim()).filter(Boolean),
            price: parseFloat(data.price),
            rating: parseFloat(data.rating),
        };

        if (isEditing) {
            put(`/admin/${product!.id}`, { data: payload } as any);
        } else {
            post('/admin', { data: payload } as any);
        }
    };

    return (
        <div className="min-h-screen bg-background">
            <header className="border-b">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center gap-4">
                        <a href="/admin" className="text-muted-foreground hover:text-foreground transition-colors">
                            <ArrowLeft className="h-5 w-5" />
                        </a>
                        <h1 className="text-xl font-bold">
                            {isEditing ? 'Edit Product' : 'New Product'}
                        </h1>
                    </div>
                </div>
            </header>

            <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-6">
                <Card>
                    <CardHeader>
                        <CardTitle>
                            {isEditing
                                ? 'Update product details. Changes sync to Meilisearch automatically.'
                                : 'Create a new product. It will be indexed in Meilisearch instantly.'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="e.g., TechPro Wireless Bluetooth Headphones"
                                />
                                {errors.title && <p className="text-sm text-destructive">{errors.title}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={4}
                                    className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    placeholder="Product description..."
                                />
                                {errors.description && <p className="text-sm text-destructive">{errors.description}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="category">Category</Label>
                                    <Input
                                        id="category"
                                        value={data.category}
                                        onChange={(e) => setData('category', e.target.value)}
                                        list="categories"
                                        placeholder="e.g., Electronics"
                                    />
                                    <datalist id="categories">
                                        {categories.map((c) => (
                                            <option key={c} value={c} />
                                        ))}
                                    </datalist>
                                    {errors.category && <p className="text-sm text-destructive">{errors.category}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="brand">Brand</Label>
                                    <Input
                                        id="brand"
                                        value={data.brand}
                                        onChange={(e) => setData('brand', e.target.value)}
                                        list="brands"
                                        placeholder="e.g., TechPro"
                                    />
                                    <datalist id="brands">
                                        {brands.map((b) => (
                                            <option key={b} value={b} />
                                        ))}
                                    </datalist>
                                    {errors.brand && <p className="text-sm text-destructive">{errors.brand}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="tags">Tags (comma-separated)</Label>
                                <Input
                                    id="tags"
                                    value={data.tags}
                                    onChange={(e) => setData('tags', e.target.value)}
                                    placeholder="e.g., wireless, bluetooth, portable"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="price">Price ($)</Label>
                                    <Input
                                        id="price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.price}
                                        onChange={(e) => setData('price', e.target.value)}
                                        placeholder="29.99"
                                    />
                                    {errors.price && <p className="text-sm text-destructive">{errors.price}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="rating">Rating (0-5)</Label>
                                    <Input
                                        id="rating"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="5"
                                        value={data.rating}
                                        onChange={(e) => setData('rating', e.target.value)}
                                        placeholder="4.5"
                                    />
                                    {errors.rating && <p className="text-sm text-destructive">{errors.rating}</p>}
                                </div>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="in_stock"
                                    checked={data.in_stock}
                                    onCheckedChange={(checked) => setData('in_stock', !!checked)}
                                />
                                <Label htmlFor="in_stock">In Stock</Label>
                            </div>

                            <div className="flex items-center gap-2 pt-4">
                                <Button type="submit" disabled={processing}>
                                    <Save className="h-4 w-4 mr-2" />
                                    {processing ? 'Saving...' : isEditing ? 'Update Product' : 'Create Product'}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.visit('/admin')}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
