import type { SearchMeta as SearchMetaType } from '@/types';
import { Zap } from 'lucide-react';

interface SearchMetaProps {
    meta: SearchMetaType;
    isSearching: boolean;
}

export function SearchMeta({ meta, isSearching }: SearchMetaProps) {
    if (isSearching) {
        return <p className="text-sm text-muted-foreground">Searching...</p>;
    }

    return (
        <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <span>
                <strong className="text-foreground">{meta.total_hits.toLocaleString()}</strong> results
            </span>
            {meta.processing_time_ms > 0 && (
                <span className="flex items-center gap-1">
                    <Zap className="h-3 w-3" />
                    {meta.processing_time_ms}ms
                </span>
            )}
        </div>
    );
}
