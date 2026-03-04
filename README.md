
# EHRIS

Laravel 12 + Vue 3 (Inertia) application.

## 1. Prerequisites

Install these first:

- PHP `8.2+`
- Composer `2+`
- Node.js `20+` and npm
- Git
- Database:
  - MySQL/MariaDB

## 2. Clone the repository

```bash
git clone <your-repo-url>
cd ehris-leave
```

## 3. Install dependencies and initialize app

Run:

```bash
composer setup
```

This command already does all of the following:

- `composer install`
- copies `.env.example` to `.env` (if missing)
- generates `APP_KEY`
- runs migrations
- `npm install`
- builds frontend assets

## 4. Configure database

1. Import `ehris_db_clean.sql`.
2. Update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ehris
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

3. Run:

```bash
php artisan migrate
```

## 5. Run the project

For local development (Laravel server + queue worker + Vite):

```bash
composer run dev
```

Open:

`http://127.0.0.1:8000`

## 6. Useful commands

- Run tests: `composer test`
- Lint PHP: `composer lint`
- Frontend dev only: `npm run dev`
- Build frontend for production: `npm run build`

## 7. Common issues

- If frontend assets look missing, run: `npm run build` or `npm run dev`
- If key is missing, run: `php artisan key:generate`
- If DB tables are missing, run: `php artisan migrate`

=======
>>>>>>> 436fea27d3c41fd469695be2a0bbac57a8d251c9
# EHRIS Leave - Setup

## Clone And Install
1. Clone the repository.
2. Change directory into the project:
```bash
cd ehris-leave
```
3. Copy the environment file:
```bash
cp .env.example .env
```
4. Install PHP dependencies:
```bash
composer install
```
5. Generate the app key:
```bash
php artisan key:generate
```
6. Install Node dependencies:
```bash
npm install
```

## Next
Set the correct database credentials in `.env`, then run the app using your preferred local environment.
<<<<<<< HEAD
=======
>>>>>>> 28cd2466f1c978f8628c442848035637ca473213
>>>>>>> 436fea27d3c41fd469695be2a0bbac57a8d251c9
