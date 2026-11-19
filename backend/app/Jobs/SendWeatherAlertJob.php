<?php

namespace App\Jobs;

use App\Mail\WeatherAlertMail;
use App\Models\NotificationLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendWeatherAlertJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [30, 120];

    public function __construct(public int $notificationLogId) {}

    public function handle(): void
    {
        $log = NotificationLog::with('alertRule.user', 'alertRule.city')->find($this->notificationLogId);

        if (!$log || $log->status === 'sent') {
            return; // повторний запуск не шле лист удруге
        }

        Mail::to($log->alertRule->user->email)
            ->send(new WeatherAlertMail($log->alertRule, (float) $log->triggered_value));

        $log->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function failed(Throwable $e): void
    {
        NotificationLog::whereKey($this->notificationLogId)->update(['status' => 'failed']);
        Log::error('Не вдалося надіслати сповіщення', [
            'notification_log_id' => $this->notificationLogId,
            'error' => $e->getMessage(),
        ]);
    }
}