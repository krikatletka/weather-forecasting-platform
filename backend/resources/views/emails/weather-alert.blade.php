<x-mail::message>
# Погодне сповіщення

Місто: **{{ $rule->city->name }}**

{{ $label }}: **{{ $value }} {{ $unit }}**
(ваше правило: {{ $rule->operator }} {{ $rule->threshold_value }} {{ $unit }})
</x-mail::message>