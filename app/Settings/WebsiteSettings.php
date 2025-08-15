<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class WebsiteSettings extends Settings
{
    public ?string $website_title;
    public ?string $website_description;
    public ?bool $use_logo;
    public ?string $website_logo;
    public ?string $website_favicon;

    public static function group(): string
    {
        return 'website';
    }
}