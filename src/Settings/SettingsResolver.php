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

    /**
     * @return string|list<string>|null
     */
    public function placeId(): string|array|null
    {
        $value = $this->get('place_id');

        if (is_string($value) && str_contains($value, ',')) {
            return array_values(array_filter(
                array_map(trim(...), explode(',', $value)),
                fn (string $v): bool => $v !== ''
            ));
        }

        return $value;
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

        $value = $this->dbCache[$key] ?? null;

        if ($value !== null && trim($value) === '') {
            return null;
        }

        return $value;
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
