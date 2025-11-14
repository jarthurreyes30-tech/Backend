<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PendingRegistration;
use App\Models\User;

echo "=== TESTING EMAIL VERIFICATION FLOW ===\n\n";

// Check pending registrations
$pending = PendingRegistration::first();
if ($pending) {
    echo "✅ Pending Registration Found:\n";
    echo "   Email: {$pending->email}\n";
    echo "   Code: {$pending->verification_code}\n";
    echo "   Expires: {$pending->expires_at}\n\n";
} else {
    echo "❌ No pending registrations\n\n";
}

// Check users
$userCount = User::count();
echo "Users in database: $userCount\n";

if ($userCount > 0) {
    echo "❌ FAILED! Users should be 0 before verification!\n";
} else {
    echo "✅ CORRECT! No users created yet.\n";
}

echo "\n=== TEST VERIFICATION NOW ===\n";
if ($pending) {
    echo "Use this code to verify: {$pending->verification_code}\n";
    echo "POST http://localhost:8000/api/auth/verify-registration\n";
    echo json_encode([
        'email' => $pending->email,
        'code' => $pending->verification_code
    ], JSON_PRETTY_PRINT);
}
