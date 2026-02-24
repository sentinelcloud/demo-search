/**
 * Analytics Tracker
 *
 * Fires tracking events immediately via fetch. Each event is sent as a single-item
 * batch to /api/track the moment it occurs — no buffering or debouncing.
 * Uses sendBeacon only on page unload for any stragglers.
 */

interface TrackingEvent {
    type: 'pageview' | 'impression' | 'click';
    timestamp: string;
    path?: string;
    title?: string;
    referrer?: string;
    product_id?: number;
    screen_width?: number;
    screen_height?: number;
    language?: string;
    utm_source?: string;
    utm_medium?: string;
    utm_campaign?: string;
    utm_term?: string;
    utm_content?: string;
    metadata?: Record<string, unknown>;
}

class Tracker {
    private endpoint = '/api/track';

    private getUTMParams(): Record<string, string> {
        if (typeof window === 'undefined') return {};
        const params = new URLSearchParams(window.location.search);
        const utm: Record<string, string> = {};
        for (const key of ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']) {
            const value = params.get(key);
            if (value) utm[key] = value;
        }
        return utm;
    }

    trackPageView(path?: string, title?: string): void {
        const utm = this.getUTMParams();
        this.send({
            type: 'pageview',
            timestamp: new Date().toISOString(),
            path: path ?? window.location.pathname,
            title: title ?? document.title,
            referrer: document.referrer || undefined,
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            language: navigator.language,
            ...utm,
        });
    }

    trackImpression(productId: number, path?: string): void {
        this.send({
            type: 'impression',
            timestamp: new Date().toISOString(),
            product_id: productId,
            path: path ?? window.location.pathname,
        });
    }

    trackClick(productId: number, metadata?: Record<string, unknown>): void {
        this.send({
            type: 'click',
            timestamp: new Date().toISOString(),
            product_id: productId,
            path: window.location.pathname,
            metadata,
        });
    }

    private send(event: TrackingEvent): void {
        const payload = JSON.stringify({ events: [event] });

        fetch(this.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: payload,
            keepalive: true,
        }).catch(() => {
            // Silently fail — analytics should never break the app
        });
    }

    destroy(): void {
        // No-op — nothing to clean up with immediate sending
    }
}

// Singleton instance
export const tracker = new Tracker();
export type { TrackingEvent };
