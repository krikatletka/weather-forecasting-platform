<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'recorded_at',
        'temperature',
        'surface_pressure',
        'humidity',
        'wind_speed',
        'wind_direction',
        'precipitation',
        'weather_code',
        'data_source',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'temperature' => 'decimal:2',
            'surface_pressure' => 'decimal:2',
            'humidity' => 'integer',
            'wind_speed' => 'decimal:2',
            'wind_direction' => 'integer',
            'precipitation' => 'decimal:2',
            'weather_code' => 'integer',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
