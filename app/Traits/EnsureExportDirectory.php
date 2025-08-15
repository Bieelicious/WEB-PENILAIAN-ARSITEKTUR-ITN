<?php

namespace App\Traits;

trait EnsureExportDirectory
{
    protected function ensureExportsFolderExists(): void
    {
        $exportPath = storage_path('app/public/exports');

        if (!is_dir($exportPath)) {
            mkdir($exportPath, 0775, true);
        }
    }
}