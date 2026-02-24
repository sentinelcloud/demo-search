<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\DeviceStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateDeviceStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $periodIso)
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        $period = Carbon::parse($this->periodIso)->startOfHour();
        $periodEnd = $period->copy()->endOfHour();

        $devices = DB::table('analytics_sessions')
            ->whereBetween('started_at', [$period, $periodEnd])
            ->select('browser', 'os', 'device_type')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->selectRaw('COUNT(*) as sessions')
            ->groupBy('browser', 'os', 'device_type')
            ->get();

        foreach ($devices as $device) {
            DeviceStat::updateOrCreate(
                [
                    'period' => $period,
                    'browser' => $device->browser ?? 'Unknown',
                    'os' => $device->os ?? 'Unknown',
                    'device_type' => $device->device_type ?? 'desktop',
                ],
                [
                    'visitors' => $device->visitors,
                    'sessions' => $device->sessions,
                ]
            );
        }
    }
}
