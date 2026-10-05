# Testing

## Prerequisites

- PHP 8.4+
- Composer
- Node.js + pnpm 10+

## Install dependencies

```bash
composer install
pnpm install
```

## PHP code style (Pint)

```bash
vendor/bin/pint --test
```

Run `vendor/bin/pint` to auto-fix.

## TypeScript type-check

```bash
pnpm run type-check
```

## Frontend build

```bash
pnpm run build
```

## JavaScript / TypeScript tests (Jest)

```bash
pnpm run test
```

## PHP tests (PHPUnit)

```bash
vendor/bin/phpunit --configuration phpunit.xml
```

Or via Artisan:

```bash
php artisan test
```

## Run everything (CI order)

```bash
vendor/bin/pint --test
pnpm run type-check
pnpm run build
pnpm run test
vendor/bin/phpunit --configuration phpunit.xml
```
