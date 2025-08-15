@php
    use App\Settings\WebsiteSettings;

    $settings = app(WebsiteSettings::class);
    $brandName = $settings->website_title;
    $logoPath = $settings->website_logo ? storage_path('app/public/' . $settings->website_logo) : null;
    $logoUrl = $settings->use_logo && $logoPath && file_exists($logoPath) ? \Illuminate\Support\Facades\Storage::url('public/' . $settings->website_logo) : null;

    $brandNames = filament()->getBrandName();
    $brandLogo = filament()->getBrandLogo();
    $brandLogoHeight = filament()->getBrandLogoHeight() ?? '1.5rem';
    $darkModeBrandLogo = filament()->getDarkModeBrandLogo();
    $hasDarkModeBrandLogo = filled($darkModeBrandLogo);

    $getLogoClasses = fn (bool $isDarkMode): string => \Illuminate\Support\Arr::toCssClasses([
        'fi-logo',
        'flex' => ! $hasDarkModeBrandLogo,
        'flex dark:hidden' => $hasDarkModeBrandLogo && (! $isDarkMode),
        'hidden dark:flex' => $hasDarkModeBrandLogo && $isDarkMode,
    ]);

    $logoStyles = "height: 40px";
@endphp

@capture($content, $logo, $isDarkMode = false)
@if ($logo instanceof \Illuminate\Contracts\Support\Htmlable)
    <div
            {{
                $attributes
                    ->class([$getLogoClasses($isDarkMode)])
                    ->style([$logoStyles])
            }}
    >
        {{ $logo }}
    </div>
@elseif (empty($logoUrl))
    <div
            {{
                $attributes->class([
                    $getLogoClasses($isDarkMode),
                    'text-xl font-bold leading-5 tracking-tight text-gray-950 dark:text-white',
                ])
            }}
    >
        {{ $brandName }}
    </div>
@else
    <img
            alt="{{ __('filament-panels::layout.logo.alt', ['name' => $brandName]) }}"
            src="{{ $logoUrl }}"
            {{
                $attributes
                    ->class([$getLogoClasses($isDarkMode)])
                    ->style([$logoStyles])
            }}
    />
@endif
@endcapture

{{ $content($brandLogo) }}

@if ($hasDarkModeBrandLogo)
    {{ $content($darkModeBrandLogo, isDarkMode: true) }}
@endif