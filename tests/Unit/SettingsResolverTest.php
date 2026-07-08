<?php

use Cegem360\GoogleReviews\Settings\SettingsResolver;
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
