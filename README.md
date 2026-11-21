# Pixelforge

Pixelforge is an image generation application built with Laravel, Vue, and Inertia.

## Requirements

- PHP 8.3 or later with the extensions required by Laravel
- Composer
- Node.js and npm
- PostgreSQL
- Mailpit or MailHog for local email capture

## Local setup

Create the PostgreSQL database before running the application setup:

```bash
createdb pixelforge
```

Create the local environment file:

```bash
cp .env.example .env
```

Edit `.env` and set the PostgreSQL username and password for your machine. Set `INITIAL_USER_PASSWORD` before migrating if you want a local password other than the development default. The migrations create the system administrator `pfapplication@in-progress.app`; `php artisan db:seed` creates the demo user `Jane Doe` (`jane@example.com`).

Install PHP and JavaScript dependencies, generate the application key, run migrations, and build the frontend:

```bash
composer setup
```

Seed the basic test user:

```bash
php artisan db:seed
```

Start the local Laravel server, queue listener, log viewer, and Vite development server:

```bash
composer dev
```

The application is available at `http://localhost:8000`.

## Local email

The default local mail transport is SMTP to Mailpit or MailHog at `127.0.0.1:1025`. Start your local mail catcher before testing password reset or other email flows, then view captured messages in its web interface, commonly at `http://localhost:8025`.

## Cloudflare Email Service

Production forces the Cloudflare mail transport. Configure `CLOUDFLARE_ACCOUNT_ID` and `CLOUDFLARE_API_TOKEN` in the production environment. Set `MAIL_FROM_ADDRESS` to `noreply@pfapplication.in-progress.app`; the sender domain must be enabled for Cloudflare Email Service. Outside production, `MAIL_TRANSPORT=smtp` routes mail to the local SMTP catcher.

## Tests

Run the test suite with:

```bash
php artisan test
```
