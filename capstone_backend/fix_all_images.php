<?php

/**
 * FIX ALL IMAGES - Comprehensive image path audit and fix
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\Charity;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  🖼️  FIX ALL IMAGES - Comprehensive Audit & Fix\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "\n";

// Check storage link
echo "📂 STORAGE CONFIGURATION CHECK\n";
echo "─────────────────────────────────────────\n";
$storagePath = storage_path('app/public');
$publicLink = public_path('storage');
$linkExists = file_exists($publicLink);

echo "Storage path: {$storagePath}\n";
echo "Public link: {$publicLink}\n";
echo "Link exists: " . ($linkExists ? "✅ YES" : "❌ NO") . "\n";

if (!$linkExists) {
    echo "⚠️  WARNING: Storage link does not exist!\n";
    echo "Run: php artisan storage:link\n";
}
echo "\n";

// Check charity logos
echo "🏢 CHARITY LOGOS & COVER IMAGES\n";
echo "─────────────────────────────────────────\n";
$charities = Charity::all();
$charityLogoCount = 0;
$charityLogoBroken = 0;
$charityCoverCount = 0;
$charityCoverBroken = 0;

foreach ($charities as $charity) {
    if ($charity->logo_path) {
        $charityLogoCount++;
        $exists = Storage::disk('public')->exists($charity->logo_path);
        if (!$exists) {
            $charityLogoBroken++;
            echo "  ❌ {$charity->name}: Logo missing - {$charity->logo_path}\n";
        }
    }
    if ($charity->cover_image) {
        $charityCoverCount++;
        $exists = Storage::disk('public')->exists($charity->cover_image);
        if (!$exists) {
            $charityCoverBroken++;
            echo "  ❌ {$charity->name}: Cover missing - {$charity->cover_image}\n";
        }
    }
}

echo "Total charity logos: {$charityLogoCount}\n";
echo "Broken logos: " . ($charityLogoBroken > 0 ? "❌ {$charityLogoBroken}" : "✅ 0") . "\n";
echo "Total charity covers: {$charityCoverCount}\n";
echo "Broken covers: " . ($charityCoverBroken > 0 ? "❌ {$charityCoverBroken}" : "✅ 0") . "\n";
echo "\n";

// Check campaign images
echo "🎯 CAMPAIGN IMAGES\n";
echo "─────────────────────────────────────────\n";
$campaigns = Campaign::all();
$campaignImageCount = 0;
$campaignImageBroken = 0;

foreach ($campaigns as $campaign) {
    // Check all possible image field names
    $imagePath = $campaign->cover_image_path ?? $campaign->image_path ?? $campaign->banner_image ?? null;
    
    if ($imagePath) {
        $campaignImageCount++;
        $exists = Storage::disk('public')->exists($imagePath);
        if (!$exists) {
            $campaignImageBroken++;
            echo "  ❌ {$campaign->title}: Image missing - {$imagePath}\n";
        }
    }
}

echo "Total campaign images: {$campaignImageCount}\n";
echo "Broken images: " . ($campaignImageBroken > 0 ? "❌ {$campaignImageBroken}" : "✅ 0") . "\n";
echo "\n";

// Check user profile images
echo "👤 USER PROFILE IMAGES\n";
echo "─────────────────────────────────────────\n";
$users = User::whereNotNull('profile_image')->get();
$userImageCount = $users->count();
$userImageBroken = 0;

foreach ($users as $user) {
    if ($user->profile_image) {
        $exists = Storage::disk('public')->exists($user->profile_image);
        if (!$exists) {
            $userImageBroken++;
            echo "  ❌ {$user->name}: Profile image missing - {$user->profile_image}\n";
        }
    }
}

echo "Total user profile images: {$userImageCount}\n";
echo "Broken images: " . ($userImageBroken > 0 ? "❌ {$userImageBroken}" : "✅ 0") . "\n";
echo "\n";

// Test URL generation
echo "🔗 IMAGE URL GENERATION TEST\n";
echo "─────────────────────────────────────────\n";
$testCharity = Charity::whereNotNull('logo_path')->first();
if ($testCharity) {
    echo "Test charity: {$testCharity->name}\n";
    echo "Logo path (DB): {$testCharity->logo_path}\n";
    echo "Logo URL (accessor): {$testCharity->logo_url}\n";
    echo "Expected URL: https://backend-production-3c74.up.railway.app/storage/{$testCharity->logo_path}\n";
    echo "File exists: " . (Storage::disk('public')->exists($testCharity->logo_path) ? "✅ YES" : "❌ NO") . "\n";
} else {
    echo "No charities with logos found for testing\n";
}
echo "\n";

// Summary
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  📊 SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "\n";
$totalImages = $charityLogoCount + $charityCoverCount + $campaignImageCount + $userImageCount;
$totalBroken = $charityLogoBroken + $charityCoverBroken + $campaignImageBroken + $userImageBroken;

echo "Total images in database: {$totalImages}\n";
echo "Total broken images: " . ($totalBroken > 0 ? "❌ {$totalBroken}" : "✅ 0") . "\n";
echo "Storage link: " . ($linkExists ? "✅ EXISTS" : "❌ MISSING") . "\n";
echo "\n";

if (!$linkExists) {
    echo "⚠️  ACTION REQUIRED:\n";
    echo "1. Run: php artisan storage:link\n";
    echo "2. Make sure public/storage symlink exists\n";
    echo "3. On Railway, ensure storage is persistent\n";
    echo "\n";
}

if ($totalBroken > 0) {
    echo "⚠️  BROKEN IMAGES FOUND:\n";
    echo "1. Check if files exist in storage/app/public/\n";
    echo "2. Verify upload paths in controllers\n";
    echo "3. Re-upload broken images through admin interface\n";
    echo "\n";
}

if ($linkExists && $totalBroken == 0) {
    echo "✅ ALL IMAGES ARE CONFIGURED CORRECTLY!\n";
    echo "\n";
}

echo "Done!\n";
