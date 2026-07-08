<?php

namespace Cegem360\GoogleReviews\Settings;

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
