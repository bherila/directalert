# UC Laravel Copilot Instructions

## Architecture Overview

- **Backend**: Laravel 10 (PHP 8.5+) API with Blade shell templates
- **Frontend**: React 19 + TypeScript via Vite
- **UI**: shadcn/ui components with Radix UI primitives and Tailwind CSS v4
- **Database**: MySQL with Eloquent ORM
- **External**: Shopify GraphQL Admin API integration
- **Multi-Tenant**: Shop-based with per-store API credentials and user access control

## Multi-Tenant Architecture

The application supports multiple Shopify stores with granular user access:

### Access Control Model
- **Admin Users**: `is_admin = true` or user ID 1 - full access to all stores and admin pages
- **Shop Access**: Users are granted access to specific shops via `user_shop_accesses` table
  - `read-only`: View offers and manifests, no modifications
  - `read-write`: Full CRUD on offers and manifests

### Middleware
- `admin` - Requires admin status ([app/Http/Middleware/EnsureAdmin.php](app/Http/Middleware/EnsureAdmin.php))
- `shop.access:read` - At least read-only access to shop ([app/Http/Middleware/EnsureShopAccess.php](app/Http/Middleware/EnsureShopAccess.php))
- `shop.access:write` - Read-write access to shop

### Shop-Scoped API Pattern
All offer and Shopify API endpoints are scoped to a shop:
```php
// routes/api.php
Route::prefix('shops/{shop}')
    ->middleware(['auth', 'shop.access:read'])
    ->group(function () {
        Route::get('offers', [OfferController::class, 'index']);
        // ...
    });
```

### Creating Shop-Specific Services
Controllers create services with shop-specific configuration:
```php
// In OfferController
private function makeOfferService(ShopifyShop $shop): OfferService
{
    $client = new ShopifyClient($shop);
    return new OfferService(
        new ShopifyProductService($client),
        new ShopifyOrderService($client)
    );
}
```

## Project Pattern

Blade routes in [routes/web.php](routes/web.php) return minimal views that mount React roots with `data-*` props for passing server data to client. React entrypoints are configured in [vite.config.ts](vite.config.ts) with alias `@` → `resources/js`.

### Mounting Pattern Example

Blade template ([resources/views/shop/offers/detail.blade.php](resources/views/shop/offers/detail.blade.php)):
```blade
@extends('layouts.app')
@section('content')
<div id="offer-detail-root" 
     data-api-base="{{ url('/api') }}"
     data-shop-id="{{ $shopId }}"
     data-offer-id="{{ $offerId }}">
</div>
@endsection
@push('head')
@vite('resources/js/offer-detail.tsx')
@endpush
```

React entrypoint ([resources/js/offer-detail.tsx](resources/js/offer-detail.tsx)):
```tsx
const root = document.getElementById('offer-detail-root');
const shopId = root?.dataset.shopId;
const offerId = root?.dataset.offerId;
const apiBase = root?.dataset.apiBase || '/api';
// API calls use: `${apiBase}/shops/${shopId}/offers/${offerId}`
```

## Core Domain Models

All under [app/Models](app/Models):
- `ShopifyShop` - Shopify store with API credentials
- `User` - Application user with admin status
- `UserShopAccess` - Pivot linking users to shops with access level
- `Offer` - Wine offers with Shopify variant linkage (shop-scoped)
- `OfferManifest` - Individual bottle allocations to orders
- `OrderToVariant` - Links Shopify orders to variants for webhook processing
- `CombineOperation` - Tracks combine shipping operations with dual parent support (audit_log_id OR webhook_id)
- `CombineOperationLog` - Detailed event logs for combine operations (like webhook_subs)

## Service Layer

### Offer Services ([app/Services/Offer](app/Services/Offer))
- `OfferService` - CRUD, detail views with manifests, metafield updates, order data
- `OfferManifestService` - Manifest quantity management, product data enrichment

### Shopify Services ([app/Services/Shopify](app/Services/Shopify))
- `ShopifyClient` - Base GraphQL client with shop-specific credentials
- `ShopifyProductService` - Product data, inventory, metafields
- `ShopifyOrderService` - Order queries, cancel, capture
- `ShopifyOrderProcessingService` - Full order processing with manifest allocation, diversity check (retries up to 5x for variety), and force repick support
- `ShopifyOrderEditService` - Order edit mutations (line items, discounts, shipping)
- `ShopifyFulfillmentService` - Fulfillment order operations (low-level GraphQL)
- `FulfillmentOrderService` - High-level combine shipping operations with audit logging (moved from ShopifyOrderProcessingService)

