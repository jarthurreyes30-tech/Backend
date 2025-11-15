<?php

/**
 * ✅ CLEANUP SCRIPT - Remove ALL donors from pending_users table
 * 
 * This script removes any donor records from pending_registrations table
 * because donors should NEVER be in that table - they use session storage now.
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\PendingRegistration;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  🧹 CLEANUP: Remove ALL donors from pending_users table\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "\n";

// Step 1: Count current donor records in pending_users
echo "Step 1: Checking for donor records in pending_users...\n";
$donorCount = PendingRegistration::where('role', 'donor')->count();
echo "  Found: {$donorCount} donor records\n\n";

if ($donorCount === 0) {
    echo "✅ DATABASE IS CLEAN - No donors in pending_users table!\n";
    echo "   This is correct. Donors should only be in session storage until verified.\n";
    exit(0);
}

// Step 2: Show the emails that will be deleted
echo "Step 2: Listing donor emails to be removed...\n";
$donors = PendingRegistration::where('role', 'donor')->get();
foreach ($donors as $donor) {
    echo "  - {$donor->email} (created: {$donor->created_at})\n";
}
echo "\n";

// Step 3: Confirm deletion
echo "Step 3: Removing donor records from pending_users...\n";
$deletedCount = PendingRegistration::where('role', 'donor')->delete();
echo "  ✅ Deleted: {$deletedCount} donor records\n\n";

// Step 4: Verify cleanup
echo "Step 4: Verifying cleanup...\n";
$remainingDonors = PendingRegistration::where('role', 'donor')->count();
if ($remainingDonors === 0) {
    echo "  ✅ SUCCESS - All donors removed from pending_users!\n";
    Log::info('✅ Cleanup complete - removed all donors from pending_users', [
        'deleted_count' => $deletedCount
    ]);
} else {
    echo "  ❌ ERROR - Still {$remainingDonors} donors in pending_users!\n";
    Log::error('Cleanup failed - donors still in pending_users', [
        'remaining' => $remainingDonors
    ]);
    exit(1);
}

// Step 5: Show charity records (should remain)
echo "\nStep 5: Verifying charity records (should remain)...\n";
$charityCount = PendingRegistration::where('role', 'charity_admin')->count();
echo "  Charities in pending_users: {$charityCount}\n";
echo "  ✅ This is correct. Charities use pending_users table.\n";

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  ✅ CLEANUP COMPLETE!\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "\n";
echo "Summary:\n";
echo "--------\n";
echo "✅ Deleted: {$deletedCount} donor records from pending_users\n";
echo "✅ Remaining charities: {$charityCount} (correct)\n";
echo "✅ Database is now clean!\n";
echo "\n";
echo "From now on:\n";
echo "------------\n";
echo "✅ Donors: Stored in SESSION until OTP verified\n";
echo "✅ Charities: Stored in pending_users until OTP verified\n";
echo "✅ No more donor pollution in database!\n";
echo "\n";
