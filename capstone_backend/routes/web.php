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

// TEMPORARY: Database check with cleanup
Route::get('/db-check', function () {
    try {
        $tables = DB::select('SHOW TABLES');
        $tableNames = array_map(function($table) {
            return array_values((array)$table)[0];
        }, $tables);
        
        // Check pending registrations
        $pendingCount = DB::table('pending_registrations')->count();
        $pendingEmails = DB::table('pending_registrations')->pluck('email');
        
        return response()->json([
            'success' => true,
            'database_connected' => true,
            'tables_count' => count($tableNames),
            'has_users_table' => in_array('users', $tableNames),
            'has_pending_registrations' => in_array('pending_registrations', $tableNames),
            'pending_registrations_count' => $pendingCount,
            'pending_emails' => $pendingEmails,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'database_connected' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

// TEMPORARY: Clean pending registrations
Route::get('/clean-pending', function () {
    try {
        $deleted = DB::table('pending_registrations')->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Pending registrations cleaned',
            'deleted_count' => $deleted,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

// TEMPORARY: Test SMTP directly
Route::get('/test-smtp-direct', function () {
    try {
        $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
            'smtp.gmail.com',
            587,
            true
        );
        $transport->setUsername('charityhub25@gmail.com');
        $transport->setPassword('nnkdtchwnldeubms');
        
        $mailer = new \Symfony\Component\Mailer\Mailer($transport);
        
        $email = (new \Symfony\Component\Mime\Email())
            ->from('charityhub25@gmail.com')
            ->to('regondolajohnarthur51@gmail.com')
            ->subject('Test Email from Railway')
            ->text('This is a test email to verify SMTP connection.');
        
        $mailer->send($email);
        
        return response()->json([
            'success' => true,
            'message' => 'SMTP test email sent successfully',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'smtp_config' => [
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'username' => 'charityhub25@gmail.com',
                'encryption' => 'tls',
            ],
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

// TEMPORARY: Force process queue jobs
Route::get('/force-process-queue', function () {
    try {
        // Get pending jobs
        $pendingJobs = \DB::table('jobs')->count();
        
        // Process jobs one by one
        $processed = 0;
        while (\DB::table('jobs')->exists() && $processed < 10) {
            \Artisan::call('queue:work', [
                '--once' => true,
                '--timeout' => 30,
                '--tries' => 1
            ]);
            $processed++;
        }
        
        $remainingJobs = \DB::table('jobs')->count();
        $failedJobs = \DB::table('failed_jobs')->count();
        
        return response()->json([
            'success' => true,
            'message' => 'Queue processing completed',
            'jobs_before' => $pendingJobs,
            'jobs_processed' => $processed,
            'jobs_remaining' => $remainingJobs,
            'failed_jobs' => $failedJobs,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// TEMPORARY: Send test email immediately (bypass queue)
Route::get('/send-test-email-now', function () {
    try {
        $email = new \App\Mail\VerifyEmailMail([
            'name' => 'Test User',
            'email' => 'regondolajohnarthur51@gmail.com',
            'code' => '123456',
            'token' => 'test-token',
            'expires_in' => 15,
        ]);
        
        // Send immediately, not queued
        \Mail::to('regondolajohnarthur51@gmail.com')->send($email);
        
        return response()->json([
            'success' => true,
            'message' => 'Test email sent immediately (not queued)',
            'to' => 'regondolajohnarthur51@gmail.com',
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

