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
