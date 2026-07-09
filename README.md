# Laravel Google Reviews

Fetch and display [Google Places](https://developers.google.com/maps/documentation/places/web-service/place-details) reviews in Laravel and Filament apps. Configurable via config, `.env`, or a Filament settings page, with a publishable Blade component for display.

> **Heads up:** Google's Places API returns up to **5 reviews** per place plus the aggregate rating (average + total count). This package cannot exceed that — it is an API limitation.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Filament 4 or 5 (optional — only needed for the admin settings page)

## Install

```bash
composer require cegem360/laravel-google-reviews
```

Publish the config:

```bash
php artisan vendor:publish --tag=google-reviews-config
```

If you use the `database` driver (the default), publish and run the migrations:

```bash
php artisan vendor:publish --tag=google-reviews-migrations
php artisan migrate
```

Add your credentials to `.env`:

```dotenv
GOOGLE_PLACES_API_KEY=your-api-key
GOOGLE_PLACES_PLACE_ID=your-place-id
GOOGLE_REVIEWS_DRIVER=database   # or: cache
```

## Configuration

`config/google-reviews.php`:

| Key | Env | Default | Purpose |
|-----|-----|---------|---------|
| `api_key` | `GOOGLE_PLACES_API_KEY` | — | Google Places API key (**env/config only**, never stored in the DB). |
| `place_id` | `GOOGLE_PLACES_PLACE_ID` | — | Place ID (string, or an array of IDs). |
| `driver` | `GOOGLE_REVIEWS_DRIVER` | `database` | `database` (persisted + moderatable) or `cache`. |
| `cache_ttl` | `GOOGLE_REVIEWS_CACHE_TTL` | `86400` | Cache lifetime in seconds (cache driver). |
| `language` | `GOOGLE_REVIEWS_LANGUAGE` | `hu` | Language code requested from Google. |

Settings resolve in the order **DB (Filament UI) → config file → env default**, so any of the three configuration methods works. The API key is the exception: it is always read from env/config for security.

## Sync reviews

Fetch fresh reviews from Google and store them:

```bash
php artisan google-reviews:sync                # all configured place ids
php artisan google-reviews:sync "ChIJ..."      # a specific place id
```

Schedule it in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('google-reviews:sync')->daily();
```

## Display

Drop the component anywhere in a Blade view:

```blade
<x-google-reviews />

{{-- override the place and cap the number shown --}}
<x-google-reviews place-id="ChIJ..." :limit="3" />
```

Publish the view to customize the markup (Tailwind-based by default):

```bash
php artisan vendor:publish --tag=google-reviews-views
```

## Programmatic access

```php
use Cegem360\GoogleReviews\Facades\GoogleReviews;

$place = GoogleReviews::for();        // read stored/cached reviews (nullable)
$place = GoogleReviews::refresh();    // fetch fresh from Google and store

$place->name;                // string|null
$place->rating;              // float|null (average)
$place->userRatingsTotal;    // int|null
foreach ($place->reviews as $review) {
    $review->authorName;     // string
    $review->rating;         // int (0-5)
    $review->text;           // string
    $review->publishedAt;    // DateTimeImmutable|null
}
```

## Filament

The package ships an optional Filament 4/5 plugin that adds a **Google Reviews** settings page (edit place ID, driver, cache TTL, language; the API key is shown read-only since it lives in `.env`).

Register it in your panel provider:

```php
use Cegem360\GoogleReviews\Filament\GoogleReviewsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(GoogleReviewsPlugin::make());
}
```

The settings page persists to a `google_review_settings` table, so publish and run the migrations if you haven't already:

```bash
php artisan vendor:publish --tag=google-reviews-migrations
php artisan migrate
```

## Testing

```bash
composer install
./vendor/bin/pest
```

## License

MIT
