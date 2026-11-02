# Project Scope (Draft)

Collect weather observations for Ukrainian cities, store historical readings in PostgreSQL, calculate daily aggregates, forecast 12–24 hours ahead using historical similar conditions and moving averages, validate forecasts against actual readings, and notify users when configured weather thresholds are met.

## Core data tables
`cities`, `weather_logs`, `weather_daily_aggregates`, `weather_predictions`, `alert_rules`, `notification_logs`, `users`.

## Architecture decisions pending
- Frontend: Blade or React/TypeScript.
- API contracts and exact schema fields.
- Source of historical observations and evaluation methodology.
