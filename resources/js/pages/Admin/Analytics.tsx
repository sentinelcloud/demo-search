import { useCallback, useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    ArrowLeft,
    Activity,
    Users,
    Eye,
    MousePointerClick,
    TrendingUp,
    Timer,
    BarChart3,
    RefreshCw,
    Zap,
    Globe,
    Monitor,
    Smartphone,
    Tablet,
} from 'lucide-react';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler,
    TimeScale,
} from 'chart.js';
import 'chartjs-adapter-date-fns';
import { Line, Doughnut, Bar } from 'react-chartjs-2';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler,
    TimeScale,
);

// ─── Types ──────────────────────────────────────────────────────────────

interface OverviewStats {
    total_visitors: number;
    new_visitors: number;
    total_sessions: number;
    total_page_views: number;
    total_bounces: number;
    bounce_rate: number;
    avg_duration: number;
}

interface TimeSeriesPoint {
    date: string;
    visitors: number;
    page_views: number;
    sessions: number;
}

interface PageStat {
    path: string;
    total_views: number;
    total_visitors: number;
}

interface ReferrerStat {
    referrer_name: string;
    total_visitors: number;
    total_sessions: number;
}

interface DeviceStat {
    browser?: string;
    os?: string;
    device_type?: string;
    total_visitors: number;
}

interface TopProduct {
    product: { id: number; name: string; brand: string; price: number } | null;
    impressions: number;
    clicks: number;
    ctr: number;
}

interface ReportEntry {
    id: number;
    report_type: string;
    period_start: string;
    period_end: string;
    generated_at: string;
    generation_time_ms: number;
    job_batch_id: string | null;
}

interface HorizonStats {
    total_processes: number;
    failed_jobs: number;
    status: string;
}

interface LiveStats {
    active_visitors: number;
    pageviews_today: number;
    sessions_today: number;
    events_today: number;
    unique_visitors_today: number;
    timestamp: string;
}

interface AnalyticsEvent {
    id: number;
    event_name: string;
    product_id: number | null;
    path: string;
    metadata: Record<string, unknown> | null;
    created_at: string;
    product?: { id: number; name: string } | null;
}

interface AnalyticsProps {
    period: string;
    overview: OverviewStats;
    timeSeries: TimeSeriesPoint[];
    topPages: PageStat[];
    topReferrers: ReferrerStat[];
    browsers: DeviceStat[];
    operatingSystems: DeviceStat[];
    deviceTypes: DeviceStat[];
    topProducts: TopProduct[];
    recentReports: ReportEntry[];
    horizonStats: HorizonStats;
}

// ─── Helper Components ──────────────────────────────────────────────────

