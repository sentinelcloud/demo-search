<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\DeviceStat;
use Carbon\Carbon;

class BuildDeviceBreakdownReport implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $periodStart, public string $periodEnd)
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $start = Carbon::parse($this->periodStart);
        $end = Carbon::parse($this->periodEnd);

        $browsers = DeviceStat::whereBetween('period', [$start, $end])
            ->select('browser')
            ->selectRaw('SUM(visitors) as total_visitors')
            ->groupBy('browser')
            ->orderByDesc('total_visitors')
            ->limit(10)
            ->get()
            ->toArray();

        $operatingSystems = DeviceStat::whereBetween('period', [$start, $end])
            ->select('os')
            ->selectRaw('SUM(visitors) as total_visitors')
            ->groupBy('os')
            ->orderByDesc('total_visitors')
            ->limit(10)
            ->get()
            ->toArray();

        $deviceTypes = DeviceStat::whereBetween('period', [$start, $end])
            ->select('device_type')
            ->selectRaw('SUM(visitors) as total_visitors')
            ->groupBy('device_type')
            ->orderByDesc('total_visitors')
            ->get()
            ->toArray();

        $data = [
            'browsers' => $browsers,
            'operating_systems' => $operatingSystems,
            'device_types' => $deviceTypes,
        ];

        cache()->put("report_fragment:{$this->batch()->id}:device_breakdown", $data, 600);
    }
}
