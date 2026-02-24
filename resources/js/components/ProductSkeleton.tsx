import { Skeleton } from '@/components/ui/skeleton';

interface ProductSkeletonProps {
    viewMode: 'grid' | 'list';
}

export function ProductSkeleton({ viewMode }: ProductSkeletonProps) {
    if (viewMode === 'list') {
        return (
            <div className="border rounded-lg p-4 flex gap-4">
                <div className="flex-1 space-y-2">
                    <Skeleton className="h-4 w-3/4" />
                    <Skeleton className="h-3 w-1/3" />
                    <Skeleton className="h-3 w-full" />
                    <Skeleton className="h-3 w-2/3" />
                </div>
                <div className="space-y-2 shrink-0">
                    <Skeleton className="h-6 w-16" />
                    <Skeleton className="h-3 w-12" />
                </div>
            </div>
        );
    }

    return (
        <div className="border rounded-lg p-4 space-y-3">
            <div className="flex justify-between">
                <Skeleton className="h-5 w-20" />
                <Skeleton className="h-5 w-16" />
            </div>
            <Skeleton className="h-4 w-4/5" />
            <Skeleton className="h-3 w-1/3" />
            <Skeleton className="h-3 w-full" />
            <Skeleton className="h-3 w-3/4" />
            <div className="flex justify-between pt-2">
                <Skeleton className="h-6 w-16" />
                <Skeleton className="h-4 w-24" />
            </div>
        </div>
    );
}
