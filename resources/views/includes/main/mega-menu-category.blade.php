@php
    $category = $block['category'];
    $count = (int) ($block['count'] ?? 0);
    $href = route('catalog_category_page', $category->url);

    if (! empty($category->meta_image)) {
        $raw = (string) $category->meta_image;
        $image = str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')
            ? $raw
            : asset('storage/'.ltrim($raw, '/'));
    } elseif (! empty($block['products']) && $block['products']->isNotEmpty()) {
        $image = $block['products']->first()->getImagePath();
    } else {
        $image = asset('dist/img/no-image.png');
    }

    $countLabel = $count > 0 ? $count.' тов.' : 'Відкрити';
@endphp

<a href="{{ $href }}" class="mega-card mega-card--category" title="{{ $category->name }}">
    <span class="mega-card__media" aria-hidden="true">
        <img src="{{ $image }}" alt="" width="72" height="72" loading="lazy" decoding="async">
    </span>
    <span class="mega-card__body">
        <span class="mega-card__name">{{ $category->name }}</span>
        <span class="mega-card__meta mega-card__meta--muted">{{ $countLabel }}</span>
    </span>
</a>
