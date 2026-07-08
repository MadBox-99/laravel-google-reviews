<?php

namespace Cegem360\GoogleReviews\Models;

use Illuminate\Database\Eloquent\Model;

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
