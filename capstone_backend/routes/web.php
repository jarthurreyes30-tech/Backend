<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

// TEMPORARY: Migration endpoint - REMOVE AFTER USE!
Route::get('/run-migrations-now', function () {
    try {
        // Run migrations
        Artisan::call('migrate', ['--force' => true]);
        $migrationOutput = Artisan::output();
        
        // Clear caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        
        // Check if table exists
        $tables = DB::select('SHOW TABLES');
        $tableNames = array_map(function($table) {
            return array_values((array)$table)[0];
        }, $tables);
        
        $pendingRegExists = in_array('pending_registrations', $tableNames);
        
        return response()->json([
            'success' => true,
            'message' => 'Migrations executed successfully',
            'migration_output' => $migrationOutput,
            'pending_registrations_exists' => $pendingRegExists,
            'all_tables' => $tableNames,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
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

