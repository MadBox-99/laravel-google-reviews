<?php

namespace Cegem360\GoogleReviews\Models;

use Illuminate\Database\Eloquent\Model;

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
