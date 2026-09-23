@extends('layouts.app')

@section('seo')
    <title>{{ $spaInitial['meta']['title'] ?? 'Mtoys' }}</title>
    @if (!empty($spaInitial['meta']['description']))
        <meta name="description" content="{{ $spaInitial['meta']['description'] }}">
    @endif
@endsection

@section('content')
    <script id="spa-initial" type="application/json">{!! json_encode($spaInitial, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    <div id="spa-root"></div>
@endsection
