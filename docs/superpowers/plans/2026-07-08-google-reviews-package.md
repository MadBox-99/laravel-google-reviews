# Google Reviews Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `cegem360/laravel-google-reviews`, a Composer package that fetches a Google location's reviews via the Google Places API (New), stores them in a DB or cache, and renders them with a publishable Blade component, with an optional Filament settings page.

**Architecture:** Small single-purpose units behind interfaces. A `GooglePlacesClient` maps the API response into readonly DTOs. A `ReviewRepository` interface has `database` and `cache` implementations, chosen at runtime by a factory from resolved settings. A `GoogleReviewsManager` (exposed via a `GoogleReviews` facade) is the entry point for reads and refreshes. A hand-written service provider wires everything; a soft-dependency Filament plugin adds a settings page.

**Tech Stack:** PHP 8.3+, Laravel 12, Filament 4 (optional), Pest v3, Orchestra Testbench, Laravel `Http`/`Cache`, Tailwind (Blade view).

## Global Constraints

- PHP `^8.3`; Laravel (`illuminate/*`) `^12.0`; Filament `^4.0` as an **optional** dependency (`suggest` + `require-dev`), feature-detected at runtime.
- Root namespace: `Madbox99\GoogleReviews` → `src/`. Test namespace: `Madbox99\GoogleReviews\Tests` → `tests/`.
- **Zero extra runtime dependencies** beyond the Laravel framework — no `spatie/laravel-package-tools`. Hand-written `GoogleReviewsServiceProvider`.
- Google Places API (New) returns **at most 5 reviews per place**; never assume more.
- API key is resolved from **env/config only, never from the DB**. All other settings resolve **DB → config → env**.
- Storage driver is config-selectable: `database` (default) or `cache`.
- Every code step is TDD: failing test first, then minimal implementation. Commit after each task.

---

## File Structure

```
composer.json
config/google-reviews.php
src/
  GoogleReviewsServiceProvider.php
  Facades/GoogleReviews.php
  GoogleReviewsManager.php
  Settings/SettingsResolver.php
  Http/GooglePlacesClient.php
  Data/ReviewData.php
  Data/PlaceData.php
  Contracts/ReviewRepository.php
  Repositories/DatabaseReviewRepository.php
  Repositories/CacheReviewRepository.php
  Repositories/ReviewRepositoryFactory.php
  Models/GoogleReviewPlace.php
  Models/GoogleReview.php
  Console/SyncReviewsCommand.php
  Filament/GoogleReviewsPlugin.php
  Filament/Pages/GoogleReviewsSettings.php
database/migrations/
  create_google_review_places_table.php.stub
  create_google_reviews_table.php.stub
  create_google_review_settings_table.php.stub
resources/views/components/google-reviews.blade.php
tests/
  Pest.php
  TestCase.php
  Unit/GooglePlacesClientTest.php
  Unit/SettingsResolverTest.php
  Unit/ReviewRepositoryFactoryTest.php
  Feature/DatabaseReviewRepositoryTest.php
  Feature/CacheReviewRepositoryTest.php
  Feature/SyncReviewsCommandTest.php
  Feature/BladeComponentTest.php
  Fixtures/place-details.json
```

---

### Task 1: Package scaffolding (composer.json + autoload + Testbench)

**Files:**
- Create: `composer.json`
- Create: `tests/TestCase.php`
- Create: `tests/Pest.php`
- Create: `phpunit.xml`

**Interfaces:**
- Consumes: nothing.
- Produces: `Madbox99\GoogleReviews\Tests\TestCase` (base test case booting the package provider via Testbench); PSR-4 autoload for `Madbox99\GoogleReviews\` → `src/` and `Madbox99\GoogleReviews\Tests\` → `tests/`. Provider class `Madbox99\GoogleReviews\GoogleReviewsServiceProvider` is referenced here and created in Task 8 — a placeholder empty provider is created in this task so the suite boots.

- [ ] **Step 1: Create `composer.json`**

```json
{
    "name": "cegem360/laravel-google-reviews",
    "description": "Fetch and display Google Places reviews in Laravel and Filament apps.",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.3",
        "illuminate/support": "^12.0",
        "illuminate/http": "^12.0",
        "illuminate/database": "^12.0",
        "illuminate/console": "^12.0"
    },
    "require-dev": {
        "orchestra/testbench": "^10.0",
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0",
        "filament/filament": "^4.0"
    },
    "suggest": {
        "filament/filament": "Adds an admin settings page (^4.0)."
    },
    "autoload": {
        "psr-4": {
            "Madbox99\\GoogleReviews\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Madbox99\\GoogleReviews\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Madbox99\\GoogleReviews\\GoogleReviewsServiceProvider"
            ],
            "aliases": {
                "GoogleReviews": "Madbox99\\GoogleReviews\\Facades\\GoogleReviews"
            }
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "config": {
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    }
}
```

- [ ] **Step 2: Create a placeholder service provider so the suite can boot**

Create `src/GoogleReviewsServiceProvider.php`:

```php
<?php

namespace Madbox99\GoogleReviews;

use Illuminate\Support\ServiceProvider;

class GoogleReviewsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
```

- [ ] **Step 3: Create `tests/TestCase.php`**

```php
<?php

namespace Madbox99\GoogleReviews\Tests;

