<?php

declare(strict_types=1);

namespace Cegem360\GoogleReviews\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $place_id
 * @property string $google_review_id
 * @property string $author_name
 * @property string|null $author_photo_url
 * @property int $rating
 * @property string|null $text
 * @property string|null $language
 * @property Carbon|null $published_at
 * @property bool $visible
 * @property array<string, mixed>|null $raw
 */
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
