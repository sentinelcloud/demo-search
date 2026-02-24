import { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import echo from '@/echo';
import {
    ArrowLeft,
    Activity,
    Users,
    Eye,
    MousePointerClick,
    RefreshCw,
    Zap,
    FileText,
    Clock,
    Radio,
    Wifi,
    WifiOff,
    CircleUser,
} from 'lucide-react';

// ─── Types ──────────────────────────────────────────────────────────────

interface HorizonStats {
    total_processes: number;
    failed_jobs: number;
    status: string;
}

interface LiveStats {
    page_views_total: number;
    page_views_this_hour: number;
    unique_visitors_today: number;
    unique_visitors_this_hour: number;
    event_pageviews: number;
    event_impressions: number;
    event_clicks: number;
    last_event_at: string | null;
    timestamp: string;
}

interface PageViewEntry {
    id: number;
    visitor_id: number;
    session_id: string;
    path: string;
    title: string | null;
    referrer: string | null;
    created_at: string;
    visitor?: { id: number; fingerprint: string } | null;
}

interface AnalyticsEvent {
    id: number;
    event_name: string;
    product_id: number | null;
    path: string;
    metadata: Record<string, unknown> | null;
    created_at: string;
    product?: { id: number; title: string } | null;
}

interface AnalyticsProps {
    recentPageViews: PageViewEntry[];
    recentEvents: AnalyticsEvent[];
    lastHour: {
        page_views: number;
        events: number;
        visitors: number;
    };
    horizonStats: HorizonStats;
    viewer: PresenceViewer;
}

interface PresenceViewer {
    id: string;
    name: string;
    color: string;
}

// ─── Helper ─────────────────────────────────────────────────────────────

function StatCard({ title, value, icon: Icon, description }: {
    title: string;
    value: string | number;
    icon: React.ElementType;
    description?: string;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <Icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{typeof value === 'number' ? value.toLocaleString() : value}</div>
                {description && <p className="text-xs text-muted-foreground mt-1">{description}</p>}
            </CardContent>
        </Card>
    );
}

function timeAgo(dateStr: string): string {
    const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
    if (diff < 5) return 'just now';
    if (diff < 60) return `${Math.floor(diff)}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    return `${Math.floor(diff / 3600)}h ago`;
}

// ─── Main Component ─────────────────────────────────────────────────────

export default function Analytics({ recentPageViews, recentEvents, lastHour, horizonStats, viewer }: AnalyticsProps) {
    const [liveStats, setLiveStats] = useState<LiveStats | null>(null);
    const [eventFeed, setEventFeed] = useState<AnalyticsEvent[]>(recentEvents);
    const [isConnected, setIsConnected] = useState(false);
    const [wsMessageCount, setWsMessageCount] = useState(0);
    const [viewers, setViewers] = useState<PresenceViewer[]>([]);
    const channelRef = useRef<ReturnType<typeof echo.channel> | null>(null);

    // Fetch initial live stats on mount (one-time HTTP, then WebSocket takes over)
    useEffect(() => {
        fetch('/admin/analytics/live')
            .then(res => res.json())
            .then(setLiveStats)
            .catch(() => {});
    }, []);

    // Subscribe to Reverb WebSocket channel
    useEffect(() => {
        const channel = echo.channel('analytics.dashboard');
        channelRef.current = channel;

        // Listen for live stats updates (throttled to 1/sec on server)
        channel.listen('.stats.updated', (data: LiveStats) => {
            setLiveStats(data);
            setWsMessageCount(prev => prev + 1);
        });

        // Listen for individual analytics events
        channel.listen('.event.processed', (data: AnalyticsEvent) => {
            setEventFeed(prev => [data, ...prev].slice(0, 50));
            setWsMessageCount(prev => prev + 1);
        });

        // Track connection state
        const pusher = echo.connector?.pusher;
        if (pusher) {
            pusher.connection.bind('connected', () => setIsConnected(true));
            pusher.connection.bind('disconnected', () => setIsConnected(false));
            pusher.connection.bind('error', () => setIsConnected(false));
            // Check if already connected
            if (pusher.connection.state === 'connected') {
                setIsConnected(true);
            }
        }

        // Join presence channel for live viewer tracking
        echo.join('analytics.viewers')
            .here((users: PresenceViewer[]) => {
                setViewers(users);
            })
            .joining((user: PresenceViewer) => {
                setViewers(prev => [...prev.filter(v => v.id !== user.id), user]);
            })
            .leaving((user: PresenceViewer) => {
                setViewers(prev => prev.filter(v => v.id !== user.id));
            });

        return () => {
            echo.leaveChannel('analytics.dashboard');
            echo.leave('analytics.viewers');
            channelRef.current = null;
        };
    }, []);

    return (
        <div className="min-h-screen bg-background">
            {/* Header */}
            <header className="border-b sticky top-0 z-30 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <div className="flex items-center gap-4">
                            <Link href="/admin" className="text-muted-foreground hover:text-foreground transition-colors">
                                <ArrowLeft className="h-5 w-5" />
                            </Link>
                            <h1 className="text-xl font-bold flex items-center gap-2">
                                <Zap className="h-5 w-5 text-amber-500" />
                                Realtime Dashboard
                            </h1>
                            {isConnected ? (
                                <Badge variant="success" className="text-[10px] flex items-center gap-1">
                                    <Wifi className="h-3 w-3" />
                                    WebSocket
                                </Badge>
                            ) : (
                                <Badge variant="destructive" className="text-[10px] flex items-center gap-1">
                                    <WifiOff className="h-3 w-3" />
                                    Disconnected
                                </Badge>
                            )}
                            <Badge variant={horizonStats.status === 'running' ? 'success' : 'destructive'} className="text-[10px]">
                                Horizon: {horizonStats.status}
                            </Badge>
                        </div>

                        <div className="flex items-center gap-2">
                            <Link href="/admin/reports">
                                <Button size="sm" variant="outline">
                                    <FileText className="h-3.5 w-3.5 mr-1.5" />
                                    Reports
                                </Button>
                            </Link>
                            <Badge variant="outline" className="text-[10px] font-mono">
                                <Radio className="h-3 w-3 mr-1" />
                                {wsMessageCount} msgs
                            </Badge>
                        </div>
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 space-y-6">
                {/* ─── Presence: Who's Watching ───────────────────────────── */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm flex items-center gap-2">
                            <CircleUser className="h-4 w-4 text-violet-500" />
                            Who&apos;s Watching
                            <Badge variant="outline" className="text-[10px] ml-1">
                                {viewers.length} viewer{viewers.length !== 1 ? 's' : ''}
                            </Badge>
                            <span className="text-[10px] font-normal text-muted-foreground ml-auto">Presence Channel</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-3">
                            {viewers.length > 0 ? (
                                viewers.map(v => (
                                    <div
                                        key={v.id}
                                        className="flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs"
                                        style={{ borderColor: v.color + '40' }}
                                    >
                                        <div
                                            className="h-6 w-6 rounded-full flex items-center justify-center text-white font-bold text-[10px] shrink-0"
                                            style={{ backgroundColor: v.color }}
                                        >
                                            {v.name.split(' ').pop()?.charAt(0) ?? '?'}
                                        </div>
                                        <span className="font-medium">
                                            {v.name}
                                            {v.id === viewer.id && (
                                                <span className="text-muted-foreground font-normal ml-1">(you)</span>
                                            )}
                                        </span>
                                        <span className="relative flex h-2 w-2">
                                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style={{ backgroundColor: v.color }} />
                                            <span className="relative inline-flex rounded-full h-2 w-2" style={{ backgroundColor: v.color }} />
                                        </span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-xs text-muted-foreground">
                                    Connecting to presence channel...
                                </p>
                            )}
                        </div>
                        <p className="text-[10px] text-muted-foreground mt-3">
                            Each browser tab gets a unique anonymous identity via session. Open multiple tabs to see presence in action.
                        </p>
                    </CardContent>
                </Card>

                {/* ─── Live Redis Counters ────────────────────────────────── */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <StatCard
                        title="Unique Visitors Today"
                        value={liveStats?.unique_visitors_today ?? 0}
                        icon={Users}
                        description="HyperLogLog estimate"
                    />
                    <StatCard
                        title="Page Views (all time)"
                        value={liveStats?.page_views_total ?? 0}
                        icon={Eye}
                        description="Redis INCR counter"
                    />
                    <StatCard
                        title="This Hour"
                        value={liveStats?.page_views_this_hour ?? 0}
                        icon={Clock}
                        description={`${liveStats?.unique_visitors_this_hour ?? 0} unique visitors`}
                    />
                    <StatCard
                        title="Last Event"
                        value={liveStats?.last_event_at ? timeAgo(liveStats.last_event_at) : '—'}
                        icon={Zap}
                        description="Most recent tracked event"
                    />
                </div>

                {/* ─── Event Type Breakdown (Redis) ──────────────────────── */}
                <div className="grid grid-cols-3 gap-4">
                    <StatCard
                        title="Pageviews"
                        value={liveStats?.event_pageviews ?? 0}
                        icon={Eye}
                        description="pageview events"
                    />
                    <StatCard
                        title="Impressions"
                        value={liveStats?.event_impressions ?? 0}
                        icon={Activity}
                        description="product_impression events"
                    />
                    <StatCard
                        title="Clicks"
                        value={liveStats?.event_clicks ?? 0}
                        icon={MousePointerClick}
                        description="product_click events"
                    />
                </div>

                {/* ─── Last Hour (DB) ────────────────────────────────────── */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm flex items-center gap-2">
                            <Clock className="h-4 w-4" />
                            Last Hour (PostgreSQL)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-3 gap-6 text-center">
                            <div>
                                <div className="text-3xl font-bold">{lastHour.page_views.toLocaleString()}</div>
                                <p className="text-xs text-muted-foreground mt-1">Page Views</p>
                            </div>
                            <div>
                                <div className="text-3xl font-bold">{lastHour.events.toLocaleString()}</div>
                                <p className="text-xs text-muted-foreground mt-1">Events</p>
                            </div>
                            <div>
                                <div className="text-3xl font-bold">{lastHour.visitors.toLocaleString()}</div>
                                <p className="text-xs text-muted-foreground mt-1">Active Visitors</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* ─── Two-column: Event Feed + Page Views ───────────────── */}
                <div className="grid lg:grid-cols-2 gap-6">
                    {/* Live Event Feed */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm flex items-center gap-2">
                                <Zap className="h-4 w-4 text-amber-500" />
                                Live Event Feed
                                {isConnected && (
                                    <span className="relative flex h-2 w-2">
                                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75" />
                                        <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500" />
                                    </span>
                                )}
                                <span className="text-[10px] font-normal text-muted-foreground ml-auto">via Reverb</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-1 max-h-96 overflow-y-auto">
                                {eventFeed.length > 0 ? (
                                    eventFeed.map(event => (
                                        <div key={event.id} className="flex items-center gap-2 text-xs py-1.5 border-b last:border-0">
                                            <Badge
                                                variant={
                                                    event.event_name === 'product_click' ? 'default' :
                                                    event.event_name === 'product_impression' ? 'secondary' : 'outline'
                                                }
                                                className="text-[9px] px-1.5 shrink-0 w-20 justify-center"
                                            >
                                                {event.event_name.replace('product_', '')}
                                            </Badge>
                                            <span className="truncate text-muted-foreground font-mono">{event.path}</span>
                                            {event.product && (
                                                <span className="truncate font-medium shrink-0 max-w-32">{event.product.title}</span>
                                            )}
                                            <span className="ml-auto text-muted-foreground shrink-0">
                                                {timeAgo(event.created_at)}
                                            </span>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm text-muted-foreground">No events yet. Browse the search page to generate events.</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recent Page Views */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm flex items-center gap-2">
                                <Eye className="h-4 w-4 text-blue-500" />
                                Recent Page Views
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-1 max-h-96 overflow-y-auto">
                                {recentPageViews.length > 0 ? (
                                    recentPageViews.map(pv => (
                                        <div key={pv.id} className="flex items-center gap-2 text-xs py-1.5 border-b last:border-0">
                                            <span className="truncate font-mono text-foreground">{pv.path}</span>
                                            {pv.title && (
                                                <span className="truncate text-muted-foreground shrink-0 max-w-40">
                                                    {pv.title}
                                                </span>
                                            )}
                                            {pv.visitor && (
                                                <span className="text-[9px] font-mono text-muted-foreground shrink-0">
                                                    {pv.visitor.fingerprint.slice(0, 8)}
                                                </span>
                                            )}
                                            <span className="ml-auto text-muted-foreground shrink-0">
                                                {timeAgo(pv.created_at)}
                                            </span>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm text-muted-foreground">No page views yet.</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* ─── Horizon & Queue Health ─────────────────────────────── */}
                <div className="grid lg:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Horizon & Queue Health</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-3 gap-4">
                                <div className="text-center">
                                    <div className="text-2xl font-bold">{horizonStats.total_processes}</div>
                                    <p className="text-xs text-muted-foreground">Workers</p>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-red-500">{horizonStats.failed_jobs}</div>
                                    <p className="text-xs text-muted-foreground">Failed Jobs</p>
                                </div>
                                <div className="text-center">
                                    <Badge variant={horizonStats.status === 'running' ? 'success' : 'destructive'}>
                                        {horizonStats.status}
                                    </Badge>
                                    <p className="text-xs text-muted-foreground mt-1">Status</p>
                                </div>
                            </div>
                            <div className="mt-4 pt-4 border-t">
                                <a href="/horizon" target="_blank" className="text-xs text-primary hover:underline">
                                    Open Horizon Dashboard →
                                </a>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Reverb WebSocket Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm flex items-center gap-2">
                                <Radio className="h-4 w-4 text-amber-500" />
                                Reverb WebSocket
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-3 gap-4">
                                <div className="text-center">
                                    <Badge variant={isConnected ? 'success' : 'destructive'}>
                                        {isConnected ? 'Connected' : 'Disconnected'}
                                    </Badge>
                                    <p className="text-xs text-muted-foreground mt-1">Status</p>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold">{wsMessageCount}</div>
                                    <p className="text-xs text-muted-foreground">Messages Received</p>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold font-mono text-xs mt-1">analytics.dashboard</div>
                                    <p className="text-xs text-muted-foreground mt-1">Channel</p>
                                </div>
                            </div>
                            <div className="mt-4 pt-4 border-t space-y-1 text-xs text-muted-foreground">
                                <p>• Events pushed from Horizon jobs via <span className="font-mono text-foreground">ShouldBroadcast</span></p>
                                <p>• <span className="font-mono text-foreground">stats.updated</span> — live Redis counters (throttled 1/sec)</p>
                                <p>• <span className="font-mono text-foreground">event.processed</span> — individual analytics events</p>
                                <p>• <span className="font-mono text-foreground">analytics.viewers</span> — presence channel (who&apos;s watching)</p>
                                <p>• No HTTP polling — pure WebSocket push via Laravel Reverb</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* ─── Architecture Info ──────────────────────────────────── */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm">🏗️ Architecture Overview (Demo)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs text-muted-foreground">
                            <div>
                                <h4 className="font-semibold text-foreground mb-1">Data Pipeline</h4>
                                <ul className="space-y-0.5">
                                    <li>→ Frontend tracker (buffered, sendBeacon)</li>
                                    <li>→ POST /api/track (batched events)</li>
                                    <li>→ Laravel Events dispatched</li>
                                    <li>→ Listeners dispatch Jobs → analytics queue</li>
                                    <li>→ Jobs process via Horizon workers</li>
                                    <li className="font-semibold text-amber-500">→ Jobs broadcast via Reverb WebSocket</li>
                                    <li className="font-semibold text-amber-500">→ Echo receives push on dashboard</li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="font-semibold text-foreground mb-1">Privacy</h4>
                                <ul className="space-y-0.5">
                                    <li>• No cookies or local storage</li>
                                    <li>• Daily rotating salt (Pirsch method)</li>
                                    <li>• SHA-256(IP + UA + salt) fingerprint</li>
                                    <li>• IP hash stored, raw IP discarded</li>
                                    <li>• X-Forwarded-For for K8s/Traefik</li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="font-semibold text-foreground mb-1">Laravel Features</h4>
                                <ul className="space-y-0.5">
                                    <li>• Horizon (3 supervisors, 3 queues)</li>
                                    <li>• Job Batches (Bus::batch)</li>
                                    <li>• Job Chains (→ UpdateRealtimeCounters)</li>
                                    <li>• Events & Listeners</li>
                                    <li>• Task Scheduling (6 scheduled jobs)</li>
                                    <li>• Redis HyperLogLog for uniques</li>
                                    <li>• Tagged cache (device detection)</li>
                                    <li className="font-semibold text-amber-500">• Reverb WebSockets (live push)</li>
                                    <li className="font-semibold text-amber-500">• Broadcasting (ShouldBroadcast)</li>
                                    <li className="font-semibold text-violet-500">• Presence Channel (who&apos;s here)</li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="font-semibold text-foreground mb-1">Storage</h4>
                                <ul className="space-y-0.5">
                                    <li>• PostgreSQL 17 (analytics tables)</li>
                                    <li>• Redis 7 (queue, cache, counters)</li>
                                    <li>• Meilisearch (product search)</li>
                                    <li>• Pre-aggregated stats (hourly)</li>
                                    <li>• Raw data pruned after 30 days</li>
                                </ul>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </main>
        </div>
    );
}