use Madbox99\GoogleReviews\GoogleReviewsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [GoogleReviewsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
```

- [ ] **Step 4: Create `tests/Pest.php`**

```php
<?php

use Madbox99\GoogleReviews\Tests\TestCase;

uses(TestCase::class)->in('Unit', 'Feature');
```

- [ ] **Step 5: Create `phpunit.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

- [ ] **Step 6: Install dependencies and verify the empty suite runs**

Run: `composer install && ./vendor/bin/pest`
Expected: PASS — "No tests executed" or 0 tests, no autoload/boot errors.

- [ ] **Step 7: Commit**

```bash
git add composer.json phpunit.xml src/GoogleReviewsServiceProvider.php tests/
git commit -m "chore: scaffold package with composer, autoload, and testbench"
```

---

### Task 2: DTOs (`ReviewData`, `PlaceData`)

**Files:**
- Create: `src/Data/ReviewData.php`
- Create: `src/Data/PlaceData.php`
- Test: `tests/Unit/GooglePlacesClientTest.php` covers these indirectly; add a small direct test file `tests/Unit/DataTest.php`.

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `ReviewData` readonly with constructor `(string $googleReviewId, string $authorName, ?string $authorPhotoUrl, int $rating, string $text, ?string $language, ?\DateTimeImmutable $publishedAt)`, plus `toArray(): array` and static `fromArray(array $data): self`.
  - `PlaceData` readonly with constructor `(string $placeId, ?string $name, ?float $rating, ?int $userRatingsTotal, array $reviews /* ReviewData[] */)`, plus `toArray(): array` and static `fromArray(array $data): self`.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/DataTest.php`:

```php
<?php

use Madbox99\GoogleReviews\Data\PlaceData;
use Madbox99\GoogleReviews\Data\ReviewData;

it('round-trips review data through array', function () {
    $review = new ReviewData(
        googleReviewId: 'abc',
        authorName: 'Jane',
        authorPhotoUrl: 'https://x/y.jpg',
        rating: 5,
        text: 'Great',
        language: 'en',
        publishedAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
    );

    $restored = ReviewData::fromArray($review->toArray());

    expect($restored->authorName)->toBe('Jane')
        ->and($restored->rating)->toBe(5)
        ->and($restored->publishedAt?->format('c'))->toBe('2026-01-01T10:00:00+00:00');
});

it('round-trips place data with nested reviews', function () {
    $place = new PlaceData(
        placeId: 'place-1',
        name: 'Acme',
        rating: 4.5,
        userRatingsTotal: 120,
        reviews: [new ReviewData('r1', 'Bob', null, 4, 'ok', 'hu', null)],
    );

    $restored = PlaceData::fromArray($place->toArray());

    expect($restored->rating)->toBe(4.5)
        ->and($restored->reviews)->toHaveCount(1)
        ->and($restored->reviews[0])->toBeInstanceOf(ReviewData::class)
        ->and($restored->reviews[0]->authorName)->toBe('Bob');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Unit/DataTest.php`
Expected: FAIL — class `ReviewData` not found.

- [ ] **Step 3: Implement `ReviewData`**

Create `src/Data/ReviewData.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Data;

use DateTimeImmutable;

final readonly class ReviewData
{
    public function __construct(
        public string $googleReviewId,
        public string $authorName,
        public ?string $authorPhotoUrl,
        public int $rating,
        public string $text,
        public ?string $language,
        public ?DateTimeImmutable $publishedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'google_review_id' => $this->googleReviewId,
            'author_name' => $this->authorName,
            'author_photo_url' => $this->authorPhotoUrl,
            'rating' => $this->rating,
            'text' => $this->text,
            'language' => $this->language,
            'published_at' => $this->publishedAt?->format(DATE_ATOM),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            googleReviewId: (string) $data['google_review_id'],
            authorName: (string) $data['author_name'],
            authorPhotoUrl: $data['author_photo_url'] ?? null,
            rating: (int) $data['rating'],
            text: (string) ($data['text'] ?? ''),
            language: $data['language'] ?? null,
            publishedAt: isset($data['published_at']) && $data['published_at']
                ? new DateTimeImmutable($data['published_at'])
                : null,
        );
    }
}
```

- [ ] **Step 4: Implement `PlaceData`**

Create `src/Data/PlaceData.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Data;

final readonly class PlaceData
{
    /** @param ReviewData[] $reviews */
    public function __construct(
        public string $placeId,
        public ?string $name,
        public ?float $rating,
        public ?int $userRatingsTotal,
        public array $reviews,
    ) {}

    public function toArray(): array
    {
        return [
            'place_id' => $this->placeId,
            'name' => $this->name,
            'rating' => $this->rating,
            'user_ratings_total' => $this->userRatingsTotal,
            'reviews' => array_map(fn (ReviewData $r) => $r->toArray(), $this->reviews),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            placeId: (string) $data['place_id'],
            name: $data['name'] ?? null,
            rating: isset($data['rating']) ? (float) $data['rating'] : null,
            userRatingsTotal: isset($data['user_ratings_total']) ? (int) $data['user_ratings_total'] : null,
            reviews: array_map(
                fn (array $r) => ReviewData::fromArray($r),
                $data['reviews'] ?? [],
            ),
        );
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Unit/DataTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add src/Data tests/Unit/DataTest.php
git commit -m "feat: add ReviewData and PlaceData DTOs"
```

---

### Task 3: Config file + `SettingsResolver`

**Files:**
- Create: `config/google-reviews.php`
- Create: `src/Settings/SettingsResolver.php`
- Create: `database/migrations/create_google_review_settings_table.php.stub`
- Test: `tests/Unit/SettingsResolverTest.php`

**Interfaces:**
- Consumes: Laravel `config`, DB connection.
- Produces: `SettingsResolver` with methods:
  - `get(string $key, mixed $default = null): mixed` — resolves **DB → config → default**, except `api_key` which skips DB.
  - `apiKey(): ?string` — config/env only.
  - `placeId(): string|array|null`
  - `driver(): string` (`database`|`cache`)
  - `cacheTtl(): int`
  - `language(): string`
  Settings persist in table `google_review_settings` (`key` string unique, `value` text nullable). DB lookup is wrapped in a try/catch so a missing table (Filament not migrated) falls back to config silently.

- [ ] **Step 1: Create the config file**

Create `config/google-reviews.php`:

```php
<?php

return [
    'api_key' => env('GOOGLE_PLACES_API_KEY'),
    'place_id' => env('GOOGLE_PLACES_PLACE_ID'),
    'driver' => env('GOOGLE_REVIEWS_DRIVER', 'database'),
    'cache_ttl' => (int) env('GOOGLE_REVIEWS_CACHE_TTL', 86400),
    'language' => env('GOOGLE_REVIEWS_LANGUAGE', 'hu'),
    'settings_table' => 'google_review_settings',
];
```

- [ ] **Step 2: Create the settings migration stub**

Create `database/migrations/create_google_review_settings_table.php.stub`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_review_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_review_settings');
    }
};
```

- [ ] **Step 3: Write the failing test**

Create `tests/Unit/SettingsResolverTest.php`:

```php
<?php

use Madbox99\GoogleReviews\Settings\SettingsResolver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config()->set('google-reviews.api_key', 'env-key');
    config()->set('google-reviews.driver', 'database');
    config()->set('google-reviews.place_id', 'config-place');
    config()->set('google-reviews.settings_table', 'google_review_settings');
});

it('falls back to config when no settings table exists', function () {
    $resolver = new SettingsResolver();

    expect($resolver->driver())->toBe('database')
        ->and($resolver->placeId())->toBe('config-place')
        ->and($resolver->apiKey())->toBe('env-key');
});

it('lets DB settings override config, except api_key', function () {
    Schema::create('google_review_settings', function ($table) {
        $table->id();
        $table->string('key')->unique();
        $table->text('value')->nullable();
        $table->timestamps();
    });
    DB::table('google_review_settings')->insert([
        ['key' => 'driver', 'value' => 'cache'],
        ['key' => 'place_id', 'value' => 'db-place'],
        ['key' => 'api_key', 'value' => 'db-key-should-be-ignored'],
    ]);

    $resolver = new SettingsResolver();

    expect($resolver->driver())->toBe('cache')
        ->and($resolver->placeId())->toBe('db-place')
        ->and($resolver->apiKey())->toBe('env-key');
});
```

- [ ] **Step 4: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Unit/SettingsResolverTest.php`
Expected: FAIL — class `SettingsResolver` not found.

- [ ] **Step 5: Implement `SettingsResolver`**

Create `src/Settings/SettingsResolver.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Settings;

use Illuminate\Support\Facades\DB;
use Throwable;

class SettingsResolver
{
    /** @var array<string,string|null>|null */
    private ?array $dbCache = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $db = $this->fromDatabase($key);
        if ($db !== null) {
            return $db;
        }

        return config("google-reviews.$key", $default);
    }

    public function apiKey(): ?string
    {
        // API key never comes from the DB.
        return config('google-reviews.api_key');
    }

    public function placeId(): string|array|null
    {
        return $this->get('place_id');
    }

    public function driver(): string
    {
        return (string) $this->get('driver', 'database');
    }

    public function cacheTtl(): int
    {
        return (int) $this->get('cache_ttl', 86400);
    }

    public function language(): string
    {
        return (string) $this->get('language', 'hu');
    }

    private function fromDatabase(string $key): ?string
    {
        if ($key === 'api_key') {
            return null;
        }

        if ($this->dbCache === null) {
            $this->dbCache = $this->loadDatabaseSettings();
        }

        return $this->dbCache[$key] ?? null;
    }

    /** @return array<string,string|null> */
    private function loadDatabaseSettings(): array
    {
        $table = config('google-reviews.settings_table', 'google_review_settings');

        try {
            return DB::table($table)->pluck('value', 'key')->all();
        } catch (Throwable) {
            return [];
        }
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Unit/SettingsResolverTest.php`
Expected: PASS (2 tests).

- [ ] **Step 7: Commit**

```bash
git add config src/Settings database/migrations/create_google_review_settings_table.php.stub tests/Unit/SettingsResolverTest.php
git commit -m "feat: add config file and SettingsResolver with DB->config->env precedence"
```

---

### Task 4: `GooglePlacesClient`

**Files:**
- Create: `src/Http/GooglePlacesClient.php`
- Create: `tests/Fixtures/place-details.json`
- Test: `tests/Unit/GooglePlacesClientTest.php`

**Interfaces:**
- Consumes: `SettingsResolver` (Task 3), Laravel `Http`, `PlaceData`/`ReviewData` (Task 2).
- Produces: `GooglePlacesClient` constructed with `(SettingsResolver $settings)`, method `fetch(string $placeId): PlaceData`. Calls Places API (New): `GET https://places.googleapis.com/v1/places/{placeId}` with header `X-Goog-Api-Key` and `X-Goog-FieldMask: id,displayName,rating,userRatingCount,reviews`. Throws `\RuntimeException` when the api key is missing or the HTTP call fails.

- [ ] **Step 1: Create the fixture**

Create `tests/Fixtures/place-details.json` (shape mirrors Places API New):

```json
{
    "id": "ChIJ-place-123",
    "displayName": { "text": "Acme Kft.", "languageCode": "hu" },
    "rating": 4.6,
    "userRatingCount": 231,
    "reviews": [
        {
            "name": "places/ChIJ-place-123/reviews/rev-1",
            "rating": 5,
            "text": { "text": "Kiváló szolgáltatás!", "languageCode": "hu" },
            "authorAttribution": {
                "displayName": "Kiss Anna",
                "photoUri": "https://example.com/anna.jpg"
            },
            "publishTime": "2026-05-01T09:30:00Z"
        },
        {
            "name": "places/ChIJ-place-123/reviews/rev-2",
            "rating": 4,
            "text": { "text": "Jó volt.", "languageCode": "hu" },
            "authorAttribution": {
                "displayName": "Nagy Béla",
                "photoUri": null
            },
            "publishTime": "2026-04-15T18:00:00Z"
        }
    ]
}
```

- [ ] **Step 2: Write the failing test**

Create `tests/Unit/GooglePlacesClientTest.php`:

```php
<?php

use Madbox99\GoogleReviews\Http\GooglePlacesClient;
use Madbox99\GoogleReviews\Settings\SettingsResolver;
use Illuminate\Support\Facades\Http;

it('maps the places api response into a PlaceData with reviews', function () {
    config()->set('google-reviews.api_key', 'test-key');

    $body = json_decode(file_get_contents(__DIR__.'/../Fixtures/place-details.json'), true);
    Http::fake([
        'places.googleapis.com/*' => Http::response($body, 200),
    ]);

    $client = new GooglePlacesClient(new SettingsResolver());
    $place = $client->fetch('ChIJ-place-123');

    expect($place->placeId)->toBe('ChIJ-place-123')
        ->and($place->name)->toBe('Acme Kft.')
        ->and($place->rating)->toBe(4.6)
        ->and($place->userRatingsTotal)->toBe(231)
        ->and($place->reviews)->toHaveCount(2)
        ->and($place->reviews[0]->authorName)->toBe('Kiss Anna')
        ->and($place->reviews[0]->authorPhotoUrl)->toBe('https://example.com/anna.jpg')
        ->and($place->reviews[0]->rating)->toBe(5)
        ->and($place->reviews[0]->text)->toBe('Kiváló szolgáltatás!')
        ->and($place->reviews[0]->publishedAt?->format('Y-m-d'))->toBe('2026-05-01');

    Http::assertSent(fn ($request) => $request->hasHeader('X-Goog-Api-Key', 'test-key'));
});

it('throws when the api key is missing', function () {
    config()->set('google-reviews.api_key', null);

    $client = new GooglePlacesClient(new SettingsResolver());

    $client->fetch('ChIJ-place-123');
})->throws(RuntimeException::class);
```

- [ ] **Step 3: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Unit/GooglePlacesClientTest.php`
Expected: FAIL — class `GooglePlacesClient` not found.

- [ ] **Step 4: Implement `GooglePlacesClient`**

Create `src/Http/GooglePlacesClient.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Http;

use Madbox99\GoogleReviews\Data\PlaceData;
use Madbox99\GoogleReviews\Data\ReviewData;
use Madbox99\GoogleReviews\Settings\SettingsResolver;
use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GooglePlacesClient
{
    private const BASE = 'https://places.googleapis.com/v1/places/';

    private const FIELD_MASK = 'id,displayName,rating,userRatingCount,reviews';

    public function __construct(private SettingsResolver $settings) {}

    public function fetch(string $placeId): PlaceData
    {
        $apiKey = $this->settings->apiKey();
        if (! $apiKey) {
            throw new RuntimeException('Google Places API key is not configured.');
        }

        $response = Http::withHeaders([
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => self::FIELD_MASK,
        ])->get(self::BASE.$placeId, [
            'languageCode' => $this->settings->language(),
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "Google Places request failed for {$placeId}: HTTP {$response->status()}"
            );
        }

        return $this->toPlaceData($placeId, $response->json());
    }

    private function toPlaceData(string $placeId, array $data): PlaceData
    {
        $reviews = array_map(
            fn (array $r) => $this->toReviewData($r),
            $data['reviews'] ?? [],
        );

        return new PlaceData(
            placeId: $data['id'] ?? $placeId,
            name: $data['displayName']['text'] ?? null,
            rating: isset($data['rating']) ? (float) $data['rating'] : null,
            userRatingsTotal: isset($data['userRatingCount']) ? (int) $data['userRatingCount'] : null,
            reviews: $reviews,
        );
    }

    private function toReviewData(array $r): ReviewData
    {
        return new ReviewData(
            googleReviewId: $r['name'] ?? '',
            authorName: $r['authorAttribution']['displayName'] ?? 'Anonymous',
            authorPhotoUrl: $r['authorAttribution']['photoUri'] ?? null,
            rating: (int) ($r['rating'] ?? 0),
            text: $r['text']['text'] ?? '',
            language: $r['text']['languageCode'] ?? null,
            publishedAt: isset($r['publishTime']) ? new DateTimeImmutable($r['publishTime']) : null,
        );
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Unit/GooglePlacesClientTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add src/Http tests/Unit/GooglePlacesClientTest.php tests/Fixtures/place-details.json
git commit -m "feat: add GooglePlacesClient mapping the Places API (New) response"
```

---

### Task 5: Eloquent models + migrations + `ReviewRepository` contract + Database repository

**Files:**
- Create: `src/Contracts/ReviewRepository.php`
- Create: `src/Models/GoogleReviewPlace.php`
- Create: `src/Models/GoogleReview.php`
- Create: `database/migrations/create_google_review_places_table.php.stub`
- Create: `database/migrations/create_google_reviews_table.php.stub`
- Create: `src/Repositories/DatabaseReviewRepository.php`
- Test: `tests/Feature/DatabaseReviewRepositoryTest.php`

**Interfaces:**
- Consumes: `PlaceData`/`ReviewData` (Task 2).
- Produces:
  - Interface `ReviewRepository` with `store(PlaceData $place): void` and `get(string $placeId): ?PlaceData`.
  - `DatabaseReviewRepository implements ReviewRepository` — upserts the place row and its reviews; `get()` returns a `PlaceData` built from DB rows, **excluding reviews where `visible = false`**.
  - Models `GoogleReviewPlace` (table `google_review_places`) and `GoogleReview` (table `google_reviews`).

- [ ] **Step 1: Create the migrations**

Create `database/migrations/create_google_review_places_table.php.stub`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_review_places', function (Blueprint $table) {
            $table->id();
            $table->string('place_id')->unique();
            $table->string('name')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('user_ratings_total')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_review_places');
    }
};
```

Create `database/migrations/create_google_reviews_table.php.stub`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('place_id')->index();
            $table->string('google_review_id');
            $table->string('author_name');
            $table->string('author_photo_url')->nullable();
            $table->unsignedTinyInteger('rating')->default(0);
            $table->text('text')->nullable();
            $table->string('language')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('visible')->default(true);
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['place_id', 'google_review_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_reviews');
    }
};
```

- [ ] **Step 2: Create the contract**

Create `src/Contracts/ReviewRepository.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Contracts;

use Madbox99\GoogleReviews\Data\PlaceData;

interface ReviewRepository
{
    public function store(PlaceData $place): void;

    public function get(string $placeId): ?PlaceData;
}
```

- [ ] **Step 3: Create the models**

Create `src/Models/GoogleReviewPlace.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleReviewPlace extends Model
{
    protected $table = 'google_review_places';

    protected $guarded = [];

    protected $casts = [
        'rating' => 'float',
        'user_ratings_total' => 'integer',
        'synced_at' => 'datetime',
    ];
}
```

Create `src/Models/GoogleReview.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleReview extends Model
{
    protected $table = 'google_reviews';

    protected $guarded = [];

    protected $casts = [
        'rating' => 'integer',
        'visible' => 'boolean',
        'published_at' => 'datetime',
        'raw' => 'array',
    ];
}
```

- [ ] **Step 4: Write the failing test**

Create `tests/Feature/DatabaseReviewRepositoryTest.php`:

```php
<?php

use Madbox99\GoogleReviews\Data\PlaceData;
use Madbox99\GoogleReviews\Data\ReviewData;
use Madbox99\GoogleReviews\Models\GoogleReview;
use Madbox99\GoogleReviews\Repositories\DatabaseReviewRepository;

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
});

function samplePlace(): PlaceData
{
    return new PlaceData(
        placeId: 'place-1',
        name: 'Acme',
        rating: 4.5,
        userRatingsTotal: 100,
        reviews: [
            new ReviewData('r1', 'Anna', null, 5, 'Great', 'hu', new DateTimeImmutable('2026-01-01T00:00:00Z')),
            new ReviewData('r2', 'Bela', null, 4, 'Good', 'hu', null),
        ],
    );
}

it('stores and reads a place round-trip', function () {
    $repo = new DatabaseReviewRepository();
    $repo->store(samplePlace());

    $place = $repo->get('place-1');

    expect($place)->not->toBeNull()
        ->and($place->name)->toBe('Acme')
        ->and($place->rating)->toBe(4.5)
        ->and($place->reviews)->toHaveCount(2);
});

it('upserts instead of duplicating on repeated store', function () {
    $repo = new DatabaseReviewRepository();
    $repo->store(samplePlace());
    $repo->store(samplePlace());

    expect(GoogleReview::where('place_id', 'place-1')->count())->toBe(2);
});

it('hides reviews flagged not visible', function () {
    $repo = new DatabaseReviewRepository();
    $repo->store(samplePlace());
    GoogleReview::where('google_review_id', 'r2')->update(['visible' => false]);

    $place = $repo->get('place-1');

    expect($place->reviews)->toHaveCount(1)
        ->and($place->reviews[0]->googleReviewId)->toBe('r1');
});

it('returns null for an unknown place', function () {
    expect((new DatabaseReviewRepository())->get('nope'))->toBeNull();
});
```

- [ ] **Step 5: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/DatabaseReviewRepositoryTest.php`
Expected: FAIL — class `DatabaseReviewRepository` not found.

- [ ] **Step 6: Implement `DatabaseReviewRepository`**

Create `src/Repositories/DatabaseReviewRepository.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Repositories;

use Madbox99\GoogleReviews\Contracts\ReviewRepository;
use Madbox99\GoogleReviews\Data\PlaceData;
use Madbox99\GoogleReviews\Data\ReviewData;
use Madbox99\GoogleReviews\Models\GoogleReview;
use Madbox99\GoogleReviews\Models\GoogleReviewPlace;
use DateTimeImmutable;

class DatabaseReviewRepository implements ReviewRepository
{
    public function store(PlaceData $place): void
    {
        GoogleReviewPlace::updateOrCreate(
            ['place_id' => $place->placeId],
            [
                'name' => $place->name,
                'rating' => $place->rating,
                'user_ratings_total' => $place->userRatingsTotal,
                'synced_at' => now(),
            ],
        );

        foreach ($place->reviews as $review) {
            GoogleReview::updateOrCreate(
                ['place_id' => $place->placeId, 'google_review_id' => $review->googleReviewId],
                [
                    'author_name' => $review->authorName,
                    'author_photo_url' => $review->authorPhotoUrl,
                    'rating' => $review->rating,
                    'text' => $review->text,
                    'language' => $review->language,
                    'published_at' => $review->publishedAt,
                    'raw' => $review->toArray(),
                ],
            );
        }
    }

    public function get(string $placeId): ?PlaceData
    {
        $placeModel = GoogleReviewPlace::where('place_id', $placeId)->first();
        if (! $placeModel) {
            return null;
        }

        $reviews = GoogleReview::where('place_id', $placeId)
            ->where('visible', true)
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (GoogleReview $r) => new ReviewData(
                googleReviewId: $r->google_review_id,
                authorName: $r->author_name,
                authorPhotoUrl: $r->author_photo_url,
                rating: (int) $r->rating,
                text: (string) $r->text,
                language: $r->language,
                publishedAt: $r->published_at
                    ? new DateTimeImmutable($r->published_at->toIso8601String())
                    : null,
            ))
            ->all();

        return new PlaceData(
            placeId: $placeModel->place_id,
            name: $placeModel->name,
            rating: $placeModel->rating,
            userRatingsTotal: $placeModel->user_ratings_total,
            reviews: $reviews,
        );
    }
}
```

- [ ] **Step 7: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/DatabaseReviewRepositoryTest.php`
Expected: PASS (4 tests).

- [ ] **Step 8: Commit**

```bash
git add src/Contracts src/Models src/Repositories/DatabaseReviewRepository.php database/migrations/create_google_review_places_table.php.stub database/migrations/create_google_reviews_table.php.stub tests/Feature/DatabaseReviewRepositoryTest.php
git commit -m "feat: add models, migrations, ReviewRepository contract and database driver"
```

---

### Task 6: `CacheReviewRepository` + `ReviewRepositoryFactory`

**Files:**
- Create: `src/Repositories/CacheReviewRepository.php`
- Create: `src/Repositories/ReviewRepositoryFactory.php`
- Test: `tests/Feature/CacheReviewRepositoryTest.php`
- Test: `tests/Unit/ReviewRepositoryFactoryTest.php`

**Interfaces:**
- Consumes: `ReviewRepository` (Task 5), `PlaceData` (Task 2), `SettingsResolver` (Task 3), Laravel `Cache`.
- Produces:
  - `CacheReviewRepository implements ReviewRepository` constructed with `(int $ttl = 86400)`; stores `PlaceData->toArray()` under key `google-reviews:{placeId}`, rebuilds with `PlaceData::fromArray()`.
  - `ReviewRepositoryFactory` constructed with `(SettingsResolver $settings)`, method `make(): ReviewRepository` returning `DatabaseReviewRepository` for `driver=database`, `CacheReviewRepository` (with resolved ttl) for `driver=cache`.

- [ ] **Step 1: Write the failing cache test**

Create `tests/Feature/CacheReviewRepositoryTest.php`:

```php
<?php

use Madbox99\GoogleReviews\Data\PlaceData;
use Madbox99\GoogleReviews\Data\ReviewData;
use Madbox99\GoogleReviews\Repositories\CacheReviewRepository;

it('stores and reads a place through the cache', function () {
    $place = new PlaceData('place-1', 'Acme', 4.2, 50, [
        new ReviewData('r1', 'Anna', null, 5, 'Nice', 'hu', null),
    ]);

    $repo = new CacheReviewRepository(ttl: 3600);
    $repo->store($place);

    $restored = $repo->get('place-1');

    expect($restored)->not->toBeNull()
        ->and($restored->name)->toBe('Acme')
        ->and($restored->reviews)->toHaveCount(1)
        ->and($restored->reviews[0]->authorName)->toBe('Anna');
});

it('returns null when nothing is cached', function () {
    expect((new CacheReviewRepository())->get('missing'))->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/CacheReviewRepositoryTest.php`
Expected: FAIL — class `CacheReviewRepository` not found.

- [ ] **Step 3: Implement `CacheReviewRepository`**

Create `src/Repositories/CacheReviewRepository.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Repositories;

use Madbox99\GoogleReviews\Contracts\ReviewRepository;
use Madbox99\GoogleReviews\Data\PlaceData;
use Illuminate\Support\Facades\Cache;

class CacheReviewRepository implements ReviewRepository
{
    public function __construct(private int $ttl = 86400) {}

    public function store(PlaceData $place): void
    {
        Cache::put($this->key($place->placeId), $place->toArray(), $this->ttl);
    }

    public function get(string $placeId): ?PlaceData
    {
        $data = Cache::get($this->key($placeId));

        return $data ? PlaceData::fromArray($data) : null;
    }

    private function key(string $placeId): string
    {
        return "google-reviews:{$placeId}";
    }
}
```

- [ ] **Step 4: Run cache test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/CacheReviewRepositoryTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Write the failing factory test**

Create `tests/Unit/ReviewRepositoryFactoryTest.php`:

```php
<?php

use Madbox99\GoogleReviews\Repositories\CacheReviewRepository;
use Madbox99\GoogleReviews\Repositories\DatabaseReviewRepository;
use Madbox99\GoogleReviews\Repositories\ReviewRepositoryFactory;
use Madbox99\GoogleReviews\Settings\SettingsResolver;

it('builds the database repository by default', function () {
    config()->set('google-reviews.driver', 'database');

    $repo = (new ReviewRepositoryFactory(new SettingsResolver()))->make();

    expect($repo)->toBeInstanceOf(DatabaseReviewRepository::class);
});

it('builds the cache repository when configured', function () {
    config()->set('google-reviews.driver', 'cache');

    $repo = (new ReviewRepositoryFactory(new SettingsResolver()))->make();

    expect($repo)->toBeInstanceOf(CacheReviewRepository::class);
});
```

- [ ] **Step 6: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Unit/ReviewRepositoryFactoryTest.php`
Expected: FAIL — class `ReviewRepositoryFactory` not found.

- [ ] **Step 7: Implement `ReviewRepositoryFactory`**

Create `src/Repositories/ReviewRepositoryFactory.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Repositories;

use Madbox99\GoogleReviews\Contracts\ReviewRepository;
use Madbox99\GoogleReviews\Settings\SettingsResolver;

class ReviewRepositoryFactory
{
    public function __construct(private SettingsResolver $settings) {}

    public function make(): ReviewRepository
    {
        return match ($this->settings->driver()) {
            'cache' => new CacheReviewRepository($this->settings->cacheTtl()),
            default => new DatabaseReviewRepository(),
        };
    }
}
```

- [ ] **Step 8: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Unit/ReviewRepositoryFactoryTest.php`
Expected: PASS (2 tests).

- [ ] **Step 9: Commit**

```bash
git add src/Repositories/CacheReviewRepository.php src/Repositories/ReviewRepositoryFactory.php tests/Feature/CacheReviewRepositoryTest.php tests/Unit/ReviewRepositoryFactoryTest.php
git commit -m "feat: add cache driver and repository factory"
```

---

### Task 7: `GoogleReviewsManager` + facade

**Files:**
- Create: `src/GoogleReviewsManager.php`
- Create: `src/Facades/GoogleReviews.php`
- Test: `tests/Feature/GoogleReviewsManagerTest.php`

**Interfaces:**
- Consumes: `GooglePlacesClient` (Task 4), `ReviewRepositoryFactory` (Task 6), `SettingsResolver` (Task 3), `PlaceData` (Task 2).
- Produces: `GoogleReviewsManager` constructed with `(GooglePlacesClient $client, ReviewRepositoryFactory $factory, SettingsResolver $settings)`:
  - `refresh(?string $placeId = null): PlaceData` — resolves place id (arg → settings; if settings returns an array, uses the first), fetches via client, stores via active repo, returns the `PlaceData`.
  - `for(?string $placeId = null): ?PlaceData` — reads from active repo; returns null if absent.
  - `resolvePlaceId(?string $placeId): string` — arg wins, else first of settings `place_id`, throws `RuntimeException` if none.
  - Facade `GoogleReviews` with accessor returning the manager binding `Madbox99\GoogleReviews\GoogleReviewsManager`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/GoogleReviewsManagerTest.php`:

```php
<?php

use Madbox99\GoogleReviews\GoogleReviewsManager;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    config()->set('google-reviews.api_key', 'test-key');
    config()->set('google-reviews.driver', 'database');
    config()->set('google-reviews.place_id', 'ChIJ-place-123');

    $body = json_decode(file_get_contents(__DIR__.'/../Fixtures/place-details.json'), true);
    Http::fake(['places.googleapis.com/*' => Http::response($body, 200)]);
});

it('refreshes from the api and stores, then reads back', function () {
    $manager = app(GoogleReviewsManager::class);

    $fetched = $manager->refresh();
    expect($fetched->reviews)->toHaveCount(2);

    $stored = $manager->for();
    expect($stored)->not->toBeNull()
        ->and($stored->name)->toBe('Acme Kft.')
        ->and($stored->reviews)->toHaveCount(2);
});

it('resolves place id from an explicit argument over settings', function () {
    $manager = app(GoogleReviewsManager::class);

    $fetched = $manager->refresh('ChIJ-place-123');
    expect($fetched->placeId)->toBe('ChIJ-place-123');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/GoogleReviewsManagerTest.php`
Expected: FAIL — target class `GoogleReviewsManager` not resolvable / not found.

(Note: DI binding is wired in Task 8; this test uses `app(...)`. If it errors on resolution before Task 8, run it again after Task 8. To keep this task self-contained, the manager is constructed manually in the assertion below.)

Replace the `app(GoogleReviewsManager::class)` calls with a manual construction so the task is independently runnable:

```php
use Madbox99\GoogleReviews\Http\GooglePlacesClient;
use Madbox99\GoogleReviews\Repositories\ReviewRepositoryFactory;
use Madbox99\GoogleReviews\Settings\SettingsResolver;

function makeManager(): GoogleReviewsManager
{
    $settings = new SettingsResolver();

    return new GoogleReviewsManager(
        new GooglePlacesClient($settings),
        new ReviewRepositoryFactory($settings),
        $settings,
    );
}
```

Then use `makeManager()` instead of `app(GoogleReviewsManager::class)` in both tests.

- [ ] **Step 3: Implement `GoogleReviewsManager`**

Create `src/GoogleReviewsManager.php`:

```php
<?php

namespace Madbox99\GoogleReviews;

use Madbox99\GoogleReviews\Data\PlaceData;
use Madbox99\GoogleReviews\Http\GooglePlacesClient;
use Madbox99\GoogleReviews\Repositories\ReviewRepositoryFactory;
use Madbox99\GoogleReviews\Settings\SettingsResolver;
use RuntimeException;

class GoogleReviewsManager
{
    public function __construct(
        private GooglePlacesClient $client,
        private ReviewRepositoryFactory $factory,
        private SettingsResolver $settings,
    ) {}

    public function refresh(?string $placeId = null): PlaceData
    {
        $id = $this->resolvePlaceId($placeId);
        $place = $this->client->fetch($id);
        $this->factory->make()->store($place);

        return $place;
    }

    public function for(?string $placeId = null): ?PlaceData
    {
        return $this->factory->make()->get($this->resolvePlaceId($placeId));
    }

    public function resolvePlaceId(?string $placeId): string
    {
        if ($placeId) {
            return $placeId;
        }

        $configured = $this->settings->placeId();
        if (is_array($configured)) {
            $configured = $configured[0] ?? null;
        }

        if (! $configured) {
            throw new RuntimeException('No Google place id configured.');
        }

        return (string) $configured;
    }
}
```

- [ ] **Step 4: Create the facade**

Create `src/Facades/GoogleReviews.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Facades;

use Madbox99\GoogleReviews\GoogleReviewsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Madbox99\GoogleReviews\Data\PlaceData refresh(?string $placeId = null)
 * @method static \Madbox99\GoogleReviews\Data\PlaceData|null for(?string $placeId = null)
 *
 * @see \Madbox99\GoogleReviews\GoogleReviewsManager
 */
class GoogleReviews extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GoogleReviewsManager::class;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/GoogleReviewsManagerTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add src/GoogleReviewsManager.php src/Facades/GoogleReviews.php tests/Feature/GoogleReviewsManagerTest.php
git commit -m "feat: add GoogleReviewsManager and GoogleReviews facade"
```

---

### Task 8: Service provider (bindings, config merge, publishing, views, command registration)

**Files:**
- Modify: `src/GoogleReviewsServiceProvider.php` (replace the placeholder from Task 1)
- Test: `tests/Feature/ServiceProviderTest.php`

**Interfaces:**
- Consumes: everything above.
- Produces: a fully wired provider that:
  - merges `config/google-reviews.php` under key `google-reviews`,
  - binds `SettingsResolver`, `GooglePlacesClient`, `ReviewRepositoryFactory`, and `GoogleReviewsManager` as singletons,
  - loads views under namespace `google-reviews` from `resources/views`,
  - registers the Blade component alias `google-reviews` (class-less, anonymous component) — done in Task 9; here only the view namespace + component path are registered,
  - registers `SyncReviewsCommand` (Task 10) when running in console — this reference is added in Task 10; in this task the command array is empty and a comment marks where it goes,
  - publishes config (tag `google-reviews-config`), migrations (tag `google-reviews-migrations`), and views (tag `google-reviews-views`).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ServiceProviderTest.php`:

```php
<?php

use Madbox99\GoogleReviews\GoogleReviewsManager;
use Madbox99\GoogleReviews\Settings\SettingsResolver;

it('binds the manager and settings resolver as singletons', function () {
    expect(app(GoogleReviewsManager::class))->toBeInstanceOf(GoogleReviewsManager::class)
        ->and(app(SettingsResolver::class))->toBe(app(SettingsResolver::class));
});

it('merges the package config', function () {
    expect(config('google-reviews.driver'))->not->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/ServiceProviderTest.php`
Expected: FAIL — `GoogleReviewsManager` not bound (BindingResolutionException) since the placeholder provider has empty register().

- [ ] **Step 3: Implement the provider**

Replace `src/GoogleReviewsServiceProvider.php` entirely:

```php
<?php

namespace Madbox99\GoogleReviews;

use Madbox99\GoogleReviews\Http\GooglePlacesClient;
use Madbox99\GoogleReviews\Repositories\ReviewRepositoryFactory;
use Madbox99\GoogleReviews\Settings\SettingsResolver;
use Illuminate\Support\ServiceProvider;

class GoogleReviewsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/google-reviews.php', 'google-reviews');

        $this->app->singleton(SettingsResolver::class);
        $this->app->singleton(GooglePlacesClient::class);
        $this->app->singleton(ReviewRepositoryFactory::class);
        $this->app->singleton(GoogleReviewsManager::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'google-reviews');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Madbox99\GoogleReviews\Console\SyncReviewsCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/google-reviews.php' => config_path('google-reviews.php'),
            ], 'google-reviews-config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_google_review_places_table.php.stub'
                    => $this->migrationPath('create_google_review_places_table.php'),
                __DIR__.'/../database/migrations/create_google_reviews_table.php.stub'
                    => $this->migrationPath('create_google_reviews_table.php'),
                __DIR__.'/../database/migrations/create_google_review_settings_table.php.stub'
                    => $this->migrationPath('create_google_review_settings_table.php'),
            ], 'google-reviews-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/google-reviews'),
            ], 'google-reviews-views');
        }
    }

    private function migrationPath(string $name): string
    {
        return database_path('migrations/'.date('Y_m_d_His').'_'.$name);
    }
}
```

Note: `SyncReviewsCommand` is created in Task 10. If running this task in isolation before Task 10, temporarily comment out the `$this->commands([...])` block, then restore it in Task 10.

- [ ] **Step 4: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/ServiceProviderTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Run the full suite**

Run: `./vendor/bin/pest`
Expected: PASS — all tests so far green.

- [ ] **Step 6: Commit**

```bash
git add src/GoogleReviewsServiceProvider.php tests/Feature/ServiceProviderTest.php
git commit -m "feat: wire service provider bindings, config, views and publishing"
```

---

### Task 9: Blade component view

**Files:**
- Create: `resources/views/components/google-reviews.blade.php`
- Modify: `src/GoogleReviewsServiceProvider.php:boot()` — register the anonymous component path.
- Test: `tests/Feature/BladeComponentTest.php`

**Interfaces:**
- Consumes: `GoogleReviewsManager` (`for()`), `PlaceData`.
- Produces: an anonymous Blade component usable as `<x-google-reviews />` (optional `:place-id="..."` and `:limit="..."` props). Renders place name, average rating, and each visible review with stars, author, and text. Registered via `Blade::anonymousComponentPath` / `loadViewComponentsAs`.

- [ ] **Step 1: Register the component namespace in the provider `boot()`**

Add to `boot()` in `src/GoogleReviewsServiceProvider.php`, after `loadViewsFrom`:

```php
$this->loadViewComponentsAs('google-reviews', []);
\Illuminate\Support\Facades\Blade::anonymousComponentPath(
    __DIR__.'/../resources/views/components',
    'google-reviews'
);
```

The component will be referenced as `<x-google-reviews::google-reviews />`. To also allow the short `<x-google-reviews />`, register an anonymous component alias:

```php
\Illuminate\Support\Facades\Blade::component('google-reviews::components.google-reviews', 'google-reviews');
```

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/BladeComponentTest.php`:

