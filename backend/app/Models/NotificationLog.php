<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_rule_id',
        'user_id',
        'triggered_value',
        'sent_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'triggered_value' => 'decimal:2',
            'sent_at' => 'datetime',
        ];
    }

    public function alertRule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
