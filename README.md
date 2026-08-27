# Multi-Vendor Order Processing Engine

A shopper builds one cart. The engine prices every line at the cheapest vendor that stocks it, runs
a configurable set of discount rules over each line, splits the result into one sub-order per vendor,
persists the whole thing in a single transaction, and notifies each vendor asynchronously on a queue.

**Stack:** Laravel 13 · PHP 8.3 · MySQL · Inertia + React 19 + TypeScript · Tailwind v4 · database queues.

> All money is stored and returned as **integers in minor units** (agorot). No floats anywhere in the
> pricing path.

---

## Setup & Installation

**Requirements:** PHP 8.3+, Composer 2, Node 20+, MySQL 8+ (a window function and a real MySQL schema
are both required — SQLite is not supported).

### 1. Backend

```bash
git clone <repository-url> multivendor-order-engine
cd multivendor-order-engine

composer install

cp .env.example .env
php artisan key:generate
# fill in DB_USERNAME / DB_PASSWORD in .env
```

Create the schema and load demo data:

```bash
mysql -e "CREATE DATABASE multivendor_order_engine"
php artisan migrate --seed
```

The seeders create categories, vendors, products, a randomised price per vendor for **every** product
— so lowest-price selection always has something to choose between — and one customer per loyalty
tier:

| Seeded customer | Loyalty tier |
| --- | --- |
| `none@example.com` | none |
| `silver@example.com` | silver |
| `gold@example.com` | gold |

### 2. Test database

`phpunit.xml` sets only `APP_ENV=testing`; everything else comes from `.env.testing`, which points at
a **real MySQL schema**:

```bash
cp .env.testing.example .env.testing
php artisan key:generate --env=testing
mysql -e "CREATE DATABASE multivendor_order_engine_testing"
php artisan migrate --env=testing
```

### 3. Frontend

```bash
npm install
```

Typed route helpers under `resources/js/routes`, `actions` and `wayfinder` are **generated** at build
time and gitignored — the dev server or a build must run once before those imports resolve.

---

## Running the Stack

```bash
composer dev     # serve + queue worker + vite + pail, all on http://localhost:8000
npm run build    # production assets
```

> **The worker matters.** Nothing runs inline. Vendor notification and the transition of the
> sub-orders and the main order to `completed` only happen once `php artisan queue:work` drains the
> batch.

---

## Architecture

### Two delivery layers over one domain core

```
┌──────────────────────────┐   ┌──────────────────────────┐
│  Inertia / React pages   │   │   Stateless JSON API     │
│  routes/web.php          │   │   routes/api.php         │
│  server-driven props     │   │   no session, no CSRF    │
└────────────┬─────────────┘   └────────────┬─────────────┘
             │                              │
             └──────────────┬───────────────┘
                            ▼
             ┌────────────────────────────────┐
             │   HTTP boundary                │
             │   validation · payload shaping │
             │   · outcome → status code      │
             └───────────────┬────────────────┘
                             ▼
             ┌────────────────────────────────┐
             │        Domain core             │
             │  pricing · discounts · split   │
             │  · placement                   │
             └───────────────┬────────────────┘
                             ▼
             ┌────────────────────────────────┐
             │  Persistence  ·  Event bus     │
             │  Eloquent      ·  queued jobs  │
             └────────────────────────────────┘
```

Both delivery layers are thin and neither holds business logic — they call the same domain core. The
web pages and the API return the same numbers because they are computed in exactly one place.

### Module map

```
app/
├── Http/          delivery layer — controllers, form requests, middleware
├── Services/
│   ├── Pricing/     cart pricing pipeline
│   ├── Discounts/   discount engine + interchangeable rules
│   └── Orders/      vendor splitting + order placement
├── Data/          immutable typed payloads (cart, priced cart, order, sub-order, item)
├── Events/        domain events emitted after commit
├── Listeners/     event handlers that queue work
├── Jobs/          queued units of work
├── Enums/         statuses, categories, loyalty tiers — never string literals
└── Models/        relations, casts, scopes
```