```php
<?php

use Madbox99\GoogleReviews\GoogleReviewsManager;
use Madbox99\GoogleReviews\Data\PlaceData;
use Madbox99\GoogleReviews\Data\ReviewData;

it('renders the reviews component with place data', function () {
    $place = new PlaceData('place-1', 'Acme Kft.', 4.5, 100, [
        new ReviewData('r1', 'Kiss Anna', null, 5, 'Kiváló!', 'hu', null),
    ]);

    $this->mock(GoogleReviewsManager::class, function ($mock) use ($place) {
        $mock->shouldReceive('for')->andReturn($place);
    });

    $html = view('google-reviews::components.google-reviews', ['placeId' => 'place-1'])->render();

    expect($html)->toContain('Acme Kft.')
        ->toContain('Kiss Anna')
        ->toContain('Kiváló!')
        ->toContain('4.5');
});

it('renders a graceful empty state when no place data exists', function () {
    $this->mock(GoogleReviewsManager::class, function ($mock) {
        $mock->shouldReceive('for')->andReturn(null);
    });

    $html = view('google-reviews::components.google-reviews', ['placeId' => 'missing'])->render();

    expect($html)->toContain('google-reviews');
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/BladeComponentTest.php`
Expected: FAIL — view `google-reviews::components.google-reviews` not found.

