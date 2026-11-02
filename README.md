# Weather Forecasting Platform

University team project (5 students): weather monitoring, historical forecasting and weather-triggered alerts for Ukrainian cities.

## Planned stack
- Backend: PHP / Laravel
- Database: PostgreSQL
- Frontend: React / TypeScript (subject to course approval)
- Local development: Docker Compose (to be added)
- Weather source: Open-Meteo

> **Status:** Planning stage. No application code has been initialized yet.

## Modules and owners
| Role | Module | Responsibilities |
|---|---|---|
| Team Lead / Student 1 | Ingestion & Prediction | Scheduler, jobs, daily aggregation, historical similarity, moving average |
| Student 2 | Database & API Client | Schema, migrations, indexes, seed data, Open-Meteo client |
| Student 3 | Validation & Analytics | Compare predictions with observations, error metrics and reporting |
| Student 4 | Alerts & Notifications | User rules, alert evaluation, emails, duplicate prevention |
| Student 5 | Auth & Frontend | Login, dashboard, forms, frontend integration |

## Planned folders
- `backend/` — Laravel application
- `frontend/` — React app (if approved)
- `docs/` — architecture, requirements and team notes

## Collaboration
1. Create a feature branch from `main` (for example `feature/weather-api`).
2. Make focused commits and push your branch.
3. Open a Pull Request to `main` and request review from the Team Lead.
4. Merge only after review. Avoid committing directly to `main`.

## Next steps
- Confirm Blade vs React with the instructor.
- Agree on the database schema and the DTO returned by the weather API client.
- Initialize Laravel and frontend apps.
- Add Docker Compose, automated tests and setup instructions.
