# Bulut Filo Yönetimi

Excel tabanlı araç ve adres içe aktarma, ChatGPT ile adres normalizasyonu,
Google Geocoding ile koordinat çözümleme ve harita üzerinde rota görselleştirme.

## Installation

Requirements: Docker Desktop with Docker Compose v2. No local PHP or Composer
installation is needed.

1. Make sure `.env` exists at the project root with `APP_URL=http://localhost:8080`
   and the database/cache values matching the services below (`DB_HOST=mysql`,
   `DB_DATABASE=bulut_filo_yonetimi`, `REDIS_HOST=redis`).

2. Build the images and start all services. The `--build` flag is required on
   the first run, otherwise the `queue` service (which reuses the `app` image)
   fails with a `pull access denied` error:

   ```
   docker compose up -d --build
   ```

3. Generate the application key (writes `APP_KEY` into `.env`):

   ```
   docker compose exec app php artisan key:generate
   ```

4. Open http://localhost:8080 — the Laravel welcome page should load.

### Verifying the setup

```
docker compose ps                                          # all services healthy/running
docker compose exec app php artisan --version
docker compose exec app php -m | grep -Ei 'pdo_mysql|redis|intl|gd|zip|bcmath|opcache'
docker compose exec app php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"
docker compose exec app php artisan tinker --execute="Cache::store('redis')->put('healthcheck','ok',10); echo Cache::store('redis')->get('healthcheck');"
docker compose logs queue --tail=50                         # should run without errors
```

Architecture notes will be added as development progresses.
