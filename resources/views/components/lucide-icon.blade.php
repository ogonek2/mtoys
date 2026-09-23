@props([
    'name',
    'width' => '20',
    'class' => '',
])

<iconify-icon icon="lucide:{{ $name }}" width="{{ $width }}" height="{{ $width }}" class="inline-block align-middle {{ $class }}"></iconify-icon>