- [ ] **Step 4: Create the Blade view**

Create `resources/views/components/google-reviews.blade.php`:

```blade
@props(['placeId' => null, 'limit' => null])

@php
    $place = app(\Madbox99\GoogleReviews\GoogleReviewsManager::class)->for($placeId);
    $reviews = $place?->reviews ?? [];
    if ($limit) {
        $reviews = array_slice($reviews, 0, (int) $limit);
    }
@endphp

<div {{ $attributes->merge(['class' => 'google-reviews']) }}>
    @if ($place)
        <div class="google-reviews__header flex items-center gap-2 mb-4">
            <span class="google-reviews__name text-lg font-semibold">{{ $place->name }}</span>
            @if ($place->rating !== null)
                <span class="google-reviews__rating text-yellow-500 font-medium">
                    ★ {{ number_format($place->rating, 1) }}
                </span>
            @endif
            @if ($place->userRatingsTotal !== null)
                <span class="google-reviews__count text-sm text-gray-500">
                    ({{ $place->userRatingsTotal }})
                </span>
            @endif
        </div>

        <ul class="google-reviews__list space-y-4">
            @foreach ($reviews as $review)
                <li class="google-reviews__item border rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-2">
                        @if ($review->authorPhotoUrl)
                            <img src="{{ $review->authorPhotoUrl }}" alt="{{ $review->authorName }}"
                                 class="w-8 h-8 rounded-full" loading="lazy">
                        @endif
                        <span class="font-medium">{{ $review->authorName }}</span>
                        <span class="text-yellow-500" aria-label="{{ $review->rating }} / 5">
                            {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
                        </span>
                    </div>
                    <p class="google-reviews__text text-gray-700">{{ $review->text }}</p>
                </li>
            @endforeach
        </ul>
    @else
        <p class="google-reviews__empty text-sm text-gray-500">
            {{ __('No reviews available yet.') }}
        </p>
    @endif
</div>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/BladeComponentTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/google-reviews.blade.php src/GoogleReviewsServiceProvider.php tests/Feature/BladeComponentTest.php
git commit -m "feat: add publishable google-reviews Blade component"
```

