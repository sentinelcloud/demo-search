/**
 * Analytics Tracker
 *
 * Buffers tracking events and flushes them in batches via sendBeacon or fetch.
 * Designed for minimal performance impact — events are queued and sent every 2 seconds.
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
    private buffer: TrackingEvent[] = [];
    private flushInterval: ReturnType<typeof setInterval> | null = null;
    private endpoint = '/api/track';
    private maxBufferSize = 50;
    private flushIntervalMs = 2000;

    constructor() {
        this.startFlushing();

        // Flush on page unload
        if (typeof window !== 'undefined') {
            window.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    this.flush();
                }
            });
            window.addEventListener('beforeunload', () => this.flush());
        }
    }

    private startFlushing(): void {
        if (this.flushInterval) return;
        this.flushInterval = setInterval(() => this.flush(), this.flushIntervalMs);
    }

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
        this.push({
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
        this.push({
            type: 'impression',
            timestamp: new Date().toISOString(),
            product_id: productId,
            path: path ?? window.location.pathname,
        });
    }

    trackClick(productId: number, metadata?: Record<string, unknown>): void {
        this.push({
            type: 'click',
            timestamp: new Date().toISOString(),
            product_id: productId,
            path: window.location.pathname,
            metadata,
        });
    }

    private push(event: TrackingEvent): void {
        this.buffer.push(event);
        if (this.buffer.length >= this.maxBufferSize) {
            this.flush();
        }
    }

    flush(): void {
        if (this.buffer.length === 0) return;

        const events = [...this.buffer];
        this.buffer = [];

        const payload = JSON.stringify({ events });

        // Prefer sendBeacon for reliability during page unload
        if (typeof navigator.sendBeacon === 'function') {
            const blob = new Blob([payload], { type: 'application/json' });
            const sent = navigator.sendBeacon(this.endpoint, blob);
            if (!sent) {
                // Fallback to fetch if sendBeacon fails
                this.sendViaFetch(payload);
            }
        } else {
            this.sendViaFetch(payload);
        }
    }

    private sendViaFetch(payload: string): void {
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
        this.flush();
        if (this.flushInterval) {
            clearInterval(this.flushInterval);
            this.flushInterval = null;
        }
    }
}

// Singleton instance
export const tracker = new Tracker();
export type { TrackingEvent };
