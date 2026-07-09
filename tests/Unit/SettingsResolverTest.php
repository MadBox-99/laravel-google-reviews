<?php

use Cegem360\GoogleReviews\Settings\SettingsResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    config()->set('google-reviews.api_key', 'env-key');
    config()->set('google-reviews.driver', 'database');
    config()->set('google-reviews.place_id', 'config-place');
    config()->set('google-reviews.settings_table', 'google_review_settings');
});

it('falls back to config when no settings table exists', function (): void {
    $resolver = new SettingsResolver;

    expect($resolver->driver())->toBe('database')
        ->and($resolver->placeId())->toBe('config-place')
        ->and($resolver->apiKey())->toBe('env-key');
});

it('lets DB settings override config, except api_key', function (): void {
    Schema::create('google_review_settings', function ($table): void {
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

    $resolver = new SettingsResolver;

    expect($resolver->driver())->toBe('cache')
        ->and($resolver->placeId())->toBe('db-place')
        ->and($resolver->apiKey())->toBe('env-key');
});

it('splits a comma-separated DB place_id into a trimmed array', function (): void {
    Schema::create('google_review_settings', function ($table): void {
        $table->id();
        $table->string('key')->unique();
        $table->text('value')->nullable();
        $table->timestamps();
    });
    DB::table('google_review_settings')->insert([
        ['key' => 'place_id', 'value' => 'ChIJ-aaa, ChIJ-bbb'],
    ]);

    $resolver = new SettingsResolver;

    expect($resolver->placeId())->toBe(['ChIJ-aaa', 'ChIJ-bbb']);
});

it('returns a single DB place_id as a plain string', function (): void {
    Schema::create('google_review_settings', function ($table): void {
        $table->id();
        $table->string('key')->unique();
        $table->text('value')->nullable();
        $table->timestamps();
    });
    DB::table('google_review_settings')->insert([
        ['key' => 'place_id', 'value' => 'ChIJ-solo'],
    ]);

    $resolver = new SettingsResolver;

    expect($resolver->placeId())->toBe('ChIJ-solo');
});

it('falls back to config cache_ttl when the DB value is an empty string', function (): void {
    config()->set('google-reviews.cache_ttl', 86400);

    Schema::create('google_review_settings', function ($table): void {
        $table->id();
        $table->string('key')->unique();
        $table->text('value')->nullable();
        $table->timestamps();
    });
    DB::table('google_review_settings')->insert([
        ['key' => 'cache_ttl', 'value' => ''],
    ]);

    $resolver = new SettingsResolver;

    expect($resolver->cacheTtl())->toBe(86400);
});
