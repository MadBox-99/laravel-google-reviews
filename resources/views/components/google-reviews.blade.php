@props(['placeId' => null, 'limit' => null])

@php
    $place = app(\Cegem360\GoogleReviews\GoogleReviewsManager::class)->for($placeId);
    $reviews = $place?->reviews ?? [];
    if ($limit) {
        $reviews = array_slice($reviews, 0, (int) $limit);
    }
@endphp

<div {{ $attributes->merge(['class' => 'google-reviews']) }}>
    @if ($place)
        <div class="google-reviews__header flex items-center gap-2 mb-4">
            <span class="google-reviews__name text-lg font-semibold">{{ $place->name }}</span>
            @if ($place->rating !== null)
                <span class="google-reviews__rating text-yellow-500 font-medium">
                    ★ {{ number_format($place->rating, 1) }}
                </span>
            @endif
            @if ($place->userRatingsTotal !== null)
                <span class="google-reviews__count text-sm text-gray-500">
                    ({{ $place->userRatingsTotal }})
                </span>
            @endif
        </div>

        <ul class="google-reviews__list space-y-4">
            @foreach ($reviews as $review)
                <li class="google-reviews__item border rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-2">
                        @if ($review->authorPhotoUrl)
                            <img src="{{ $review->authorPhotoUrl }}" alt="{{ $review->authorName }}"
                                 class="w-8 h-8 rounded-full" loading="lazy">
                        @endif
                        <span class="font-medium">{{ $review->authorName }}</span>
                        <span class="text-yellow-500" aria-label="{{ $review->rating }} / 5">
                            {{ str_repeat('★', max(0, min(5, $review->rating))) }}{{ str_repeat('☆', max(0, 5 - min(5, $review->rating))) }}
                        </span>
                    </div>
                    <p class="google-reviews__text text-gray-700">{{ $review->text }}</p>
                </li>
            @endforeach
        </ul>
    @else
        <p class="google-reviews__empty text-sm text-gray-500">
            {{ __('No reviews available yet.') }}
        </p>
    @endif
</div>
