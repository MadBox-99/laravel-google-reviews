<?php

namespace Cegem360\GoogleReviews\Http;

use Cegem360\GoogleReviews\Data\PlaceData;
use Cegem360\GoogleReviews\Data\ReviewData;
use Cegem360\GoogleReviews\Settings\SettingsResolver;
use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GooglePlacesClient
{
    private const string BASE = 'https://places.googleapis.com/v1/places/';

    private const string FIELD_MASK = 'id,displayName,rating,userRatingCount,reviews';

    public function __construct(private readonly SettingsResolver $settings) {}

    public function fetch(string $placeId): PlaceData
    {
        $apiKey = $this->settings->apiKey();
        throw_unless($apiKey, RuntimeException::class, 'Google Places API key is not configured.');

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

    /**
     * @param  array<string, mixed>  $data
     */
    private function toPlaceData(string $placeId, array $data): PlaceData
    {
        $reviews = array_map(
            $this->toReviewData(...),
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

    /**
     * @param  array<string, mixed>  $r
     */
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
