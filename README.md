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


## Collaboration — GitHub Workflow

Each team member has their own branch, created in advance by the Team Lead. **Do not work directly on `main`.**

### 1. Clone the repository

Open a terminal and run:

```bash
git clone https://github.com/krikatletka/weather-forecasting-platform.git
cd weather-forecasting-platform
```

This downloads the project to your computer.

### 2. Switch to your branch

Each team member must work in their assigned branch.

For example, if your branch is `diana`:

```bash
git switch diana
```

If Git cannot find your branch locally, run:

```bash
git fetch origin
git switch --track origin/diana
```

Replace `diana` with your own branch name.

**Important:** Always check that you are working in your own branch before making changes:

```bash
git branch --show-current
```

### 3. Make your changes

Work on your assigned module and save your files.

To see which files have changed:

```bash
git status
```

### 4. Commit your changes

After completing part of your work, run:

```bash
git add .
git commit -m "Implement assigned module"
```

Use a meaningful commit message describing what you changed.

### 5. Push your changes to GitHub

Run:

```bash
git push -u origin HEAD
```

This uploads your commits to your current branch on GitHub.

For subsequent pushes, you can simply use:

```bash
git push
```

### 6. Create a Pull Request

When your work is ready for review:

1. Open the [GitHub repository](https://github.com/krikatletka/weather-forecasting-platform).
2. Go to **Pull requests → New pull request**.
3. Select `main` as the base branch.
4. Select your own branch as the compare branch.
5. Click **Create pull request**.
6. Ask the Team Lead to review your changes.

**Do not merge your Pull Request yourself.** The Team Lead will review and merge it.

### Important rules

- Always work in your assigned branch, never directly in `main`.
- Commit and push your changes regularly.
- Do not modify another team member's work without discussing it first.
- Do not force-push or delete shared branches.
- If you encounter Git conflicts or errors, ask the Team Lead before proceeding.