## API Surface

Defined in [routes/api.php](routes/api.php):

### Shops
- `GET /api/shops` - List accessible shops for current user

### Offers (shop-scoped)
- `GET/POST /api/shops/{shop}/offers` - List/create offers (list supports `?status=active|archived`)
- `GET/DELETE /api/shops/{shop}/offers/{id}` - Get/delete (deletion fails if allocated manifests exist)
- `POST /api/shops/{shop}/offers/{id}/archive` - Archive an offer (only if ended)
- `POST /api/shops/{shop}/offers/{id}/unarchive` - Unarchive an offer
- `GET /api/shops/{shop}/offers/{id}/metafields` - Update and return Shopify metafields
- `GET /api/shops/{shop}/offers/{id}/orders` - Get orders with manifest allocations
- `GET /api/shops/{shop}/offers/cleanup-count` - Get count of offers that can be archived (ended >30d)
- `POST /api/shops/{shop}/offers/cleanup` - Bulk archive offers that ended >30d ago

### Manifests (shop-scoped)
- `GET/PUT /api/shops/{shop}/offers/{id}/manifests` - Get summary / update quantities

### Shopify (shop-scoped)
- `GET /api/shops/{shop}/shopify/products?type=deal|manifest-item` - Get tagged products
- `POST /api/shops/{shop}/shopify/product-data` - Get product data by variant IDs
- `POST /api/shops/{shop}/shopify/set-inventory` - Set inventory quantity
- `POST /api/shopify/webhook` - Order webhook (HMAC verified, uses X-Shopify-Shop-Domain header)

### Admin
- `GET/POST /api/admin/users` - List/create users
- `GET/PUT/DELETE /api/admin/users/{id}` - User CRUD
- `PUT /api/admin/users/{id}/shop-accesses` - Update user shop access
- `GET/POST /api/admin/stores` - List/create stores
- `GET/PUT/DELETE /api/admin/stores/{id}` - Store CRUD
- `GET /api/admin/webhooks` - List webhooks
- `GET /api/admin/webhooks/{id}` - Get details
- `POST /api/admin/webhooks/{id}/rerun` - Re-run webhook
- `GET /api/admin/audit-logs` - List and search audit logs
- `GET /api/admin/combine-operations` - List combine shipping operations
- `GET /api/admin/combine-operations/{id}` - Get combine operation details with logs
- `POST /api/admin/shops/{shop}/orders/{orderId}/repick` - Force repick all manifests for an order
- `POST /api/admin/shops/{shop}/orders/{orderId}/combine-shipping` - Combine shipping (merge fulfillment orders)

## Frontend Patterns

### Data Fetching
Use [resources/js/fetchWrapper.ts](resources/js/fetchWrapper.ts):
```typescript
const data = await fetchWrapper.get(`${apiBase}/offers`);
await fetchWrapper.post(`${apiBase}/offers`, { offer_name: '...', ... });
await fetchWrapper.put(`${apiBase}/offers/${id}/manifests`, { manifests: [...] });
await fetchWrapper.delete(`${apiBase}/offers/${id}`, {});
```

Includes CSRF meta header, `credentials: include`, JSON parsing with fallback.

### UI Components
All under [resources/js/components/ui](resources/js/components/ui):
- Use shadcn/ui components (Button, Table, Badge, Alert, Input, Label, Checkbox, Textarea, etc.)
- Money formatting via [resources/js/lib/currency.ts](resources/js/lib/currency.ts)

### Page Structure
Each page follows this pattern:
1. Mount element with data attributes
2. `createRoot` renders the TSX component
3. Component fetches data from API on mount
4. Loading states handled using `<Skeleton>` components from `resources/js/components/ui/skeleton.tsx` instead of raw text.
5. Container + MainTitle + content layout

## Key UI Flows

### Offers (Shop List) ([resources/js/shops.tsx](resources/js/shops.tsx))
- Entry point for most users (labeled "Offers" in navbar).
- Lists accessible shops with access level badges.
- Links to shop dashboard/offers.

### Admin User Management ([resources/js/admin-users.tsx](resources/js/admin-users.tsx), [resources/js/admin-user-detail.tsx](resources/js/admin-user-detail.tsx))
- Labeled "Users" in navbar.
- List users with create/delete actions.
- Edit user details and shop access assignments.

