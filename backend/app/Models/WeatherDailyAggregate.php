<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherDailyAggregate extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'date',
        'temp_min',
        'temp_max',
        'temp_avg',
        'avg_pressure',
        'dominant_wind_dir',
        'total_precipitation',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'temp_min' => 'decimal:2',
            'temp_max' => 'decimal:2',
            'temp_avg' => 'decimal:2',
            'avg_pressure' => 'decimal:2',
            'dominant_wind_dir' => 'integer',
            'total_precipitation' => 'decimal:2',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
