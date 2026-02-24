import { useEffect, useRef, useCallback } from 'react';
import { tracker } from '@/lib/tracker';

/**
 * Track product impressions using IntersectionObserver.
 * Each product is tracked once per page load when it scrolls into the viewport.
 */
export function useImpressionTracking() {
    const trackedIds = useRef<Set<number>>(new Set());
    const observerRef = useRef<IntersectionObserver | null>(null);

    useEffect(() => {
        observerRef.current = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const productId = Number(entry.target.getAttribute('data-product-id'));
                        if (productId && !trackedIds.current.has(productId)) {
                            trackedIds.current.add(productId);
                            tracker.trackImpression(productId);
                        }
                    }
                });
            },
            { threshold: 0.5 } // 50% visible
        );

        return () => {
            observerRef.current?.disconnect();
        };
    }, []);

    const observeProduct = useCallback((element: HTMLElement | null) => {
        if (element && observerRef.current) {
            observerRef.current.observe(element);
        }
    }, []);

    const resetTracking = useCallback(() => {
        trackedIds.current.clear();
    }, []);

    return { observeProduct, resetTracking };
}
