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

// TEMPORARY: Clear all caches
Route::get('/clear-all-caches', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        
        return response()->json([
            'success' => true,
            'message' => 'All caches cleared successfully',
            'cleared' => ['config', 'cache', 'routes', 'views', 'compiled']
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

// TEMPORARY: Test registration endpoint
Route::post('/test-registration', function (\Illuminate\Http\Request $request) {
    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|unique:pending_registrations,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Generate 6-digit code
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = \Str::random(60);
        $expiresAt = now()->addMinutes(15);

        // Store pending registration
        $pendingRegistration = \App\Models\PendingRegistration::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Hash::make($validated['password']),
            'role' => 'donor',
            'verification_code' => $code,
            'verification_token' => $token,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'resend_count' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test registration successful - NO EMAIL SENT',
            'email' => $validated['email'],
            'code' => $code,
            'pending_id' => $pendingRegistration->id,
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

