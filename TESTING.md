# Testing

## Prerequisites

- PHP 8.5+
- Composer
- Node.js + pnpm 10+

## Install dependencies

```bash
composer install
pnpm install
```

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
pnpm run type-check
pnpm run build
pnpm run test
vendor/bin/phpunit --configuration phpunit.xml
```
