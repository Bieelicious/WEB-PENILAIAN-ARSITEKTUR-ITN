<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('website.website_title', 'Tugas Akhir');
        $this->migrator->add('website.website_description', 'Tugas Akhir');
        $this->migrator->add('website.use_logo', '0');
        $this->migrator->add('website.website_logo', '');
        $this->migrator->add('website.website_favicon', '');
    }
};