Services are grouped **by domain, not by direction** — the whole order flow lives together whether it
answers a question or writes a row.

### The pricing pipeline

Pricing a cart runs as four ordered stages, each one input and one output:

1. **Vendor resolution** — the cheapest active listing per product, resolved for the whole cart in a
   single `ROW_NUMBER() OVER (PARTITION BY product ORDER BY price)` query. A product with no active
   listing short-circuits here and the remaining stages never run.
2. **Line construction** — quantity × unit price, at list price.
3. **Discount application** — the rules that fired are recorded on each line.
4. **Totalling** — the priced cart and its totals.

### Asynchronous by design

Queues are database-backed and nothing runs inline. Placement commits, then emits a domain event; a
listener fans the vendor notifications out as a **job batch**, one job per vendor, and the batch's
completion callback is what advances the parent order to `completed`. The jobs are dispatched only
after the transaction commits, so a worker can never pick up a job for a row that is not yet visible.
The HTTP response returns as soon as the order is written.

---

## Domain Model

```
vendors ──┐
          ├── listings (vendor × product × price × active flag)
products ─┘        ▲
   │               │ cheapest active listing wins
categories         │
                   │
orders ──── sub_orders ──── order_items ──▶ the exact listing bought
  │            │                 └── the discount rules that fired (json)
  │            └── one per vendor
  └── idempotency key (unique) · original / discount / final price
```

Money is recorded at three levels — item, sub-order, order — and each level stores the original
price, the discount and the final price, so any total can be reconciled against its parts.

A sub-order's totals are the sum of **its own lines only**. An order-wide adjustment is never spread
across vendors, so each vendor is billed exactly what its own items came to.

Each item stores the listing it was bought from, so the price paid stays explainable even after a
vendor changes or withdraws its offer.

---

## API Reference

Base URL `/api`. Stateless — no session, no CSRF token. Validation failures return `422` with
Laravel's standard error envelope.

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `POST` | `/api/cart/quote` | Price a cart — read-only, no writes |
| `POST` | `/api/orders` | Place an order — idempotent |

`user` is optional and selects the **loyalty tier for pricing only** — it is not an authenticated
identity. There is no auth layer in this assessment.

### `POST /api/orders`

```http
POST /api/orders HTTP/1.1
Content-Type: application/json
Accept: application/json

{
  "items": [
    { "product": "01JBQ2K7X8N4M5PQRSTVWXYZ01", "quantity": 2 },
    { "product": "01JBQ2K7X8N4M5PQRSTVWXYZ02", "quantity": 6 }
  ],
  "user": "01JBQ2K7X8N4M5PQRSTUSER001",
  "idempotency_key": "01JBQ8Z9Y0A1B2C3D4E5F6G7H8"
}
```

**`201 Created`** — a Gold customer (7%), one electronics line (5%) and one books line (10%) that also
clears the 5-unit bulk threshold (5%). The two products are cheapest at different vendors, so the
order splits in two — the second sub-order has the same shape:

```json
{
  "ulid": "01JBQ9A0B1C2D3E4F5G6H7J8K9",
  "status": "pending",
  "original_price": 52800,
  "discount": 9036,
  "final_price": 43764,
  "created_at": "2026-08-27T09:14:22+00:00",
  "sub_orders": [
    {
      "ulid": "01JBQ9A0B1C2D3E4F5G6H7J8KA",
      "status": "pending",
      "vendor": { "ulid": "01JBQ2K7X8N4M5PQRSVENDOR1", "name": "Northwind Supply" },
      "original_price": 25800,
      "discount": 3096,
      "final_price": 22704,
      "order_items": [
        {
          "product": {
            "ulid": "01JBQ2K7X8N4M5PQRSTVWXYZ01",
            "name": "Wireless Headphones",
            "description": "Over-ear, active noise cancelling.",
            "category": { "slug": "electronics", "name": "Electronics" }
          },
          "quantity": 2,
          "original_unit_price": 12900,
          "original_price": 25800,
          "discount": 3096,
          "final_price": 22704
        }
      ]
    },
    { "…": "Meridian Trading — 6 × Clean Code, 27000 − 5940 = 21060" }
  ]
}
```

