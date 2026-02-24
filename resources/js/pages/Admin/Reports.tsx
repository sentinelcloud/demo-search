import { useCallback } from 'react';
import { Link, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    ArrowLeft,
    Activity,
    Users,
    Eye,
    TrendingUp,
    Timer,
    BarChart3,
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
} from 'chart.js';
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
    product: { id: number; title: string; brand: string; price: number } | null;
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

interface ReportsProps {
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
}

// ─── Helpers ────────────────────────────────────────────────────────────

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

const CHART_COLORS = [
    'rgba(59, 130, 246, 0.8)',
    'rgba(16, 185, 129, 0.8)',
    'rgba(249, 115, 22, 0.8)',
    'rgba(139, 92, 246, 0.8)',
    'rgba(236, 72, 153, 0.8)',
    'rgba(245, 158, 11, 0.8)',
    'rgba(20, 184, 166, 0.8)',
    'rgba(239, 68, 68, 0.8)',
    'rgba(107, 114, 128, 0.8)',
    'rgba(34, 197, 94, 0.8)',
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

export default function Reports({
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
}: ReportsProps) {
    const changePeriod = useCallback((newPeriod: string) => {
        router.get('/admin/reports', { period: newPeriod }, { preserveState: true });
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
        scales: { x: { beginAtZero: true } },
    };

    const doughnutOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' as const, labels: { boxWidth: 12, padding: 8 } },
        },
    };

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
                                <BarChart3 className="h-5 w-5" />
                                Analytics Reports
                            </h1>
                        </div>

                        <div className="flex items-center gap-2">
                            <Link href="/admin/analytics">
                                <Button size="sm" variant="outline">
                                    <Zap className="h-3.5 w-3.5 mr-1.5" />
                                    Realtime
                                </Button>
                            </Link>

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
                        </div>
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 space-y-6">
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
                                            <th className="pb-2 font-medium text-right">Price</th>
                                            <th className="pb-2 font-medium text-right">Impressions</th>
                                            <th className="pb-2 font-medium text-right">Clicks</th>
                                            <th className="pb-2 font-medium text-right">CTR</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {topProducts.map((item, i) => (
                                            <tr key={item.product?.id ?? i} className="border-b last:border-0">
                                                <td className="py-2 text-muted-foreground">{i + 1}</td>
                                                <td className="py-2">{item.product?.title ?? 'Unknown'}</td>
                                                <td className="py-2 text-muted-foreground">{item.product?.brand ?? '-'}</td>
                                                <td className="py-2 text-right font-mono">
                                                    {item.product ? `$${item.product.price.toFixed(2)}` : '-'}
                                                </td>
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

                {/* ─── Recent Reports (Job Batches) ──────────────────────── */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm">Recent Generated Reports (Job Batches)</CardTitle>
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
            </main>
        </div>
    );
}
