<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

// Health check endpoint
Route::get('/health-check', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
    ]);
});

// Override Laravel's default storage route with CORS support
Route::get('/storage/{path}', function ($path) {
    try {
        // Check if file exists
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }
        
        $file = Storage::disk('public')->path($path);
        $mimeType = Storage::disk('public')->mimeType($path);
        
        return response()->file($file, [
            'Content-Type' => $mimeType,
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => '*',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    } catch (\Exception $e) {
        \Log::error('Storage file serving error', [
            'path' => $path,
            'error' => $e->getMessage()
        ]);
        return response()->json(['error' => 'File serving error'], 500);
    }
})->where('path', '.*');

// Image diagnosis endpoint
Route::get('/diagnose-images', function () {
    try {
        $publicPath = storage_path('app/public');
        $publicExists = is_dir($publicPath);
        
        // Check for common image directories
        $imageDirs = [
            'profile_images',
            'charity_logos', 
            'charity_covers',
            'campaign_images',
            'charity_docs'
        ];
        
        $dirStatus = [];
        $sampleFiles = [];
        
        foreach ($imageDirs as $dir) {
            $fullPath = $publicPath . '/' . $dir;
            $exists = is_dir($fullPath);
            $dirStatus[$dir] = [
                'exists' => $exists,
                'path' => $fullPath,
                'files_count' => $exists ? count(glob($fullPath . '/*')) : 0
            ];
            
            if ($exists) {
                $files = glob($fullPath . '/*');
                if (!empty($files)) {
                    $sampleFiles[$dir] = array_slice($files, 0, 3);
                }
            }
        }
        
        return response()->json([
            'storage_public_exists' => $publicExists,
            'storage_public_path' => $publicPath,
            'app_url' => config('app.url'),
            'filesystem_config' => [
                'default' => config('filesystems.default'),
                'public_disk_url' => config('filesystems.disks.public.url'),
                'public_disk_root' => config('filesystems.disks.public.root')
            ],
            'image_directories' => $dirStatus,
            'sample_files' => $sampleFiles,
            'storage_link_exists' => is_link(public_path('storage')),
            'storage_link_target' => is_link(public_path('storage')) ? readlink(public_path('storage')) : null
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

