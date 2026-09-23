@php
    $href = route('catalog_product_page', [
        'category' => $product->category_url ?? 'catalog',
        'product' => $product->url,
    ]);
    $price = $product->price;
    $hasPrice = $price !== null && $price !== '' && (float) $price > 0;
    $priceLabel = $hasPrice
        ? number_format((float) $price, 0, '.', ' ').' ₴'
        : 'Ціна уточнюється';
@endphp

<a href="{{ $href }}" class="mega-card mega-card--product" title="{{ $product->name }}">
    <span class="mega-card__media" aria-hidden="true">
        <img src="{{ $product->getImagePath() }}" alt="" width="96" height="96" loading="lazy" decoding="async">
    </span>
    <span class="mega-card__body">
        <span class="mega-card__name">{{ $product->name }}</span>
        <span class="mega-card__meta {{ $hasPrice ? '' : 'mega-card__meta--muted' }}">{{ $priceLabel }}</span>
    </span>
</a>
