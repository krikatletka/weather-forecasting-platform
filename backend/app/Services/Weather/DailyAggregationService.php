<?php

namespace App\Services\Weather;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Aggregate UTC calendar days; timestamps in weather_logs are stored as UTC. */
class DailyAggregationService
{
    public function aggregate(CarbonImmutable $from, CarbonImmutable $to, ?int $cityId = null): int
    {
        if ($from->greaterThan($to)) {
            throw new \InvalidArgumentException('Start date must not be after end date.');
        }

        $query = DB::table('weather_logs')
            ->select('city_id')
            ->selectRaw('DATE(recorded_at) AS day')
            ->selectRaw('MIN(temperature) AS temp_min, MAX(temperature) AS temp_max, AVG(temperature) AS temp_avg')
            ->selectRaw('AVG(surface_pressure) AS avg_pressure, SUM(precipitation) AS total_precipitation')
            ->selectRaw('SUM(SIN(RADIANS(wind_direction))) AS wind_sin, SUM(COS(RADIANS(wind_direction))) AS wind_cos')
            ->where('recorded_at', '>=', $from->startOfDay()->format('Y-m-d H:i:s'))
            ->where('recorded_at', '<', $to->addDay()->startOfDay()->format('Y-m-d H:i:s'))
            ->groupBy('city_id', DB::raw('DATE(recorded_at)'))
            ->orderBy('city_id')
            ->orderBy('day');

        if ($cityId !== null) {
            $query->where('city_id', $cityId);
        }

        $now = now();
        $total = 0;
        $batch = [];
        foreach ($query->cursor() as $row) {
            // Circular average: 359° + 1° is 0°, not 180°.
            $direction = (int) round(rad2deg(atan2((float) $row->wind_sin, (float) $row->wind_cos)));
            $direction = ($direction + 360) % 360;
            $batch[] = [
                'city_id' => $row->city_id,
                'date' => $row->day,
                'temp_min' => round((float) $row->temp_min, 2),
                'temp_max' => round((float) $row->temp_max, 2),
                'temp_avg' => round((float) $row->temp_avg, 2),
                'avg_pressure' => round((float) $row->avg_pressure, 2),
                'dominant_wind_dir' => $direction,
                'total_precipitation' => round((float) $row->total_precipitation, 2),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (count($batch) >= 250) {
                $this->save($batch);
                $total += count($batch);
                $batch = [];
            }
        }
        if ($batch !== []) {
            $this->save($batch);
            $total += count($batch);
        }
        return $total;
    }

    private function save(array $rows): void
    {
        DB::table('weather_daily_aggregates')->upsert(
            $rows,
            ['city_id', 'date'],
            ['temp_min', 'temp_max', 'temp_avg', 'avg_pressure', 'dominant_wind_dir', 'total_precipitation', 'updated_at']
        );
    }
}
