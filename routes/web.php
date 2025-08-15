<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;


Route::get('/', function () {
    return redirect('/lecturer');
});

Route::get('/download-excel/{filename}', function ($filename) {
    $filename = urldecode(basename($filename));
    $path = "exports/{$filename}";

    if (!Storage::disk('public')->exists($path)) {
        \Log::error('Excel file not found', ['path' => $path]);
        abort(404, 'File not found');
    }

    $response = new StreamedResponse(function () use ($path) {
        $stream = Storage::disk('public')->readStream($path);
        fpassthru($stream);
        fclose($stream);
    });

    $response->headers->set('Content-Type', Storage::disk('public')->mimeType($path));
    $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($path) . '"');

    $response->send();
    Storage::disk('public')->delete($path);

    return $response;
})->name('download.excel');

Route::get('/download-pdf/{filename}', function ($filename) {
    $filename = urldecode(basename($filename));
    $path = "exports/{$filename}";

    if (!Storage::disk('public')->exists($path)) {
        \Log::error('PDF file not found', ['path' => $path]);
        abort(404, 'File not found');
    }

    $response = new StreamedResponse(function () use ($path) {
        $stream = Storage::disk('public')->readStream($path);
        fpassthru($stream);
        fclose($stream);
    });

    $response->headers->set('Content-Type', Storage::disk('public')->mimeType($path));
    $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($path) . '"');

    $response->send();
    Storage::disk('public')->delete($path);

    return $response;
})->name('download.pdf');
