@props(['mailConfig' => []])
@php
    if (empty($mailConfig) && app()->bound('mailsettings.current')) {
        $mailConfig = (array)app('mailsettings.current');
    }
    $casinoName = $mailConfig['casino_name'] ?? config('casino.name', config('app.name'));
    $loginUrl = !empty($mailConfig['login_url'])
        ? $mailConfig['login_url']
        : config('casino.support_url', config('app.url'));
    $headerUrl = !empty($mailConfig['url']) ? $mailConfig['url'] : $loginUrl;
    $footerText = $mailConfig['footer'] ?? ($casinoName . '. All rights reserved.');
@endphp

<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="$headerUrl" :logo="($mailConfig['logo'] ?? null)">
            {{ $casinoName }}
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
            © {{ date('Y') }} {{ $footerText }}
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
