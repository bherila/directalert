# Direct Alert Project

This project is a Laravel application for managing direct alert information, including a database model, history table, and an admin export feature.

## Local Development

Local development uses a local SQLite database (`database/database.sqlite`), not the production MySQL database. Point `.env` at production only via the documented SSH workflow below, not by putting production DB credentials in a local `.env` - a local environment holding production DB credentials directly was the root cause of a production incident during the PII-encryption migration (wrong `APP_KEY` used against real data).

### Setup

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
```

Make sure `.env` has `DB_CONNECTION=sqlite` and a `DIRECT_ALERT_BLIND_INDEX_PEPPER` value (any string is fine for local dev; see `.env.example`). Set `MAIL_MAILER=log` so local testing doesn't send real emails (2FA codes, admin notifications, invite links all get written to `storage/logs/laravel.log` instead).

### Seeded login

The seeder (`database/seeders/DatabaseSeeder.php`) creates one admin user:

| Email | Password | Role |
|---|---|---|
| `ben@herila.net` | `password` | admin |

### Seeded sample DirectAlert data

The seeder also creates 7 sample `direct_alert` rows covering the different states a real record can be in, plus one `direct_alert_history` snapshot. Use these account numbers + last names to exercise the citizen self-service flow (`/` → `/verify` → `/update-information`) locally:

| Account # | Name | State |
|---|---|---|
| `1000001` | SMITH, JOHN | Freshly imported - no contact info yet (the state every row is in right after an admin CSV import) |
| `1000007` | SMITH, ROBERT | Freshly imported, shares a last name with `1000001` - exercises that verification matches on account_number, not just name |
| `1000002` | DOE, JANE | Fully self-registered - email + all 4 phone fields + every opt-in enabled |
| `1000003` | GARCIA, MARIA | Partially registered - email only |
| `1000004` | ACME PROPERTY MANAGEMENT LLC | Commercial/organization account (account_name isn't always a person) |
| `1000005` | PATEL, RAJESH | Cell-only registration (no email/landline), SMS opt-in |
| `1000006` | KOWALSKI, ANNA | Already exported and purged - `exported_at` is set but contact info has since been cleared; also has a `direct_alert_history` snapshot from before the purge |

If you change the seeded accounts, keep this table and `DatabaseSeeder.php` in sync.

### Reaching the real production database

Production runs MySQL on a cPanel-hosted server; its `.env` lives only on that server, not in this repo. To run a one-off Artisan command against production, SSH in and run it there directly (this guarantees `APP_KEY`/`DIRECT_ALERT_BLIND_INDEX_PEPPER` match what's actually deployed - do not run production-data commands from a local checkout with overridden `DB_*` env vars, which is what caused the incident referenced above):

```bash
ssh <cpanel-account>@<host>
cd ~/directalert-app
php artisan <command>
```

Deploys happen via `.github/workflows/deploy.yml` on push to `main` (see that file for the exact steps) - it does **not** run migrations automatically, so any schema migration must be run manually on the server before/alongside a deploy that depends on it.

## Deployment to cPanel

Deploying a Laravel application to cPanel involves several steps to ensure both PHP dependencies and frontend assets are correctly set up, and the web server points to the correct directory.

Here's a general guide:

1.  **Upload Project Files:**
    *   Compress your project directory (excluding `node_modules` and `vendor` directories, as these will be installed on the server).
    *   Upload the compressed file to your cPanel file manager or via FTP/SFTP.
    *   Extract the files into the desired location (e.g., within your `home` directory, outside the `public_html` folder for security).

2.  **Set up the `.env` file:**
    *   Copy the `.env.example` file in your project root and rename it to `.env`.
    *   Edit the `.env` file to configure your database connection details (`DB_*` variables), `APP_URL`, `APP_KEY`, and any other environment-specific settings.
    *   You can generate a new `APP_KEY` using Artisan, but you'll need SSH access for that. If you don't have SSH, you can generate one locally (`php artisan key:generate --show`) and paste it into the `.env` file.

3.  **Install PHP Dependencies (Composer):**
    *   cPanel often provides a "Composer" tool in the "Software" section. Use this to navigate to your project root directory on the server and run `composer install --no-dev`. This will install all the necessary PHP packages.
    *   If you have SSH access, you can simply SSH into your account, navigate to the project root, and run `composer install --no-dev`.

4.  **Install Frontend Dependencies and Build Assets (NPM/Yarn/Bun & Vite):**
    *   cPanel might have a "Node.js" or "Setup Node.js App" tool. Use this to set up a Node.js environment for your project.
    *   Use the terminal within the Node.js tool (or SSH) to navigate to your project root.
    *   Install JavaScript dependencies: `npm install` (or `yarn install` or `bun install` depending on your project's lock file).
    *   Build the frontend assets: `npm run build` (or `yarn build` or `bun build`). This compiles CSS and JavaScript using Vite and places them in the `public` directory.
    *   Alternatively, you can use the `npm run zip` command to build the project and create a `dist.zip` file. This file will include the necessary `public` assets, `vendor` directory, and `.env` file for deployment.

5.  **Run Database Migrations:**
    *   If you have SSH access, navigate to your project root and run `php artisan migrate`.
    *   If you don't have SSH, some cPanel setups allow running Artisan commands via a web interface or a custom script. Alternatively, you might need to use a tool like phpMyAdmin to manually create the tables based on the migration files (though this is less recommended).

6.  **Configure Web Server Document Root:**
    *   This is a crucial step. You need to tell cPanel's web server (Apache or Nginx) to point the domain or subdomain to the `public` directory inside your project folder, not the project root itself.
    *   In cPanel, this is usually done in the "Domains" or "Subdomains" section. Edit the domain/subdomain and change the "Document Root" to point to `/path/to/your/project/public`.

7.  **Verify `.htaccess`:**
    *   Laravel includes a `public/.htaccess` file which is usually sufficient for routing. Ensure this file is present in your `public` directory on the server.

8.  **Set Directory Permissions:**
    *   Ensure that the `storage` and `bootstrap/cache` directories within your project have write permissions for the web server user. You can usually do this via the cPanel File Manager by selecting the folders and changing permissions (often to 755 or 775, but consult your host's documentation).

After completing these steps, your Laravel application should be accessible via your domain, and the `/admin/export` page and `/api/dump/csv` endpoint should be functional.
