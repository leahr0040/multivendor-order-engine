# Multivendor Order Engine

A shopper builds one cart; the engine prices every line at the cheapest vendor that stocks it,
splits the order into one sub-order per vendor, and notifies each vendor on a queue.

Laravel 13 + Inertia/React 19 + MySQL. Prices are integers in minor units (agorot) — no floats anywhere.

## Setup

Needs PHP 8.3+, Node 20+, and MySQL.

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate   # fill in DB_USERNAME / DB_PASSWORD
mysql -e "CREATE DATABASE multivendor_order_engine"
php artisan migrate --seed
```

For the test suite, repeat with the testing env — it runs against a real MySQL schema, not SQLite:

```bash
cp .env.testing.example .env.testing && php artisan key:generate --env=testing
mysql -e "CREATE DATABASE multivendor_order_engine_testing"
php artisan migrate --env=testing
```

## Running

```bash
composer dev     # server + queue worker + vite + logs, on http://localhost:8000
composer test    # the full gate: pint + phpstan (level 7) + tests
```

The queue worker matters: vendor notification and the order's transition to `completed` only
happen once the jobs run.

## What it does

Browse the catalog at `/`, check out, and land on `/orders/{ulid}` showing the per-vendor split.
The same domain core is exposed as JSON:

```bash
POST /api/cart/quote
{ "items": [{ "product": "<product_ulid>", "quantity": 2 }], "user": "<user_ulid>" }

POST /api/orders
{ "items": [...], "user": "<user_ulid>", "idempotency_key": "<ulid>" }
```

`user` is optional and only picks the loyalty tier for pricing — there is no auth yet.
Quoting is free of side effects; placing an order is idempotent on `idempotency_key`, so a
resubmit returns the existing order with `200` instead of creating a second one.

Unknown, unavailable, or duplicated products are a `422` with the offending ULIDs listed —
the cart is never silently repaired.

## How it works

`CartPriceService` resolves the cheapest active vendor per product in one query, prices each
line, and hands the cart to `DiscountEngineService`. `PlaceOrder` writes the order, its
vendor sub-orders, and their items in a single transaction, then fires `OrderPlaced`;
`NotifyVendors` batches a `NotifyVendorJob` per vendor and marks the order completed when the
batch finishes. Each vendor's totals are the sum of its own lines only — an order-wide
adjustment is never spread across vendors.

`DiscountEngineService` is deliberately an empty rule set: the seam is wired end to end
(per-line `discount` and `applied_rules` flow from pricing through to the stored order items),
the rules themselves are not implemented.
