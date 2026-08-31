# Bulut Filo Yönetimi

A fleet management application that imports vehicle and address data from
user-uploaded Excel files. Addresses are normalised via the OpenAI API,
converted to coordinates via Google Geocoding, and rendered as routes on a
map via the Google Routes API. Internship assessment project — single
developer, one sprint (10 working days).

## Tech stack

- **Backend:** PHP 8.4, Laravel 13, MySQL 8, Redis (queue + cache), Laravel Horizon
- **Frontend:** React 19, TypeScript, Vite, TanStack Query, TanStack Table, React Router, `@vis.gl/react-google-maps`, Tailwind CSS, react-hook-form + zod
- **Infrastructure:** Docker Compose (php-fpm, nginx, mysql, redis, horizon)
- **External services:** OpenAI Chat Completions API, Google Geocoding API, Google Routes API

## Installation

Requirements: Docker Desktop with Docker Compose v2 and Node.js (CI runs
Node 26; no `engines` constraint is enforced locally). No local PHP or
Composer installation is needed for the backend.

### Backend

1. Make sure `.env` exists at the project root with `APP_URL=http://localhost:8080`
   and the database/cache values matching the services below (`DB_HOST=mysql`,
   `DB_DATABASE=bulut_filo_yonetimi`, `REDIS_HOST=redis`), plus real
   `OPENAI_API_KEY` and `GOOGLE_MAPS_SERVER_KEY` values if you want the
   address/geocoding/route pipeline to actually resolve anything.

2. Build the images and start all services. The `--build` flag is required on
   the first run, otherwise the `horizon` service (which reuses the `app`
   image) fails with a `pull access denied` error:

   ```
   docker compose up -d --build
   ```

3. Generate the application key (writes `APP_KEY` into `.env`):

   ```
   docker compose exec app php artisan key:generate
   ```

4. Run migrations and seed the institution hierarchy:

   ```
   docker compose exec app php artisan migrate --seed
   ```

5. Open http://localhost:8080 — the Laravel welcome page should load, and
   http://localhost:8080/horizon should show the queue dashboard.

### Frontend

The React app is a separate Vite project under `frontend/`, not served by
the Docker Compose stack above.

1. Install dependencies:

   ```
   cd frontend
   npm install
   ```

2. Copy the environment file and set a Google Maps **browser** key (separate
   from the backend's server key) for the route map to render:

   ```
   cp .env.example .env
   ```

3. Start the dev server:

   ```
   npm run dev
   ```

4. Open http://localhost:5173.

### Sample data

The institution hierarchy is seeded automatically. There are no vehicles
until an Excel file is imported — either through the Imports screen
("İçe Aktarımlar" in the Turkish UI), or by uploading one of the ready-made
sample files under `docs/` (`ornek-import.xlsx`,
`ornek-import-plaka-devri.xlsx`), which already match the seeded institution
codes.

### Verifying the setup

```
docker compose ps                                          # all services healthy/running
docker compose exec app php artisan --version
docker compose exec app php -m | grep -Ei 'pdo_mysql|redis|intl|gd|zip|bcmath|opcache'
docker compose exec app php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"
docker compose exec app php artisan tinker --execute="Cache::store('redis')->put('healthcheck','ok',10); echo Cache::store('redis')->get('healthcheck');"
docker compose logs horizon --tail=50                       # should run without errors
curl -s http://localhost:8080/horizon/api/stats | head -c 200 # Horizon dashboard API responds
```

## Architecture

### Domain model

A vehicle's identity is its **VIN** (chassis number), not its plate — a
plate is a time-bound assignment that can change over the vehicle's
lifetime:

```
vehicles        id, vin (unique), brand, model, institution_id, status
vehicle_plates  id, vehicle_id, plate, assigned_at, released_at
```

`status` is a backed enum (`active` / `passive` / `left_fleet`); vehicles are
never hard-deleted. An active plate assignment's `released_at` is a sentinel
value (`9999-12-31 00:00:00`), and a `UNIQUE(plate, released_at)` constraint
guarantees at most one active assignment per plate at a time.

Institutions form a tree (arbitrary depth, resolved recursively — never
hardcoded to a level count) and are read-only: seeded once, no CRUD.

### Excel import decision tree

Uploading a file dispatches `ProcessVehicleImportJob`, which seeds one
`ImportRow` per data row and applies a four-branch decision tree per row via
`VehicleImportService`:

1. **VIN exists** — update the vehicle; if the plate changed, close the old
   assignment and open a new one; reactivate the vehicle if it was inactive.
2. **VIN is new, the plate is currently held by a passive/left-fleet
   vehicle** — a plate transfer: close the old assignment, open a new one
   under the new VIN.
3. **VIN is new, the plate is currently active on another vehicle** — a
   genuine conflict. The row is never silently overwritten; it's marked
   `needs_review` with a reference to the conflicting vehicle, for a human to
   resolve.
4. **VIN is new, the plate is free** — assign it directly.

### Address resolution, geocoding and routing

Each row's start/end address (if present) is normalised via OpenAI
(`AddressFormatterInterface`), geocoded via Google Geocoding
(`GeocoderInterface`), and a route between the two is computed once via the
Google Routes API (`RouteProviderInterface`) — cached by address/route-pair
hash and never recomputed on page load. All three external services sit
behind interfaces bound in `AppServiceProvider`, are rate-limited via
`Redis::throttle`, and retried via `Http::retry`.

### Read API and frontend

`GET /api/v1/vehicles` and `GET /api/v1/institutions` expose the domain for
the frontend's server-side sortable/filterable/paginated tables
(`spatie/laravel-query-builder`). Filter/sort/page state lives entirely in
the URL query string, using the same parameter names the backend query
builder expects — no separate client-side filter state to keep in sync.
Filtering vehicles by institution cascades down the tree: selecting a parent
institution includes every descendant institution's vehicles.

### Queue processing

All long-running work — Excel parsing and the three external API calls —
runs in queued jobs, processed by Laravel Horizon (dashboard at `/horizon`).
A job that hits a rate limit releases itself back onto the queue with a
delay instead of failing.

### Screens

| Route | Purpose |
|---|---|
| `/` | Backend health check |
| `/vehicles` | Server-side filterable/sortable/paginated vehicle list |
| `/vehicles/:id` | Vehicle detail, including full plate history |
| `/imports` | Upload an Excel file; browse past import batches |
| `/imports/:id` | Per-row status (including `needs_review` conflicts with the conflicting VIN), multi-select rows to draw their routes together on a map |

## Development

```
./vendor/bin/pint --test          # PSR-12 style check
./vendor/bin/phpstan analyse      # static analysis (Larastan, level 6)
./vendor/bin/pest                 # backend tests
```

```
cd frontend
npm run lint                      # oxlint
npm run typecheck                 # tsc --noEmit
npm run build                     # production build
```

All five checks run in CI on every push (`.github/workflows/ci.yml`); a
build isn't considered done until all of them are green.
