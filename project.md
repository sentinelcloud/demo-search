## Project description: Laravel + React + Meilisearch Demo

This demo project showcases how to integrate **Meilisearch** into a modern **Laravel** backend with a **React** frontend. It’s designed as a practical reference implementation: clean architecture, realistic searchable data, and a polished UI built with **Tailwind CSS** and **shadcn/ui** components.

### Goals

* Demonstrate **Meilisearch in a Laravel context** (indexing, syncing, searching, filtering, sorting).
* Provide a **React search experience** that feels production-like (instant search, facets, highlighting, empty states).
* Generate **repeatable demo data** and fully automate indexing so the project is easy to run locally.

---

## What the demo includes

### 1) Searchable demo domain

A simple but realistic dataset that benefits from search. Example domain (customizable):

* **Products / Catalog items** (title, description, category, tags, brand, price, rating, stock status)
* Optional extras: **Blog posts**, **docs/articles**, or **companies** to show multi-index search

The dataset is generated via Laravel seeders/factories so anyone can run:

* `migrate`
* `seed`
* `index/sync`

…and immediately get meaningful search results.

### 2) Laravel backend (API + indexing)

Laravel provides:

* **REST API endpoints** for searching and fetching results
* **Meilisearch indexing pipeline** (initial import + incremental updates)
* Configuration for:

  * searchable / displayed attributes
  * filterable attributes (e.g., category, tags, inStock)
  * sortable attributes (e.g., price, rating, created_at)
  * synonyms and stop-words (optional demo)
  * typo tolerance and ranking rules (optional demo)

Typical API capabilities showcased:

* Full-text search
* Filters (facets)
* Sorting
* Pagination
* Highlighting / snippet extraction
* “Did you mean” style UX (Meilisearch typo tolerance)

### 3) React frontend (Tailwind + shadcn/ui)

A responsive search UI built with **Tailwind CSS** and **shadcn/ui** that demonstrates:

* Search input with debounced “instant search”
* Results grid/list view
* Facet sidebar (category/tags/price range/in-stock)
* Sort dropdown (relevance, price, newest)
* Pagination or infinite scrolling
* Loading skeletons, empty states, and error states
* Highlighted matching terms in results

---

## Demo flows to highlight

* **Initial import**: seed database → index into Meilisearch
* **Live updates**: create/update/delete a record in Laravel → reflect in Meilisearch
* **Search experience**:

  * typo handling (“iphnoe” → iPhone-like matches)
  * filtering + sorting combinations
  * fast response times and UX feedback

---

## Deliverables

* Fully runnable local environment (e.g., Docker Compose for Laravel + Meilisearch)
* Seeded demo dataset (hundreds/thousands of records)
* Laravel API + indexing commands (one-liners to sync/rebuild)
* React UI implementing a complete search experience with shadcn/ui components
* Clear README with setup steps and example queries

---

## Suggested repo tagline

**“A reference demo showing Meilisearch-powered search in Laravel with a modern React UI (Tailwind + shadcn/ui), including seeded data, indexing, filters, sorting, and a production-style search UX.”**
