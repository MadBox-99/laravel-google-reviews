# cegem360/laravel-google-reviews — Design Spec

**Date:** 2026-07-08
**Status:** Approved for planning

## 1. Summary

A reusable Composer package that fetches a Google location's reviews via the
**Google Places API (New)**, stores/caches them, and renders them with a
ready-made, publishable Blade component. It installs into both plain Laravel and
Filament Laravel projects with a single `composer require` plus minimal config.

### Known constraint
The Google Places API returns at most **5 reviews per place** (Google selects the
"most relevant"), plus the aggregate rating (average + total count). The package
cannot exceed this — it is an API limitation, not a package choice.

## 2. Target stack

- PHP 8.3+
- Laravel 12
- Filament 4 (optional / soft dependency)
- Tests: Pest v3 + Orchestra Testbench
- Root namespace: `Madbox99\GoogleReviews`
- Package name: `cegem360/laravel-google-reviews`
- Service provider: **hand-written `GoogleReviewsServiceProvider`**, zero extra
  dependencies (no spatie/laravel-package-tools). It registers the config merge,
  facade/manager binding, command, views, and the publish groups manually.

## 3. Architecture

Small, single-purpose units communicating through explicit interfaces.

| Unit | Responsibility | Depends on |
|---|---|---|
| `GooglePlacesClient` | Calls Places API (New) Place Details via Laravel `Http`; maps raw JSON → DTOs. | `SettingsResolver`, `Http` |
| `PlaceData` (readonly DTO) | Typed place: place id, name, rating, total ratings, list of `ReviewData`. | — |
| `ReviewData` (readonly DTO) | Typed single review: author name, author photo url, rating, text, language, published_at, google review id. | — |
| `ReviewRepository` (interface) | Read/write reviews for a place. | — |
| `DatabaseReviewRepository` | Persists to `google_review_places` + `google_reviews` tables. | Eloquent models |
| `CacheReviewRepository` | Stores DTOs in Laravel cache with TTL. | `Cache` |
| `ReviewRepositoryFactory` | Returns the active repository based on resolved `driver`. | `SettingsResolver` |
| `GoogleReviewsManager` (+ `GoogleReviews` facade) | Entry point. `for(?placeId)` reads from active repository; `refresh(?placeId)` fetches from API and stores. | client, factory |
| `SyncReviewsCommand` (`google-reviews:sync`) | Fetches from API and stores via repository. Schedulable. | manager |
| `SettingsResolver` | Resolves each setting with precedence **DB (Filament UI) → config file → env**. API key resolved from **env/config only** (never DB). | config, DB settings |
| `google-reviews.blade.php` component | Renders reviews (stars, avatar, text). Publishable/overridable. | manager |
| `GoogleReviewsPlugin` | Thin Filament plugin registering the settings page. | Filament (optional) |
| `GoogleReviewsSettings` page | Filament page to edit place id, driver, cache ttl, language. | plugin |

### Data flow

```
google-reviews:sync (or first uncached read)
  → GooglePlacesClient  → GET Places API (New) Place Details
  → PlaceData + ReviewData[]
  → active ReviewRepository.store()           (DB table OR cache)

<x-google-reviews /> / GoogleReviews::for()
  → active ReviewRepository.get()             (no API call per page view)
```

## 4. Storage drivers (config-selectable)

### `database`
- `google_review_places`: `id`, `place_id` (unique), `name`, `rating` (decimal),
  `user_ratings_total` (int), `synced_at`, timestamps.
- `google_reviews`: `id`, `place_id`, `google_review_id` (unique per place),
  `author_name`, `author_photo_url`, `rating`, `text`, `language`,
  `published_at`, `visible` (bool, default true, for moderation),
  `raw` (json), timestamps.
- Eloquent models: `GoogleReviewPlace`, `GoogleReview`.

### `cache`
- No migration. `PlaceData` (incl. its reviews) serialized into Laravel cache
  under a namespaced key per place, with configurable TTL.
- The `visible`/moderation flag has no effect in cache mode (documented).

## 5. Configuration

`config/google-reviews.php`:

```php
return [
    'api_key'  => env('GOOGLE_PLACES_API_KEY'),
    'place_id' => env('GOOGLE_PLACES_PLACE_ID'),  // string, or array of ids
    'driver'   => env('GOOGLE_REVIEWS_DRIVER', 'database'), // database|cache
    'cache_ttl'=> env('GOOGLE_REVIEWS_CACHE_TTL', 86400),   // seconds
    'language' => env('GOOGLE_REVIEWS_LANGUAGE', 'hu'),
    'table_prefix' => 'google_review',
];
```

Every value is env-overridable. When Filament settings exist in the DB, they
override the config file (except `api_key`). Resolution order per key:
**DB setting → config → env default**.

## 6. Filament integration (optional)

- `GoogleReviewsPlugin::make()` added to the panel provider registers a
  **Settings page** exposing: place id, driver, cache ttl, language. API key is
  shown read-only with a note that it lives in env.
- Settings persist in a single-row key/value settings table
  (`google_review_settings`) read by `SettingsResolver`.
- Without Filament the package works identically from config/env — Filament is a
  soft dependency (`suggest` in composer, feature-detected at runtime).

## 7. Customization / publishing

`php artisan vendor:publish` tags:
- `google-reviews-config` → `config/google-reviews.php`
- `google-reviews-views` → Blade component view (Tailwind-based, fully overridable)
- `google-reviews-migrations` → the two migrations

The default Blade view uses Tailwind utility classes and is designed to be
replaced after publishing.

## 8. Testing

Pest v3 + Testbench. Google responses mocked with `Http::fake()`.

Coverage:
- `GooglePlacesClient` maps a fixture JSON response into correct DTOs.
- `DatabaseReviewRepository` and `CacheReviewRepository` store/read round-trip.
- `ReviewRepositoryFactory` selects the driver from resolved config.
- `SettingsResolver` precedence: DB overrides config overrides env; api_key never
  from DB.
- `SyncReviewsCommand` fetches (faked) and persists.
- Blade component renders reviews and hides `visible=false` (DB mode).

## 9. Out of scope (YAGNI)

- Google Business Profile API / OAuth / replying to reviews.
- More than 5 reviews per place (API limit).
- Filament review-moderation Resource, dashboard widget (settings page only for now).
- Livewire component (Blade only for now).

## 10. Open items

None. Single-place default with array support baked in; hand-written service
provider with zero extra dependencies.
