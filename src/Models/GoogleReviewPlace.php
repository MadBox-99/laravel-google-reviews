<?php

declare(strict_types=1);

namespace Madbox99\GoogleReviews\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $place_id
 * @property string|null $name
 * @property float|null $rating
 * @property int|null $user_ratings_total
 * @property Carbon|null $synced_at
 */
class GoogleReviewPlace extends Model
{
    protected $table = 'google_review_places';

    protected $guarded = [];

    protected $casts = [
        'rating' => 'float',
        'user_ratings_total' => 'integer',
        'synced_at' => 'datetime',
    ];
}
