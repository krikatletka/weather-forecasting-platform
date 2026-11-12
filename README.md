# Weather Forecasting Platform

University team project (5 students) for weather monitoring, historical forecasting, and weather-triggered alerts for Ukrainian cities.

## Project Status

Backend infrastructure and database architecture have been implemented.

Completed:
- Laravel 12 backend initialization
- PostgreSQL 16 database setup
- Docker Compose configuration
- Database migrations and Eloquent models
- Model relationships and database indexes
- Seeders for Ukrainian cities and synthetic weather history
- Basic database integrity checks

In development:
- Open-Meteo API integration
- Weather ingestion and scheduling
- Daily weather aggregation
- Historical forecasting algorithms
- Prediction validation and analytics
- Alerts and notifications
- Authentication and frontend

## Technology Stack

- Backend: PHP 8.2 / Laravel 12
- Database: PostgreSQL 16
- Frontend: React / TypeScript (subject to course approval)
- Local development: Docker Compose
- Dependency management: Composer
- Weather data source: Open-Meteo

## Project Structure

```text
weather-forecasting-platform/
├── backend/
│   ├── app/
│   │   └── Models/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── public/
│   ├── routes/
│   ├── storage/
│   ├── tests/
│   ├── .env.example
│   ├── Dockerfile
│   ├── artisan
│   └── composer.json
├── frontend/
├── docs/
├── .env.example
├── .gitignore
├── docker-compose.yml
└── README.md
```

## Modules and Owners

| Role | Module | Responsibilities |
|---|---|---|
| Team Lead / Student 1 | Ingestion & Prediction | Scheduler, jobs, daily aggregation, historical similarity, moving average |
| Student 2 | Database & API Client | PostgreSQL schema, migrations, indexes, seed data, Open-Meteo client |
| Student 3 | Validation & Analytics | Prediction validation, error metrics, analytics and reporting |
| Student 4 | Alerts & Notifications | User-defined rules, alert evaluation, notifications and duplicate prevention |
| Student 5 | Auth & Frontend | Authentication, dashboard, forms and frontend integration |

## Backend Setup

### Requirements

Install the following tools:

- Git
- Docker Desktop with Docker Compose

PHP and Composer are included in the backend Docker image, so local installations are not required for the Docker-based workflow.

### Installation

**1. Clone the repository**

```powershell
git clone https://github.com/krikatletka/weather-forecasting-platform.git
```

Open the project directory:

```powershell
cd weather-forecasting-platform
```

**2. Create the root environment file**

For PowerShell:

```powershell
Copy-Item .env.example .env
```

Open the root `.env` file and replace `change_me` with your own PostgreSQL password.

Example:

```dotenv
POSTGRES_PASSWORD=your_local_password
```

Do not commit this file to Git.

**3. Create the Laravel environment file**

```powershell
Copy-Item backend/.env.example backend/.env
```

The backend container receives the PostgreSQL password from the root `.env` file through Docker Compose.