function StatCard({
    title,
    value,
    icon: Icon,
    description,
    trend,
}: {
    title: string;
    value: string | number;
    icon: React.ElementType;
    description?: string;
    trend?: 'up' | 'down' | 'neutral';
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <Icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{typeof value === 'number' ? value.toLocaleString() : value}</div>
                {description && (
                    <p className="text-xs text-muted-foreground mt-1">
                        {trend === 'up' && <TrendingUp className="inline h-3 w-3 text-green-500 mr-1" />}
                        {description}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

const CHART_COLORS = [
    'rgba(59, 130, 246, 0.8)',   // blue
    'rgba(16, 185, 129, 0.8)',   // green
    'rgba(249, 115, 22, 0.8)',   // orange
    'rgba(139, 92, 246, 0.8)',   // purple
    'rgba(236, 72, 153, 0.8)',   // pink
    'rgba(245, 158, 11, 0.8)',   // amber
    'rgba(20, 184, 166, 0.8)',   // teal
    'rgba(239, 68, 68, 0.8)',    // red
    'rgba(107, 114, 128, 0.8)',  // gray
    'rgba(34, 197, 94, 0.8)',    // lime
];

function formatDuration(seconds: number): string {
    if (seconds < 60) return `${seconds}s`;
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}m ${secs}s`;
}

function getDeviceIcon(type: string) {
    switch (type?.toLowerCase()) {
        case 'desktop': return Monitor;
        case 'smartphone': return Smartphone;
        case 'tablet': return Tablet;
        default: return Globe;
    }
}

// ─── Main Component ─────────────────────────────────────────────────────

export default function Analytics({
    period,
    overview,
    timeSeries,
    topPages,
    topReferrers,
    browsers,
    operatingSystems,
    deviceTypes,
    topProducts,
    recentReports,
    horizonStats,
}: AnalyticsProps) {
    const [liveStats, setLiveStats] = useState<LiveStats | null>(null);
    const [eventFeed, setEventFeed] = useState<AnalyticsEvent[]>([]);
    const [isPolling, setIsPolling] = useState(true);

    // Poll live stats every 5 seconds
    useEffect(() => {
        if (!isPolling) return;

        const fetchLive = () => {
            fetch('/admin/analytics/live')
                .then(res => res.json())
                .then(setLiveStats)
                .catch(() => {});
        };

        fetchLive();
        const interval = setInterval(fetchLive, 5000);
        return () => clearInterval(interval);
    }, [isPolling]);

    // Poll event feed every 10 seconds
    useEffect(() => {
        if (!isPolling) return;

        const fetchEvents = () => {
            fetch('/admin/analytics/events?limit=20')
                .then(res => res.json())
                .then(setEventFeed)
                .catch(() => {});
        };

        fetchEvents();
        const interval = setInterval(fetchEvents, 10000);
        return () => clearInterval(interval);
    }, [isPolling]);

    const changePeriod = useCallback((newPeriod: string) => {
        router.get('/admin/analytics', { period: newPeriod }, { preserveState: true });
    }, []);

    // ─── Chart Configs ──────────────────────────────────────────────────

    const timeSeriesData = {
        labels: timeSeries.map(p => p.date),
        datasets: [
            {
                label: 'Visitors',
                data: timeSeries.map(p => p.visitors),
                borderColor: 'rgba(59, 130, 246, 1)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.3,
            },
            {
                label: 'Page Views',
                data: timeSeries.map(p => p.page_views),
                borderColor: 'rgba(16, 185, 129, 1)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.3,
            },
            {
                label: 'Sessions',
                data: timeSeries.map(p => p.sessions),
                borderColor: 'rgba(249, 115, 22, 1)',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                fill: true,
                tension: 0.3,
            },
        ],
    };

    const timeSeriesOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' as const } },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true },
        },
    };

    const browserData = {
        labels: browsers.map(b => b.browser || 'Unknown'),
        datasets: [{
            data: browsers.map(b => b.total_visitors),
            backgroundColor: CHART_COLORS.slice(0, browsers.length),
        }],
    };

    const deviceTypeData = {
        labels: deviceTypes.map(d => d.device_type || 'Unknown'),
        datasets: [{
            data: deviceTypes.map(d => d.total_visitors),
            backgroundColor: CHART_COLORS.slice(0, deviceTypes.length),
        }],
    };

    const osData = {
        labels: operatingSystems.map(o => o.os || 'Unknown'),
        datasets: [{
            label: 'Visitors',
            data: operatingSystems.map(o => o.total_visitors),
            backgroundColor: CHART_COLORS.slice(0, operatingSystems.length),
        }],
    };

    const osOptions = {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y' as const,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true },
        },
    };

    const doughnutOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' as const, labels: { boxWidth: 12, padding: 8 } },
        },
    };

    // ─── Render ─────────────────────────────────────────────────────────

    return (
        <div className="min-h-screen bg-background">
            {/* Header */}
            <header className="border-b sticky top-0 z-30 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <div className="flex items-center gap-4">
                            <a href="/admin" className="text-muted-foreground hover:text-foreground transition-colors">
                                <ArrowLeft className="h-5 w-5" />
                            </a>
                            <h1 className="text-xl font-bold flex items-center gap-2">
                                <BarChart3 className="h-5 w-5" />
                                Analytics Dashboard
                            </h1>
                            <Badge variant={horizonStats.status === 'running' ? 'success' : 'destructive'} className="text-[10px]">
                                Horizon: {horizonStats.status}
                            </Badge>
                        </div>

                        <div className="flex items-center gap-2">
                            {/* Period selector */}
                            <div className="flex gap-1 bg-muted rounded-lg p-1">
                                {[
                                    { value: '1h', label: '1H' },
                                    { value: '24h', label: '24H' },
                                    { value: '7d', label: '7D' },
                                    { value: '30d', label: '30D' },
                                ].map(p => (
                                    <button
                                        key={p.value}
                                        onClick={() => changePeriod(p.value)}
                                        className={`px-3 py-1 text-xs font-medium rounded-md transition-colors ${
                                            period === p.value
                                                ? 'bg-background shadow text-foreground'
                                                : 'text-muted-foreground hover:text-foreground'
                                        }`}
                                    >
                                        {p.label}
                                    </button>
                                ))}
                            </div>

                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setIsPolling(prev => !prev)}
                            >
                                <RefreshCw className={`h-3.5 w-3.5 mr-1 ${isPolling ? 'animate-spin' : ''}`} />
                                {isPolling ? 'Live' : 'Paused'}
                            </Button>
                        </div>
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 space-y-6">
                {/* ─── Live Counters ──────────────────────────────────────── */}
                {liveStats && (
                    <div className="grid grid-cols-2 sm:grid-cols-5 gap-4">
                        <StatCard title="Active Visitors" value={liveStats.active_visitors} icon={Zap} description="Right now" />
                        <StatCard title="Unique Today" value={liveStats.unique_visitors_today} icon={Users} description="HyperLogLog" />
                        <StatCard title="Pageviews Today" value={liveStats.pageviews_today} icon={Eye} description="Redis counter" />
                        <StatCard title="Sessions Today" value={liveStats.sessions_today} icon={Activity} description="Redis counter" />
                        <StatCard title="Events Today" value={liveStats.events_today} icon={MousePointerClick} description="Redis counter" />
                    </div>
                )}

                {/* ─── Overview Stats ─────────────────────────────────────── */}
                <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4">
                    <StatCard title="Visitors" value={overview.total_visitors} icon={Users} />
                    <StatCard title="New Visitors" value={overview.new_visitors} icon={Users} />
                    <StatCard title="Sessions" value={overview.total_sessions} icon={Activity} />
                    <StatCard title="Page Views" value={overview.total_page_views} icon={Eye} />
                    <StatCard title="Bounces" value={overview.total_bounces} icon={TrendingUp} />
                    <StatCard title="Bounce Rate" value={`${overview.bounce_rate}%`} icon={TrendingUp} />
                    <StatCard title="Avg Duration" value={formatDuration(overview.avg_duration)} icon={Timer} />
                </div>

                {/* ─── Time Series Chart ──────────────────────────────────── */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm">Traffic Over Time</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-72">
                            {timeSeries.length > 0 ? (
                                <Line data={timeSeriesData} options={timeSeriesOptions} />
                            ) : (
                                <div className="flex items-center justify-center h-full text-muted-foreground text-sm">
                                    No data for this period yet. Start browsing to generate analytics.
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* ─── Two-column: Pages + Referrers ─────────────────────── */}
                <div className="grid lg:grid-cols-2 gap-6">
                    {/* Top Pages */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Top Pages</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {topPages.length > 0 ? (
                                <div className="space-y-2 max-h-80 overflow-y-auto">
                                    {topPages.map((page, i) => (
                                        <div key={page.path} className="flex items-center justify-between text-sm py-1 border-b last:border-0">
                                            <div className="flex items-center gap-2 min-w-0">
                                                <span className="text-muted-foreground text-xs w-5">{i + 1}</span>
                                                <span className="truncate font-mono text-xs">{page.path}</span>
                                            </div>
                                            <div className="flex gap-4 shrink-0 text-xs text-muted-foreground">
                                                <span>{Number(page.total_views).toLocaleString()} views</span>
                                                <span>{Number(page.total_visitors).toLocaleString()} visitors</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">No page data yet</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Top Referrers */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Top Referrers</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {topReferrers.length > 0 ? (
                                <div className="space-y-2 max-h-80 overflow-y-auto">
                                    {topReferrers.map((ref, i) => (
                                        <div key={ref.referrer_name} className="flex items-center justify-between text-sm py-1 border-b last:border-0">
                                            <div className="flex items-center gap-2 min-w-0">
                                                <span className="text-muted-foreground text-xs w-5">{i + 1}</span>
                                                <span className="truncate">{ref.referrer_name}</span>
                                            </div>
                                            <div className="flex gap-4 shrink-0 text-xs text-muted-foreground">
                                                <span>{Number(ref.total_visitors).toLocaleString()} visitors</span>
                                                <span>{Number(ref.total_sessions).toLocaleString()} sessions</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">No referrer data yet</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* ─── Three-column: Browsers, Devices, OS ───────────────── */}
                <div className="grid lg:grid-cols-3 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Browsers</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-48">
                                {browsers.length > 0 ? (
                                    <Doughnut data={browserData} options={doughnutOptions} />
                                ) : (
                                    <div className="flex items-center justify-center h-full text-muted-foreground text-sm">No data</div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Device Types</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-48">
                                {deviceTypes.length > 0 ? (
                                    <Doughnut data={deviceTypeData} options={doughnutOptions} />
                                ) : (
                                    <div className="flex items-center justify-center h-full text-muted-foreground text-sm">No data</div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Operating Systems</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-48">
                                {operatingSystems.length > 0 ? (
                                    <Bar data={osData} options={osOptions} />
                                ) : (
                                    <div className="flex items-center justify-center h-full text-muted-foreground text-sm">No data</div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* ─── Product Analytics ──────────────────────────────────── */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm">Top Products (Impressions & CTR)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {topProducts.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="pb-2 font-medium">#</th>
                                            <th className="pb-2 font-medium">Product</th>
                                            <th className="pb-2 font-medium">Brand</th>
                                            <th className="pb-2 font-medium text-right">Impressions</th>
                                            <th className="pb-2 font-medium text-right">Clicks</th>
                                            <th className="pb-2 font-medium text-right">CTR</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {topProducts.map((item, i) => (
                                            <tr key={item.product?.id ?? i} className="border-b last:border-0">
                                                <td className="py-2 text-muted-foreground">{i + 1}</td>
                                                <td className="py-2">{item.product?.name ?? 'Unknown'}</td>
                                                <td className="py-2 text-muted-foreground">{item.product?.brand ?? '-'}</td>
                                                <td className="py-2 text-right">{item.impressions.toLocaleString()}</td>
                                                <td className="py-2 text-right">{item.clicks.toLocaleString()}</td>
                                                <td className="py-2 text-right">
                                                    <Badge variant={item.ctr > 5 ? 'success' : 'secondary'} className="text-[10px]">
                                                        {item.ctr}%
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">No product analytics yet. Browse the search page to generate impressions.</p>
                        )}
                    </CardContent>
                </Card>

                {/* ─── Two-column: Live Event Feed + Reports ─────────────── */}
                <div className="grid lg:grid-cols-2 gap-6">
                    {/* Event Feed */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm flex items-center gap-2">
                                <Zap className="h-4 w-4 text-amber-500" />
                                Live Event Feed
                                {isPolling && (
                                    <span className="relative flex h-2 w-2">
                                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75" />
                                        <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500" />
                                    </span>
                                )}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-1 max-h-72 overflow-y-auto">
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
                                            <span className="truncate text-muted-foreground">{event.path}</span>
                                            {event.product && (
                                                <span className="truncate font-medium shrink-0 max-w-32">{event.product.name}</span>
                                            )}
                                            <span className="ml-auto text-muted-foreground shrink-0">
                                                {new Date(event.created_at).toLocaleTimeString()}
                                            </span>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm text-muted-foreground">No events yet. Browse the search page to generate events.</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recent Reports + Horizon Status */}
                    <div className="space-y-6">
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
                                    <a
                                        href="/horizon"
                                        target="_blank"
                                        className="text-xs text-primary hover:underline"
                                    >
                                        Open Horizon Dashboard →
                                    </a>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">Recent Reports (Job Batches)</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {recentReports.length > 0 ? (
                                    <div className="space-y-2">
                                        {recentReports.map(report => (
                                            <div key={report.id} className="flex items-center justify-between text-xs py-1 border-b last:border-0">
                                                <div>
                                                    <Badge variant="outline" className="text-[9px] mr-2">{report.report_type}</Badge>
                                                    <span className="text-muted-foreground">
                                                        {new Date(report.generated_at).toLocaleString()}
                                                    </span>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Badge variant="secondary" className="text-[9px]">
                                                        {report.generation_time_ms}ms
                                                    </Badge>
                                                    {report.job_batch_id && (
                                                        <span className="text-[9px] text-muted-foreground font-mono truncate max-w-24">
                                                            {report.job_batch_id.slice(0, 8)}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">No reports generated yet. They run every 10 minutes.</p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* ─── Architecture Info (for demo purposes) ──────────────── */}
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
                                    <li>→ Listeners dispatch Jobs →  analytics queue</li>
                                    <li>→ Jobs process via Horizon workers</li>
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
                                    <li>• Task Scheduling (5 scheduled jobs)</li>
                                    <li>• Redis HyperLogLog for uniques</li>
                                    <li>• Tagged cache (device detection)</li>
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