### Manage Shops (Admin Store Management) ([resources/js/admin-stores.tsx](resources/js/admin-stores.tsx), [resources/js/admin-store-detail.tsx](resources/js/admin-store-detail.tsx))
- Labeled "Manage Shops" in navbar.
- List stores with create/delete actions.
- Edit store details and API credentials.
- **Logic**: When the first store is created, any existing offers with `shop_id IS NULL` are automatically assigned to it.

### Webhook Management ([resources/js/admin-webhooks.tsx](resources/js/admin-webhooks.tsx), [resources/js/admin-webhook-detail.tsx](resources/js/admin-webhook-detail.tsx))
- Labeled "Webhooks" in navbar (needs to be added).
- Lists all incoming webhooks with status badges.
- Detail page shows payload, headers, and execution logs (webhook_sub events).
- Detail page also shows combine operations linked to the webhook (if any).
- Re-run functionality creates a new webhook record linked to the original.

### Audit Log Management ([resources/js/admin-audit-logs.tsx](resources/js/admin-audit-logs.tsx))
- Labeled "Audit Log" in navbar.
- Paginated list of system events (event name, user ID, timestamp).
- Searchable by event name, details, order ID, or offer ID.
- Detailed view using `AuditLogDetailCell`: large JSON payloads are displayed in a modal dialog with copy-to-clipboard functionality to keep the table clean.
- Links to Combine Operations page for viewing shipping merge operations.

### Combine Operations ([resources/js/admin-combine-operations.tsx](resources/js/admin-combine-operations.tsx), [resources/js/admin-combine-operation-detail.tsx](resources/js/admin-combine-operation-detail.tsx))
- Tracks combine shipping operations (fulfillment order merging).
- Combine operations can be linked to either an audit log (manual admin trigger) or a webhook (webhook-triggered).
- List page shows all combine operations with status, shop, user, and shipping method info.
- Detail page displays:
  - Summary: order link, shop, user, timestamps
  - Original shipping method identified from order
  - Fulfillment orders before/after count
  - Event log with detailed steps and Shopify API call data
- Each operation links back to audit log or webhook for complete traceability.

## Shopify Performance & Caching

The `ShopifyProductService` implements a robust caching strategy to minimize API calls to Shopify:
- **Extended TTL**: Most product and variant data is cached for **1 hour**.
- **Individual Variant Caching**: Variants are cached by their specific ID hash, allowing for partial cache hits in bulk requests.
- **Proactive Invalidation**: Caches are automatically cleared when inventory is updated or metafields are written via the service.
- **Shop-Specific**: All caching is scoped to the specific Shopify shop credentials.

### Offer List ([resources/js/offers.tsx](resources/js/offers.tsx))
- Lists offers with Shopify product data
- Filter by Active vs Archived status
- Actions: Delete (if not ended and no allocations), Archive (if ended), Unarchive (if archived)
- Links to detail page, delete action

### Offer Detail ([resources/js/offer-detail.tsx](resources/js/offer-detail.tsx))
- Shows offer info, manifest table grouped by variant
- Action buttons: Add Bottles, View Orders, Profitability, Metafields
- Handles deficit alerts and Shopify quantity sync
- Delete manifest action for unallocated items

### Add Manifest ([resources/js/offer-add-manifest.tsx](resources/js/offer-add-manifest.tsx))
- Product selector with search filter
- Quantity input, submits to PUT manifests endpoint

### Profitability ([resources/js/offer-profitability.tsx](resources/js/offer-profitability.tsx))
- Calculates margins from offer price vs unit costs
- Shows product breakdown and sell-through scenarios
- Best/worst case profit analysis

### Metafields ([resources/js/offer-metafields.tsx](resources/js/offer-metafields.tsx))
- Fetches and displays offer metafield JSON
- Updates Shopify product metafields on load

### Order Manifests ([resources/js/offer-manifests.tsx](resources/js/offer-manifests.tsx))
- Shows orders with purchased vs upgrade items
- Highlights quantity mismatches
- Links to Shopify order admin
- Admin-only Repick button to force reassignment of all manifests for an order (disabled for cancelled/shipped orders with tooltip explanation)
- Admin-only Combine button to merge fulfillment orders and apply the original shipping method selected by the customer (enabled when there are multiple open fulfillment orders or a single generic "Shipping" group)

## Authentication

- Laravel session-based auth with login redirect
- [bootstrap/app.php](bootstrap/app.php) configures guest redirect with intended URL
- [LoginController](app/Http/Controllers/Auth/LoginController.php) handles redirect param

