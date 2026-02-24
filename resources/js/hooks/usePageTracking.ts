import { useEffect, useRef } from 'react';
import { tracker } from '@/lib/tracker';

/**
 * Track page views automatically when the path changes.
 * Uses Inertia router — monitors URL pathname for changes.
 */
export function usePageTracking(): void {
    const lastPath = useRef<string>('');

    useEffect(() => {
        const currentPath = window.location.pathname + window.location.search;

        if (currentPath !== lastPath.current) {
            lastPath.current = currentPath;
            tracker.trackPageView(window.location.pathname, document.title);
        }
    });
}
