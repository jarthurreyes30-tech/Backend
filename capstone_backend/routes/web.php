<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

// TEMPORARY: Simple health check
Route::get('/health-check', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
    ]);
});

// TEMPORARY: Database check
Route::get('/db-check', function () {
    try {
        $tables = DB::select('SHOW TABLES');
        $tableNames = array_map(function($table) {
            return array_values((array)$table)[0];
        }, $tables);
        
        return response()->json([
            'success' => true,
            'database_connected' => true,
            'tables_count' => count($tableNames),
            'has_users_table' => in_array('users', $tableNames),
            'has_pending_registrations' => in_array('pending_registrations', $tableNames),
            'all_tables' => $tableNames,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'database_connected' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

// TEMPORARY: Simple migration runner
Route::get('/run-migrations-simple', function () {
    try {
        // Just run migrate without complex operations
        \Illuminate\Support\Facades\Artisan::call('migrate --force');
        
        return response()->json([
            'success' => true,
            'message' => 'Migration command executed',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ], 500);
    }
});

// Override Laravel's default storage route with CORS support
Route::get('/storage/{path}', function ($path) {
    // Check if file exists
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
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
})->where('path', '.*');