## Configuration

### Shopify ([config/services.php](config/services.php))
Shopify API credentials are now stored per-store in the `shopify_shops` table, not in environment variables. The legacy config block remains for reference:
```php
'shopify' => [
    'store_url' => env('SHOPIFY_STORE_URL'),
    'access_token' => env('SHOPIFY_ACCESS_TOKEN'),
    'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET'),
],
```

## Build/Test Workflow

```bash
# Install
composer install && pnpm install
cp .env.example .env
php artisan key:generate
php artisan migrate

# Development
composer dev    # Runs artisan serve + Vite concurrently
# Or separately:
php artisan serve
pnpm dev

# Testing
composer test   # PHPUnit
pnpm test       # Jest

# Build
pnpm build
```

## PHP Testing Guidelines

### Database Safety

**CRITICAL**: Tests MUST use SQLite, never MySQL. This is enforced at multiple levels:
- `phpunit.xml` sets `DB_CONNECTION=sqlite`
- `.env.testing` provides backup configuration  
- `TestCase.php` throws an exception if MySQL is detected

### Test Base Classes

#### `Tests\TestCase` - For non-database tests
Use for unit tests or feature tests that don't need database access.

```php
namespace Tests\Feature;

use Tests\TestCase;

class MyTest extends TestCase
{
    public function test_something(): void
    {
        // No database access needed
        $response = $this->get('/health');
        $response->assertOk();
    }
}
```

#### `Tests\DatabaseTestCase` - For database tests
Use for tests that need database access. Includes `RefreshDatabase` trait and helper methods.

```php
namespace Tests\Feature;

use Tests\DatabaseTestCase;

class MyDatabaseTest extends DatabaseTestCase
{
    public function test_user_creation(): void
    {
        // Database is set up with SQLite schema
        $user = $this->createTestUser(isAdmin: true);
        $shop = $this->createTestShop();
        $this->grantShopAccess($user, $shop, 'read-write');

        $this->assertDatabaseHas('users', [
            'email' => $user->email,
        ]);
    }
}
```

### Helper Methods in DatabaseTestCase

- `createTestUser(bool $isAdmin = false)` - Create a test user
- `createTestShop(array $attributes = [])` - Create a test Shopify shop
- `grantShopAccess(User $user, ShopifyShop $shop, string $accessLevel)` - Grant shop access

### Test File Organization

```
tests/
├── TestCase.php           # Base class with SQLite safety checks
├── DatabaseTestCase.php   # Base class for database tests
├── Traits/
│   └── RequiresSqlite.php # Reusable SQLite enforcement trait
├── Feature/               # Feature/integration tests
│   ├── ExampleTest.php
│   └── DatabaseExampleTest.php
└── Unit/                  # Unit tests
    └── ExampleTest.php
```

### Database Schema

The SQLite schema is at `database/schema/sqlite-schema.sql`. This is used by `RefreshDatabase` trait.

When adding new migrations for MySQL:
1. Add the migration for MySQL as usual
2. Update `database/schema/sqlite-schema.sql` with the SQLite-compatible equivalent
3. Run tests to verify the schema works

### Running Tests

```bash
# Run all PHP tests
composer test

# Run specific test file
./vendor/bin/phpunit tests/Feature/DatabaseExampleTest.php

# Run specific test method
./vendor/bin/phpunit --filter test_can_create_user

# Run with verbose output
./vendor/bin/phpunit -v
```

## When Extending

1. **New pages**: Add blade template, TSX entry point, Vite input, web route
2. **New API endpoints**: Add service method, controller method, api route
3. **Shopify operations**: Add GraphQL constant and method to appropriate service
4. **Keep** `credentials: include` in fetches for session + CSRF
5. **Use** shadcn components, not react-bootstrap
6. **Follow** first-or-create patterns for per-entity resources
7. **Write tests**: Extend `DatabaseTestCase` for tests needing database, `TestCase` otherwise
8. **Update SQLite schema**: When adding MySQL migrations, also update `database/schema/sqlite-schema.sql`

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/framework (LARAVEL) - v10
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v10
- tailwindcss (TAILWINDCSS) - v4


## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v10 rules ===

## Laravel 10

- Use the `search-docs` tool to get version specific documentation.
- Middleware typically live in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in Laravel 10:
    - Middleware registration is in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule registration is in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`
- When using Eloquent model casts, you must use `protected $casts = [];` and not the `casts()` method. The `casts()` method isn't available on models in Laravel 10.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit <name>` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>