---

### Task 10: `SyncReviewsCommand`

**Files:**
- Create: `src/Console/SyncReviewsCommand.php`
- Modify: `src/GoogleReviewsServiceProvider.php` — ensure the command is registered (from Task 8 it already is; if it was commented out, restore it now).
- Test: `tests/Feature/SyncReviewsCommandTest.php`

**Interfaces:**
- Consumes: `GoogleReviewsManager` (`refresh`), `SettingsResolver` (to iterate configured place ids).
- Produces: artisan command `google-reviews:sync {place? : optional place id}`. With no argument it syncs all configured place ids (settings `place_id` as string or array). Prints one line per synced place. Returns `Command::SUCCESS`; on a `RuntimeException` from a place, logs the error line and continues, returning `Command::FAILURE` if every place failed.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SyncReviewsCommandTest.php`:

```php
<?php

use Madbox99\GoogleReviews\Models\GoogleReview;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    config()->set('google-reviews.api_key', 'test-key');
    config()->set('google-reviews.driver', 'database');
    config()->set('google-reviews.place_id', 'ChIJ-place-123');

    $body = json_decode(file_get_contents(__DIR__.'/../Fixtures/place-details.json'), true);
    Http::fake(['places.googleapis.com/*' => Http::response($body, 200)]);
});

