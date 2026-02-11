# Admin Audit Logging - Usage Guide

## Overview
This implementation adds comprehensive audit logging for admin operations (login, import, export) with email notifications and historical tracking.

## Database Migration

Before using these features, run the migration:

```bash
php artisan migrate
```

This creates the `admin_audit_log` table with the following structure:
- `id` - Auto-incrementing primary key
- `auth_user_id` - Foreign key to users table (nullable)
- `action` - Type of action: 'login', 'import', or 'export'
- `was_successful` - Boolean indicating success/failure
- `records_affected` - Count of records imported/exported
- `records_skipped` - Count of duplicate records (import only)
- `records_failed` - Count of failed records
- `error_message` - Error details if operation failed
- `timestamps` - Created/updated timestamps

## Email Configuration

Configure your mail settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

Notifications are sent to: **ben@herila.net**

## Features

### 1. Login Audit Logging
- Automatically logs all login attempts
- Records both successful and failed attempts
- Failed attempts include error message

### 2. Export Operations
- Logs every export with record count
- Sends email notification on completion
- Shows last 20 exports on `/admin/export` page
- New "Since Last Export" button for quick date selection

### 3. Import Operations
- Logs every import with detailed statistics
- Tracks imported, skipped (duplicates), and failed records
- Sends email notification on completion
- Shows last 20 imports on `/admin/import` page

### 4. History Display
- **Export History**: View your last 20 exports with:
  - Date/time
  - Success/failure status (color-coded badges)
  - Number of records exported
  - Error message (if failed)

- **Import History**: View your last 20 imports with:
  - Date/time
  - Success/failure status (color-coded badges)
  - Records imported
  - Records skipped (duplicates)
  - Records failed
  - Error message (if failed)

## Using the "Since Last Export" Button

1. Navigate to `/admin/export`
2. If you have previous exports, you'll see a blue "Since Last Export" button
3. Click it to automatically set:
   - Start date: Your last successful export date
   - End date: Current date/time

## Email Notifications

Email notifications are sent for:
- ✅ Export operations (successful or failed)
- ✅ Import operations (successful or failed)
- ❌ Login operations (not notified via email)

### Email Format
- **Subject**: "MW DirectAlert Notification"
- **To**: ben@herila.net
- **Content**: HTML formatted with:
  - Operation type (Export/Import)
  - User who performed the operation
  - Success/failure status (color-coded header)
  - Record statistics
  - Error details (if failed)

### Error Handling
If email sending fails:
- The operation (import/export) still succeeds
- Error is logged to application logs
- User is not notified of email failure

## Programmatic Usage

### Logging Operations Manually

```php
use App\Services\AdminAuditLogService;

$auditService = new AdminAuditLogService();

// Log successful export
$auditService->log(
    action: 'export',
    wasSuccessful: true,
    recordsAffected: 100
);

// Log failed import
$auditService->log(
    action: 'import',
    wasSuccessful: false,
    recordsAffected: 50,
    recordsSkipped: 10,
    recordsFailed: 5,
    errorMessage: 'Database connection failed'
);
```

### Querying Audit Logs

```php
use App\Services\AdminAuditLogService;

$auditService = new AdminAuditLogService();

// Get last export date
$lastExportDate = $auditService->getLastExportDate();

// Get export history (last 10)
$exports = $auditService->getExportHistory(10);

// Get import history (last 10)
$imports = $auditService->getImportHistory(10);
```

## Security Notes

- Audit logs are automatically associated with the authenticated user
- Login attempts for non-authenticated users have `auth_user_id` set to NULL
- History displays are scoped to the current user
- Users cannot delete their own audit logs (read-only display)

## Testing

Run tests to verify audit logging:

```bash
# PHP tests
php artisan test --filter AdminAuditLogServiceTest

# All tests
php artisan test
pnpm test
pnpm type-check
pnpm build
```

## Troubleshooting

### Emails Not Sending
1. Check `.env` mail configuration
2. Verify mail credentials
3. Check `storage/logs/laravel.log` for errors
4. Operations should still succeed even if emails fail

### History Not Showing
1. Verify user is authenticated
2. Check if user has performed any operations
3. Only shows last 20 operations per user

### Migration Issues
1. Ensure database connection is configured
2. Run `php artisan migrate:status` to check migration status
3. Run `php artisan migrate` to apply pending migrations