The Laravel database configuration is:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=weather_db
DB_USERNAME=weather_user
DB_PASSWORD=change_me
```

The `DB_PASSWORD` in this example is a placeholder. Docker Compose overrides this variable inside the backend container with the password from the root `.env`.

**4. Build the backend Docker image**

```powershell
docker compose build backend
```

**5. Start PostgreSQL**

```powershell
docker compose up -d postgres
```

**6. Install Laravel dependencies**

```powershell
docker compose run --rm backend composer install
```

**7. Generate the Laravel application key**

```powershell
docker compose run --rm backend php artisan key:generate
```

**8. Start the Laravel backend**

```powershell
docker compose up -d backend
```

**9. Run database migrations**

```powershell
docker compose exec backend php artisan migrate
```

**10. Populate the database with test data**

```powershell
docker compose exec backend php artisan db:seed
```

This command runs `CitySeeder` and `WeatherLogSeeder`.

**11. Check container status**

```powershell
docker compose ps
```

Expected services:
- `weather-postgres` — Up (healthy)
- `weather-backend` — Up

### Application Access

Laravel backend:

http://localhost:8000

PostgreSQL connection from Docker containers:

- Host: `postgres`
- Port: `5432`
- Database: `weather_db`
- Username: `weather_user`

PostgreSQL connection from Windows:

- Host: `127.0.0.1`
- Port: `5433`
- Database: `weather_db`
- Username: `weather_user`

The password is configured locally in the root `.env` file.

## Database Architecture

The application uses PostgreSQL 16 and Laravel Eloquent ORM.

### Main Tables

| Table | Description |
|---|---|
| `cities` | Ukrainian cities, coordinates, time zones and active status |
| `weather_logs` | Hourly weather observations and their sources |
| `weather_daily_aggregates` | Daily minimum, maximum and average weather statistics |
| `weather_predictions` | Forecast results from different prediction algorithms |
| `alert_rules` | User-defined conditions for weather alerts |
| `notification_logs` | History and delivery status of weather notifications |

Laravel also provides standard tables for users, cache and background jobs.

### Relationships

- One city has many weather logs.
- One city has many daily aggregates.
- One city has many weather predictions.
- One city has many alert rules.
- One user has many alert rules.
- One user has many notification logs.
- One alert rule has many notification logs.

Foreign keys are used to maintain referential integrity.

### Indexes and Constraints

The database includes indexes for efficient querying.

Important constraints:

- `weather_logs`: unique combination of `city_id` and `recorded_at`
- `weather_daily_aggregates`: unique combination of `city_id` and `date`

Additional indexes support queries for historical weather conditions, forecasts and notifications.

## Seeders and Test Data

Two database seeders are available:

### CitySeeder

Creates 10 Ukrainian cities:

1. Kyiv
2. Kharkiv
3. Lviv
4. Odesa
5. Dnipro
6. Zaporizhzhia
7. Poltava
8. Chernihiv
9. Vinnytsia
10. Ivano-Frankivsk

City names are stored in Ukrainian.

Each city includes:
- Name
- Latitude and longitude
- Time zone (`Europe/Kyiv`)
- Active status

### WeatherLogSeeder

Generates synthetic hourly weather observations.

Dataset parameters:

- Cities: 10
- Period: 180 days
- Frequency: 1 observation per hour
- Observations per city: 4,320
- Total observations: 43,200

Fixed UTC period:

- Start: `2026-05-15 20:00:00`
- End: `2026-11-11 19:00:00`

Generated weather parameters:

- Temperature (°C)
- Atmospheric pressure (hPa)
- Relative humidity (%)
- Wind speed (m/s)
- Wind direction (degrees)
- Precipitation (mm)
- Weather code

The generator includes seasonal and daily temperature variation and simplified weather patterns.

All generated observations have:

```text
data_source = synthetic
```

Real observations obtained through the Open-Meteo integration will be identified with:

```text
data_source = open_meteo
```

**Important:** Synthetic observations are intended only for development and testing. They are not real historical weather measurements and must not be represented as such.

The seeder uses a fixed time range and avoids duplicate records using the unique city/time constraint.

### Run Seeders

Run all registered seeders:

```powershell
docker compose exec backend php artisan db:seed
```

Run only the city seeder:

```powershell
docker compose exec backend php artisan db:seed --class=CitySeeder
```

Run only the weather history seeder:

```powershell
docker compose exec backend php artisan db:seed --class=WeatherLogSeeder
```

## Database Verification

Check migration status:

```powershell
docker compose exec backend php artisan migrate:status
```

Check the database connection:

```powershell
docker compose exec backend php artisan db:show
```

Count cities:

```powershell
docker compose exec backend php artisan tinker --execute="echo App\Models\City::count();"
```

Expected result:

```text
10
```

Count weather observations:

```powershell
docker compose exec backend php artisan tinker --execute="echo App\Models\WeatherLog::count();"
```

Expected result on a newly seeded database:

```text
43200
```

Verify synthetic observations:

```powershell
docker compose exec backend php artisan tinker --execute="echo App\Models\WeatherLog::where('data_source', 'synthetic')->count();"
```

Expected result on a newly seeded database:

```text
43200
```

The initial development dataset was also checked for:
- Duplicate city/time observations
- Missing required weather values
- Invalid humidity, wind direction, wind speed and precipitation values

All performed checks returned zero invalid records.

## Useful Docker Commands

Start all services:

```powershell
docker compose up -d
```

Check container status:

```powershell
docker compose ps
```

View backend logs:

```powershell
docker compose logs backend
```

View PostgreSQL logs:

```powershell
docker compose logs postgres
```

Stop containers:

```powershell
docker compose down
```

Rebuild the backend image:

```powershell
docker compose up -d --build backend
```

Do not use `docker compose down -v` unless you intentionally want to delete the PostgreSQL data volume.

## Collaboration and Git Workflow

The project is developed by a team of five students.

Each student works in their own Git branch.

Workflow:

1. Work in your personal development branch.
2. Create focused commits.
3. Push changes to your branch.
4. Open a Pull Request to `main`.
5. Request code review from the Team Lead.
6. Merge changes only after approval.

Do not commit directly to `main`.

## Security

Never commit:
- Local `.env` files
- Database passwords
- Application secrets and API keys
- The `backend/vendor/` directory
- Temporary development files

Environment examples are provided through `.env.example` files.

Docker Compose exposes the development ports on `127.0.0.1` only.

## Next Steps

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
