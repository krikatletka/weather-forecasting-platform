<?php

namespace App\Mail;

use App\Models\AlertRule;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WeatherAlertMail extends Mailable
{
    public const LABELS = [
        'temperature' => ['Температура', '°C'],
        'humidity' => ['Вологість', '%'],
        'surface_pressure' => ['Тиск', 'гПа'],
        'wind_speed' => ['Швидкість вітру', 'м/с'],
        'precipitation' => ['Опади', 'мм'],
    ];

    public function __construct(public AlertRule $rule, public float $value) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Погодне сповіщення: ' . $this->rule->city->name);
    }

    public function content(): Content
    {
        [$label, $unit] = self::LABELS[$this->rule->parameter] ?? [$this->rule->parameter, ''];

        return new Content(
            markdown: 'emails.weather-alert',
            with: ['label' => $label, 'unit' => $unit],
        );
    }
}