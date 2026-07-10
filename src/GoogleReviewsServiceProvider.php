<?php

declare(strict_types=1);

namespace Madbox99\GoogleReviews;

use Madbox99\GoogleReviews\Console\SyncReviewsCommand;
use Madbox99\GoogleReviews\Http\GooglePlacesClient;
use Madbox99\GoogleReviews\Repositories\ReviewRepositoryFactory;
use Madbox99\GoogleReviews\Settings\SettingsResolver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Override;

class GoogleReviewsServiceProvider extends ServiceProvider
{
    #[Override]
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

        $this->loadViewComponentsAs('google-reviews', []);
        Blade::anonymousComponentPath(
            __DIR__.'/../resources/views/components',
            'google-reviews'
        );
        Blade::component('google-reviews::components.google-reviews', 'google-reviews');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncReviewsCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/google-reviews.php' => config_path('google-reviews.php'),
            ], 'google-reviews-config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_google_review_places_table.php.stub' => $this->migrationPath('create_google_review_places_table.php'),
                __DIR__.'/../database/migrations/create_google_reviews_table.php.stub' => $this->migrationPath('create_google_reviews_table.php'),
                __DIR__.'/../database/migrations/create_google_review_settings_table.php.stub' => $this->migrationPath('create_google_review_settings_table.php'),
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