The order comes back `pending`; the worker flips each sub-order and then the order to `completed`
once the notification batch drains.

**Idempotency.** Resubmitting the same `idempotency_key` returns the **existing** order with `200 OK`
instead of creating a second one. A concurrent duplicate is caught at the unique constraint rather
than by a read-then-write check, so two simultaneous submissions cannot both win.

**Status codes**

| Code | Meaning |
| --- | --- |
| `201` | Order created |
| `200` | Replay of a known `idempotency_key` — the original order |
| `422` | Unknown product, duplicate cart line, no active vendor offer, or invalid payload |

The cart is never silently repaired — the `422` names the offending identifiers:

```json
{
  "message": "No vendor currently offers: 01JBQ2K7X8N4M5PQRSTVWXYZ09",
  "errors": {
    "items": ["No vendor currently offers: 01JBQ2K7X8N4M5PQRSTVWXYZ09"]
  }
}
```

### `POST /api/cart/quote`

The same payload minus `idempotency_key`, and no writes at all. Returns the priced cart with the
discount rules that fired, per line — useful for showing a live cart total before checkout:

```json
{
  "lines": [
    {
      "product_ulid": "01JBQ2K7X8N4M5PQRSTVWXYZ02",
      "product_name": "Clean Code",
      "vendor_ulid": "01JBQ2K7X8N4M5PQRSVENDOR2",
      "vendor_name": "Meridian Trading",
      "quantity": 6,
      "original_unit_price": 4500,
      "original_price": 27000,
      "discount": 5940,
      "final_price": 21060,
      "applied_rules": ["Category offer", "Bulk discount", "Loyalty discount"]
    }
  ],
  "original_price": 27000,
  "discount": 5940,
  "final_price": 21060
}
```

### Web UI

| Route | Page |
| --- | --- |
| `GET /` | Catalog with infinite scroll and cart |
| `GET /orders/{ulid}` | Order confirmation with the per-vendor split |

---

## Discount Configuration

Every rate lives in `config/discounts.php` — no percentage is hardcoded in a rule.

| Rule | Eligibility | Rate source | Default |
| --- | --- | --- | --- |
| Category | The line's product category is configured | Per-category percentage | electronics 5% · books 10% |
| Quantity threshold | Quantity clears at least one configured threshold | Highest matched threshold | 5+ → 5% · 10+ → 12% |
| Loyalty tier | The customer's tier is configured | Per-tier percentage | silver 3% · gold 7% |

**How they combine:** percentages from all eligible rules are **summed**, then capped at the ceiling
(default 50%). Quantity thresholds are not cumulative — the highest matched threshold wins. The
resulting discount is floored to a whole minor unit, so a line's parts always reconcile to its total.
The engine produces a new discounted line rather than mutating the original, and the rules that fired
are persisted on the order item so a historical order can explain its own price.

**Adding a rule:**

1. Implement the discount rule contract in `app/Services/Discounts/Rules`.
2. Add it to the tagged rule set in the discount service provider.
3. Add its display label to `lang/en/discounts.php`.

Rates are additive, so registration order is irrelevant and no existing class changes.

---

## Testing & QA

```bash
composer test       # the full gate: config:clear + pint --test + phpstan (level 7) + artisan test
php artisan test    # fast loop
composer lint       # pint --parallel (fix)
composer types:check
npm run lint && npm run types:check && npm run format
```

Targeted runs:

```bash
php artisan test --filter=OrderSplitTest
php artisan test --filter='it splits by vendor'
php artisan test tests/Feature/OrderTest.php
```

Because queues are asynchronous and nothing runs inline, async behaviour is asserted on the
**dispatch** with faked queues and buses, never on a job's side effect.
