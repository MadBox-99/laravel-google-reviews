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
            return array_values(array_filter(array_map(strval(...), $configured)));
        }

        return $configured ? [$configured] : [];
    }
}
