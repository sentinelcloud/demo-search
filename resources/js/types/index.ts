export interface Product {
    id: number;
    title: string;
    description: string;
    category: string;
    brand: string;
    tags: string[];
    price: number;
    rating: number;
    in_stock: boolean;
    image_url: string | null;
    created_at: number;
    _formatted?: {
        title?: string;
        description?: string;
        [key: string]: unknown;
    };
}

export interface SearchQuery {
    q: string;
    category: string | null;
    brand: string | null;
    tag: string | null;
    in_stock: string | null;
    price_min: string | null;
    price_max: string | null;
    sort: string;
    page: number;
}

export interface SearchMeta {
    total_hits: number;
    page: number;
    per_page: number;
    total_pages: number;
    processing_time_ms: number;
}

export interface FacetDistribution {
    category?: Record<string, number>;
    brand?: Record<string, number>;
    tags?: Record<string, number>;
    in_stock?: Record<string, number>;
}

export interface SearchPageProps {
    products: Product[];
    facets: FacetDistribution;
    query: SearchQuery;
    meta: SearchMeta;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}
