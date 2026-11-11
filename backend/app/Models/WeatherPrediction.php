<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'generated_at',
        'predicted_for_at',
        'predicted_temp',
        'predicted_precipitation',
        'algorithm_type',
        'confidence_score',
        'actual_temp',
        'error_margin',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'predicted_for_at' => 'datetime',
            'predicted_temp' => 'decimal:2',
            'predicted_precipitation' => 'decimal:2',
            'confidence_score' => 'decimal:4',
            'actual_temp' => 'decimal:2',
            'error_margin' => 'decimal:2',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
