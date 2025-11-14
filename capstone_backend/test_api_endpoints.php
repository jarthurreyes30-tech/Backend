<?php
/**
 * API Endpoint Testing Script
 * Run this script to test all critical API endpoints
 * 
 * Usage: php test_api_endpoints.php
 */

// Define base URL
define('BASE_URL', 'https://backend-production-3c74.up.railway.app');
// define('BASE_URL', 'http://localhost:8000'); // For local testing

// Color codes for terminal output
define('GREEN', "\033[32m");
define('RED', "\033[31m");
define('YELLOW', "\033[33m");
define('BLUE', "\033[34m");
define('RESET', "\033[0m");

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function testEndpoint($method, $endpoint, $data = null, $headers = [], $expectedStatus = 200) {
    global $totalTests, $passedTests, $failedTests;
    
    $totalTests++;
    $url = BASE_URL . $endpoint;
    
    echo BLUE . "\n[TEST $totalTests] $method $endpoint" . RESET . "\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'Content-Type: application/json';
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo RED . "✗ FAILED - cURL Error: $error" . RESET . "\n";
        $failedTests++;
        return null;
    }
    
    $responseData = json_decode($response, true);
    
    if ($httpCode === $expectedStatus) {
        echo GREEN . "✓ PASSED - Status: $httpCode" . RESET . "\n";
        $passedTests++;
    } else {
        echo RED . "✗ FAILED - Expected: $expectedStatus, Got: $httpCode" . RESET . "\n";
        echo YELLOW . "Response: " . substr($response, 0, 200) . RESET . "\n";
        $failedTests++;
    }
    
    return $responseData;
}

echo BLUE . "===========================================\n";
echo "API ENDPOINT TESTING SCRIPT\n";
echo "Base URL: " . BASE_URL . "\n";
echo "===========================================\n" . RESET;

// Test 1: Health Check
echo "\n" . YELLOW . "--- HEALTH CHECKS ---" . RESET;
testEndpoint('GET', '/api/ping');

// Test 2: Email Connection Test
testEndpoint('GET', '/api/email/test-connection');

// Test 3: Registration (will fail if email exists - that's OK)
echo "\n" . YELLOW . "--- AUTHENTICATION ---" . RESET;
$testEmail = 'test_' . time() . '@example.com';
$registrationData = [
    'name' => 'Test User',
    'email' => $testEmail,
    'password' => 'Password123!',
    'password_confirmation' => 'Password123!'
];
$regResponse = testEndpoint('POST', '/api/auth/register-minimal', $registrationData, [], 201);

// Test 4: Resend Verification (if registration succeeded)
if ($regResponse && isset($regResponse['success']) && $regResponse['success']) {
    testEndpoint('POST', '/api/auth/resend-verification-code', ['email' => $testEmail]);
}

// Test 5: Admin Login
$adminLoginData = [
    'email' => 'admin@example.com',
    'password' => 'password'
];
$loginResponse = testEndpoint('POST', '/api/auth/login', $adminLoginData);

$adminToken = null;
if ($loginResponse && isset($loginResponse['token'])) {
    $adminToken = $loginResponse['token'];
    echo GREEN . "Admin token obtained: " . substr($adminToken, 0, 20) . "..." . RESET . "\n";
}

// Test 6: Protected Endpoints (with admin token)
if ($adminToken) {
    echo "\n" . YELLOW . "--- ADMIN ENDPOINTS ---" . RESET;
    
    $authHeaders = ['Authorization: Bearer ' . $adminToken];
    
    testEndpoint('GET', '/me', null, $authHeaders);
    testEndpoint('GET', '/api/admin/dashboard', null, $authHeaders);
    testEndpoint('GET', '/api/admin/users', null, $authHeaders);
    testEndpoint('GET', '/api/admin/charities', null, $authHeaders);
}

// Test 7: Public Endpoints
echo "\n" . YELLOW . "--- PUBLIC ENDPOINTS ---" . RESET;
testEndpoint('GET', '/api/charities');
testEndpoint('GET', '/api/campaigns');
testEndpoint('GET', '/api/categories');

// Test 8: Location Endpoints
echo "\n" . YELLOW . "--- LOCATION ENDPOINTS ---" . RESET;
testEndpoint('GET', '/api/locations/regions');

// Summary
echo "\n" . BLUE . "===========================================\n";
echo "TEST SUMMARY\n";
echo "===========================================\n" . RESET;
echo "Total Tests: $totalTests\n";
echo GREEN . "Passed: $passedTests\n" . RESET;
echo RED . "Failed: $failedTests\n" . RESET;
echo BLUE . "===========================================\n" . RESET;

if ($failedTests === 0) {
    echo GREEN . "\n✓ ALL TESTS PASSED!\n" . RESET;
    exit(0);
} else {
    echo RED . "\n✗ SOME TESTS FAILED!\n" . RESET;
    exit(1);
}
