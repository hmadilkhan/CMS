<?php

/**
 * AI Chatbot Test Script
 * 
 * Run: php test-ai-flexible.php
 * 
 * This tests the new flexible AI query system
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AiChatService;
use App\Models\User;

echo "=== AI Chatbot Flexibility Test ===\n\n";

// Get test user
$user = User::first();

if (!$user) {
    echo "❌ No user found in database. Please create a user first.\n";
    exit(1);
}

echo "Testing with user: {$user->name} ({$user->email})\n";
echo "User roles: " . $user->roles->pluck('name')->join(', ') . "\n\n";

$service = app(AiChatService::class);

// Test questions - mix of common and uncommon queries
$testQuestions = [
    // Basic counts
    'How many projects do I have?',
    'Total customers count',
    'Kitne tickets hain?',
    
    // Lists with filters
    'Show me all customers',
    'List all projects',
    'Show pending tickets',
    'Projects in Sales department',
    
    // RELATIONSHIP TESTS - NEW!
    'Show projects with customer names',
    'List tickets with creator name',
    'Show projects with customer email and phone',
    'Projects with department and subdepartment names',
    'Tickets with assigned user name',
    'Show customers with their projects',
    
    // Uncommon queries (should work with new system)
    'Show me customers from California',
    'List all employees',
    'Show me all departments',
    'How many users are in the system?',
    'List tickets assigned to me',
    
    // Grouped queries
    'Show tickets grouped by status',
    'Projects count by department',
    'Customers by state',
    
    // Complex queries
    'Show me project details with customer email and phone',
    'List all service tickets with creator name',
    
    // Should be blocked (security)
    'Delete all projects',
    'Update customer email',
    'DROP TABLE users',
];

$successCount = 0;
$failCount = 0;
$blockedCount = 0;

foreach ($testQuestions as $index => $question) {
    $num = $index + 1;
    echo "[$num] Question: $question\n";
    
    try {
        $chat = $service->send($user, $question);
        $lastMessage = $chat->messages->last();
        
        $status = $lastMessage->metadata['status'] ?? 'unknown';
        $intent = $lastMessage->metadata['query_plan']['intent'] ?? 'N/A';
        $approved = $lastMessage->metadata['sql_validation']['approved'] ?? false;
        
        echo "    Status: $status\n";
        echo "    Intent: $intent\n";
        
        if ($status === 'success') {
            $rowCount = $lastMessage->metadata['query_execution']['row_count'] ?? 0;
            echo "    ✅ Success! Rows: $rowCount\n";
            echo "    Response: " . substr($lastMessage->content, 0, 100) . "...\n";
            $successCount++;
        } elseif ($status === 'unsafe_query_rejected') {
            echo "    🛡️  Blocked (Security)\n";
            echo "    Reason: " . ($lastMessage->metadata['sql_validation']['reason'] ?? 'Unknown') . "\n";
            $blockedCount++;
        } elseif ($status === 'invalid_question') {
            echo "    ❓ Unknown intent\n";
            echo "    Response: $lastMessage->content\n";
            $failCount++;
        } else {
            echo "    ⚠️  Failed\n";
            echo "    Response: $lastMessage->content\n";
            $failCount++;
        }
        
        // Show SQL if available
        if (isset($lastMessage->metadata['sql_preview']['sql'])) {
            $sql = $lastMessage->metadata['sql_preview']['sql'];
            echo "    SQL: " . substr($sql, 0, 150) . "...\n";
        }
        
    } catch (Exception $e) {
        echo "    ❌ Error: {$e->getMessage()}\n";
        $failCount++;
    }
    
    echo "\n";
}

echo "=== Test Summary ===\n";
echo "✅ Successful: $successCount\n";
echo "🛡️  Blocked (Security): $blockedCount\n";
echo "❌ Failed: $failCount\n";
echo "📊 Total: " . count($testQuestions) . "\n\n";

$successRate = round(($successCount / count($testQuestions)) * 100, 1);
echo "Success Rate: $successRate%\n";

if ($successRate >= 70) {
    echo "🎉 Great! The flexible AI system is working well!\n";
} elseif ($successRate >= 50) {
    echo "⚠️  Moderate performance. Some improvements needed.\n";
} else {
    echo "❌ Poor performance. Check configuration and OpenAI API key.\n";
}
