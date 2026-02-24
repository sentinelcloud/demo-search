# Demo Search — Meilisearch + Laravel + React

A reference demo showing **Meilisearch-powered search** in **Laravel** with a modern **React** UI (Tailwind CSS + shadcn/ui), including seeded data, indexing, filters, sorting, and a production-style search UX.

![Laravel](https://img.shields.io/badge/Laravel-12-red) ![React](https://img.shields.io/badge/React-19-blue) ![Meilisearch](https://img.shields.io/badge/Meilisearch-1.12-purple) ![Tailwind](https://img.shields.io/badge/Tailwind-4-cyan)

## Features

- **Instant search** with debounced input (300ms)
- **Typo tolerance** — try "iphnoe" and get iPhone-like matches
- **Faceted filtering** — category, brand, price range, stock status
- **Sorting** — relevance, price (asc/desc), rating, newest
- **Highlighted results** — matching terms highlighted in title and description
- **Pagination** with page controls
- **Grid/List view** toggle
- **Loading skeletons** during search transitions
- **Empty states** with helpful suggestions
- **Admin CRUD** — create/edit/delete products with live Meilisearch sync
- **2,000 seeded products** across 10 categories and 20 brands
- **Database fallback** — works without Meilisearch (falls back to SQLite LIKE queries)

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.2+ |
| Search Engine | Meilisearch 1.12 (via Docker) |
| Search Integration | Laravel Scout + meilisearch-php |
| Frontend | React 19, TypeScript, Inertia.js 2 |
| Styling | Tailwind CSS 4, shadcn/ui components |
| Database | SQLite |
| Build | Vite 7 |

## Prerequisites

- **PHP 8.2+** with SQLite extension
- **Composer**
- **Node.js 18+** and npm
- **Docker** (for Meilisearch)

## Quick Start

```bash
# 1. Clone and enter the project
git clone <repo-url> demo-search
cd demo-search

# 2. Start Meilisearch
docker compose up -d

# 3. Run full setup (install deps, migrate, seed, index)
make setup

# 4. Start development servers
make dev
```

Then open **http://localhost:8000** in your browser.

### Manual Setup (without Make)

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed
docker compose up -d
php artisan scout:sync-index-settings
php artisan scout:import "App\Models\Product"
npm run build
composer run dev
```

## Commands

| Command | Description |
|---------|------------|
| `make setup` | Full setup: deps, migrate, seed, sync, import, build |
| `make fresh` | Reset: drop DB, re-migrate, re-seed, re-index |
| `make dev` | Start Laravel + Vite dev servers |
| `make seed` | Seed 2,000 products |
| `make index` | Import products into Meilisearch |
| `make sync` | Push index settings to Meilisearch |
| `make meilisearch` | Start Meilisearch via Docker |

## Project Structure

```
app/
├── Http/Controllers/
│   ├── SearchController.php    # Search with Meilisearch faceted search
│   └── AdminController.php     # CRUD for products
├── Models/
│   └── Product.php             # Eloquent model with Scout Searchable
└── Providers/
    └── AppServiceProvider.php   # Meilisearch client binding

database/
├── factories/ProductFactory.php  # Realistic product generator
├── migrations/                   # Products table
└── seeders/ProductSeeder.php     # Seeds 2,000 products

resources/js/
├── app.tsx                     # Inertia app entry point
├── components/
│   ├── FacetSidebar.tsx        # Category, brand, price, stock filters
│   ├── ProductCard.tsx         # Product display (grid + list modes)
│   ├── ProductGrid.tsx         # Results grid with empty/loading states
│   ├── ProductSkeleton.tsx     # Loading skeleton
│   ├── SearchInput.tsx         # Debounced search with Cmd+K shortcut
│   ├── SearchMeta.tsx          # Result count + query time
│   ├── SearchPagination.tsx    # Page navigation
│   ├── SortDropdown.tsx        # Sort selector
│   └── ui/                     # shadcn/ui primitives
├── pages/
│   ├── Search.tsx              # Main search page
│   └── Admin/
│       ├── Index.tsx           # Product listing table
│       └── Form.tsx            # Create/edit form
└── types/index.ts              # TypeScript interfaces

config/scout.php                # Meilisearch index settings
docker-compose.yml              # Meilisearch service
```

## Example Searches

Try these on the search page:

| Query | What it demonstrates |
|-------|---------------------|
| `headphones` | Basic full-text search |
| `iphnoe` | Typo tolerance |
| `wireless bluetooth` | Multi-word search |
| `laptop` → filter Electronics | Search + faceted filter |
| (empty) → sort Price: Low → High | Browse mode with sorting |
| `yoga` → toggle In Stock | Filter combination |

## Meilisearch Dashboard

While running, access the Meilisearch dashboard at **http://localhost:7700** with API key `masterKeyDemoSearch2024`.

## Architecture Notes

- **Inertia.js** connects Laravel and React — no separate API or CORS needed
- **Search uses Meilisearch PHP client directly** (not Scout `::search()`) for facets and highlighting
- **Database fallback** catches Meilisearch errors, falls back to SQLite queries
- **Scout Searchable trait** handles automatic sync on create/update/delete
- **`withoutSyncingToSearch`** used during seeding for batch performance

## License

MIT