it('syncs configured place reviews into the database', function () {
    $this->artisan('google-reviews:sync')
        ->assertExitCode(0);

    expect(GoogleReview::where('place_id', 'ChIJ-place-123')->count())->toBe(2);
});

it('accepts an explicit place id argument', function () {
    $this->artisan('google-reviews:sync', ['place' => 'ChIJ-place-123'])
        ->assertExitCode(0);

    expect(GoogleReview::count())->toBe(2);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/SyncReviewsCommandTest.php`
Expected: FAIL — command `google-reviews:sync` not found.

- [ ] **Step 3: Implement the command**

Create `src/Console/SyncReviewsCommand.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Console;

use Madbox99\GoogleReviews\GoogleReviewsManager;
use Madbox99\GoogleReviews\Settings\SettingsResolver;
use Illuminate\Console\Command;
use RuntimeException;

class SyncReviewsCommand extends Command
{
    protected $signature = 'google-reviews:sync {place? : Optional Google place id to sync}';

    protected $description = 'Fetch and store Google reviews for the configured place(s).';

    public function handle(GoogleReviewsManager $manager, SettingsResolver $settings): int
    {
        $placeIds = $this->resolvePlaceIds($settings);

        if ($placeIds === []) {
            $this->error('No Google place id configured.');

            return self::FAILURE;
        }

        $succeeded = 0;

        foreach ($placeIds as $placeId) {
            try {
                $place = $manager->refresh($placeId);
                $this->info("Synced {$place->placeId}: ".count($place->reviews).' reviews.');
                $succeeded++;
            } catch (RuntimeException $e) {
                $this->error("Failed to sync {$placeId}: {$e->getMessage()}");
            }
        }

        return $succeeded > 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @return string[] */
    private function resolvePlaceIds(SettingsResolver $settings): array
    {
        $argument = $this->argument('place');
        if ($argument) {
            return [(string) $argument];
        }

        $configured = $settings->placeId();
        if (is_array($configured)) {
            return array_values(array_filter(array_map('strval', $configured)));
        }

        return $configured ? [(string) $configured] : [];
    }
}
```

- [ ] **Step 4: Ensure the command is registered**

Confirm `src/GoogleReviewsServiceProvider.php` `boot()` includes the command in the `$this->commands([...])` array (added in Task 8). If it was commented out, uncomment it now.

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/SyncReviewsCommandTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add src/Console/SyncReviewsCommand.php src/GoogleReviewsServiceProvider.php tests/Feature/SyncReviewsCommandTest.php
git commit -m "feat: add google-reviews:sync artisan command"
```

---

### Task 11: Filament plugin + settings page (optional integration)

**Files:**
- Create: `src/Filament/GoogleReviewsPlugin.php`
- Create: `src/Filament/Pages/GoogleReviewsSettings.php`
- Create: `resources/views/filament/pages/google-reviews-settings.blade.php`
- Test: `tests/Feature/FilamentPluginTest.php`

**Interfaces:**
- Consumes: `SettingsResolver`, `google_review_settings` table (Task 3), Filament 4 classes.
- Produces:
  - `GoogleReviewsPlugin implements \Filament\Contracts\Plugin` with `make()`, `getId(): string` (`'google-reviews'`), `register(Panel $panel): void` registering `GoogleReviewsSettings` page, and `boot(Panel $panel): void` (no-op).
  - `GoogleReviewsSettings extends \Filament\Pages\Page` — a form editing `place_id`, `driver` (select database/cache), `cache_ttl`, `language`; `mount()` loads current values via `SettingsResolver`; `save()` upserts rows into `google_review_settings`. API key field is displayed disabled with a hint that it lives in env.

Because Filament is a dev/optional dependency, this task's test is guarded to skip when Filament is not installed.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/FilamentPluginTest.php`:

```php
<?php

use Madbox99\GoogleReviews\Filament\GoogleReviewsPlugin;

it('exposes a stable plugin id', function () {
    if (! class_exists(\Filament\Contracts\Plugin::class)) {
        $this->markTestSkipped('Filament not installed.');
    }

    expect(GoogleReviewsPlugin::make()->getId())->toBe('google-reviews');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/FilamentPluginTest.php`
Expected: FAIL — class `GoogleReviewsPlugin` not found.

- [ ] **Step 3: Implement the plugin**

Create `src/Filament/GoogleReviewsPlugin.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Filament;

use Madbox99\GoogleReviews\Filament\Pages\GoogleReviewsSettings;
use Filament\Contracts\Plugin;
use Filament\Panel;

class GoogleReviewsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'google-reviews';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            GoogleReviewsSettings::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
```

- [ ] **Step 4: Implement the settings page**

Create `src/Filament/Pages/GoogleReviewsSettings.php`:

```php
<?php

namespace Madbox99\GoogleReviews\Filament\Pages;

use Madbox99\GoogleReviews\Settings\SettingsResolver;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class GoogleReviewsSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $title = 'Google Reviews';

    protected static string $view = 'google-reviews::filament.pages.google-reviews-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsResolver::class);

        $this->form->fill([
            'place_id' => is_array($settings->placeId())
                ? implode(',', $settings->placeId())
                : $settings->placeId(),
            'driver' => $settings->driver(),
            'cache_ttl' => $settings->cacheTtl(),
            'language' => $settings->language(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('place_id')
                    ->label('Google Place ID')
                    ->helperText('Comma-separate multiple place ids.')
                    ->required(),
                Select::make('driver')
                    ->options(['database' => 'Database', 'cache' => 'Cache'])
                    ->required(),
                TextInput::make('cache_ttl')
                    ->label('Cache TTL (seconds)')
                    ->numeric()
                    ->default(86400),
                TextInput::make('language')
                    ->maxLength(5)
                    ->default('hu'),
                TextInput::make('api_key_note')
                    ->label('API key')
                    ->disabled()
                    ->default('Configured via GOOGLE_PLACES_API_KEY in .env'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $table = config('google-reviews.settings_table', 'google_review_settings');

        foreach (['place_id', 'driver', 'cache_ttl', 'language'] as $key) {
            DB::table($table)->updateOrInsert(
                ['key' => $key],
                ['value' => (string) ($this->data[$key] ?? ''), 'updated_at' => now(), 'created_at' => now()],
            );
        }

        Notification::make()->title('Saved')->success()->send();
    }
}
```

- [ ] **Step 5: Create the page view**

Create `resources/views/filament/pages/google-reviews-settings.blade.php`:

```blade
<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Save
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
```

- [ ] **Step 6: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/FilamentPluginTest.php`
Expected: PASS (1 test) — Filament is in `require-dev`, so the class exists and id is asserted.

- [ ] **Step 7: Run the full suite**

Run: `./vendor/bin/pest`
Expected: PASS — all tests green.

- [ ] **Step 8: Commit**

```bash
git add src/Filament resources/views/filament tests/Feature/FilamentPluginTest.php
git commit -m "feat: add optional Filament plugin and settings page"
```

---

### Task 12: README + `.gitignore` + final polish

**Files:**
- Create: `README.md`
- Create: `.gitignore`
- Test: run the full suite.

**Interfaces:**
- Consumes: everything.
- Produces: install/usage docs.

- [ ] **Step 1: Create `.gitignore`**

```
/vendor
composer.lock
.phpunit.result.cache
.phpunit.cache
build
```

- [ ] **Step 2: Create `README.md`**

````markdown
# Laravel Google Reviews

Fetch and display Google Places reviews in Laravel and Filament apps.

> Google's Places API returns up to **5 reviews** per place plus the aggregate rating. This package cannot exceed that limit.

## Install

```bash
composer require cegem360/laravel-google-reviews
```

Publish and configure:

```bash
php artisan vendor:publish --tag=google-reviews-config
php artisan vendor:publish --tag=google-reviews-migrations   # database driver only
php artisan migrate
```

Add to `.env`:

```
GOOGLE_PLACES_API_KEY=your-key
GOOGLE_PLACES_PLACE_ID=your-place-id
GOOGLE_REVIEWS_DRIVER=database   # or: cache
```

## Sync reviews

```bash
php artisan google-reviews:sync
```

Schedule it in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('google-reviews:sync')->daily();
```

## Display

```blade
<x-google-reviews />
<x-google-reviews place-id="ChIJ..." :limit="3" />
```

Publish the view to customize:

```bash
php artisan vendor:publish --tag=google-reviews-views
```

## Facade

```php
use Madbox99\GoogleReviews\Facades\GoogleReviews;

$place = GoogleReviews::for();        // read stored/cached reviews
$place = GoogleReviews::refresh();    // fetch fresh from Google and store
```

## Filament

Add the plugin to your panel provider:

```php
use Madbox99\GoogleReviews\Filament\GoogleReviewsPlugin;

$panel->plugin(GoogleReviewsPlugin::make());
```

Publish and run the settings migration too:

```bash
php artisan vendor:publish --tag=google-reviews-migrations
php artisan migrate
```

## License

MIT
````

- [ ] **Step 3: Run the full test suite**

Run: `./vendor/bin/pest`
Expected: PASS — every test green.

- [ ] **Step 4: Commit**

```bash
git add README.md .gitignore
git commit -m "docs: add README and gitignore"
```

---

## Self-Review Notes

**Spec coverage:**
- Places API (New) client → Task 4. ✓
- DB + cache drivers, config-selectable → Tasks 5, 6. ✓
- Settings precedence DB→config→env, api key env-only → Task 3. ✓
- Blade component, publishable → Task 9, publishing in Task 8. ✓
- Filament settings page via thin plugin → Task 11. ✓
- Sync command, schedulable → Task 10. ✓
- Single place default, array-capable → Manager (Task 7), command (Task 10), settings page (Task 11). ✓
- Pest + Testbench, Http::fake → Task 1 + every feature/unit test. ✓
- Zero extra runtime deps, hand-written provider → Task 1, 8. ✓
- Publishing tags config/views/migrations → Task 8. ✓

**Placeholder scan:** No TBD/TODO in code steps; all code shown in full.

**Type consistency:** `ReviewRepository::store(PlaceData): void` / `get(string): ?PlaceData` used identically across Tasks 5, 6, 7. `PlaceData`/`ReviewData` constructor signatures consistent across Tasks 2, 4, 5, 6, 7, 9. `SettingsResolver` method names (`apiKey`, `placeId`, `driver`, `cacheTtl`, `language`) consistent across Tasks 3, 4, 7, 10, 11. Manager methods (`for`, `refresh`, `resolvePlaceId`) consistent across Tasks 7, 9, 10.

**Cross-task ordering caveat:** Task 8's provider references `SyncReviewsCommand` (Task 10). The plan flags this inline (comment out / restore). Tasks are otherwise strictly incremental.
