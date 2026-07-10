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

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @param  array<string, mixed>  $data
     */
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
