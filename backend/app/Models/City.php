<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'timezone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    public function weatherLogs(): HasMany
    {
        return $this->hasMany(WeatherLog::class);
    }

    public function dailyAggregates(): HasMany
    {
        return $this->hasMany(WeatherDailyAggregate::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(WeatherPrediction::class);
    }

    public function alertRules(): HasMany
    {
        return $this->hasMany(AlertRule::class);
    }
}
