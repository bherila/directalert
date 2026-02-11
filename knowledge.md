# Project Overview

This project, `directalert`, is designed to manage direct alert information.

## Database

- `direct_alert` table: Stores the latest version of direct alert records. Includes fields for account details, contact info, and opt-in timestamps. Has a unique index on `account_number` and `account_name`.
- `direct_alert_history` table: Stores historical versions of `direct_alert` records, linked by `account_number`. Includes an index on `account_number`.

## API Endpoints

- `GET /api/dump/csv`: Exports data from the `direct_alert` table as a CSV file. Accepts `start` and `end` ISO timestamp query parameters to filter by the `created_at` date.

## Admin Pages

- `/admin/export`: A web page with a date range picker to download the CSV export via the API endpoint. Defaults to the prior month's first day to the current date/time. Protected by authentication.

## Authentication

- Standard Laravel authentication at `/auth/login` and `/auth/register`
- New users have no roles by default
- After login/register, users are redirected to `/admin/export`
- All admin routes are protected by the `auth` middleware

## Development Setup

- Standard Laravel development server (`php artisan serve`) is likely needed.