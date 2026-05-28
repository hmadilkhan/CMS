<?php

/**
 * Debug: "Priority wise tickets summary show karo"
 * 
 * Run: php debug-priority-tickets.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AiChatService;
use App\Models\User;
use App\Models\ServiceTicket;

echo "=== Debug: Priority-wise Tickets Query ===\n\n";

// Get user
$user = User::first();
if (!$user) {
    echo "❌ No user found\n";
    exit(1);
}

echo "User: {$user->name} (ID: {$user->id})\n";
echo "Roles: " . $user->roles->pluck('name')->join(', ') . "\n\n";

// Check database
echo "--- Database Check ---\n";
$totalTickets = ServiceTicket::count();
echo "Total Tickets: $totalTickets\n";

if ($totalTickets > 0) {
    $priorities = ServiceTicket::select('priority')
        ->groupBy('priority')
        ->pluck('priority');
    echo "Priorities in DB: " . $priorities->join(', ') . "\n";
    
    foreach ($priorities as $priority) {
        $count = ServiceTicket::where('priority', $priority)->count();
        echo "  - $priority: $count tickets\n";
    }
} else {
    echo "⚠️  No tickets in database!\n";
}

echo "\n--- Testing AI Query ---\n";

$service = app(AiChatService::class);

try {
    $chat = $service->send($user, 'Priority wise tickets summary show karo');
    $lastMessage = $chat->messages->last();
    
    echo "Status: " . ($lastMessage->metadata['status'] ?? 'unknown') . "\n";
    echo "Intent: " . ($lastMessage->metadata['query_plan']['intent'] ?? 'N/A') . "\n";
    
    // Show plan
    if (isset($lastMessage->metadata['query_plan'])) {
        $plan = $lastMessage->metadata['query_plan'];
        echo "\n--- Query Plan ---\n";
        echo "Answer Type: " . ($plan['answer_type'] ?? 'N/A') . "\n";
        echo "Tables: " . json_encode($plan['tables'] ?? []) . "\n";
        echo "Columns: " . json_encode($plan['columns'] ?? []) . "\n";
        echo "Group By: " . json_encode($plan['group_by'] ?? []) . "\n";
        echo "Filters: " . json_encode($plan['filters'] ?? []) . "\n";
    }
    
    // Show SQL
    if (isset($lastMessage->metadata['sql_preview'])) {
        $sqlPreview = $lastMessage->metadata['sql_preview'];
        echo "\n--- Generated SQL ---\n";
        echo "SQL: " . $sqlPreview['sql'] . "\n";
        echo "Bindings: " . json_encode($sqlPreview['bindings'] ?? []) . "\n";
        echo "Builder Type: " . ($sqlPreview['builder_type'] ?? 'N/A') . "\n";
    }
    
    // Show validation
    if (isset($lastMessage->metadata['sql_validation'])) {
        $validation = $lastMessage->metadata['sql_validation'];
        echo "\n--- SQL Validation ---\n";
        echo "Approved: " . ($validation['approved'] ? 'YES' : 'NO') . "\n";
        if (!$validation['approved']) {
            echo "Reason: " . ($validation['reason'] ?? 'Unknown') . "\n";
        }
    }
    
    // Show execution
    if (isset($lastMessage->metadata['query_execution'])) {
        $execution = $lastMessage->metadata['query_execution'];
        echo "\n--- Query Execution ---\n";
        echo "Success: " . ($execution['success'] ? 'YES' : 'NO') . "\n";
        echo "Row Count: " . ($execution['row_count'] ?? 0) . "\n";
        
        if ($execution['success'] && ($execution['row_count'] ?? 0) > 0) {
            echo "Rows:\n";
            foreach ($execution['rows'] ?? [] as $row) {
                echo "  " . json_encode($row) . "\n";
            }
        }
        
        if (!$execution['success']) {
            echo "Error: " . ($execution['error_message'] ?? 'Unknown') . "\n";
        }
    }
    
    echo "\n--- Response ---\n";
    echo $lastMessage->content . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "Trace: {$e->getTraceAsString()}\n";
}

echo "\n--- Expected Query ---\n";
echo "SELECT priority, COUNT(*) as aggregate\n";
echo "FROM service_tickets\n";
echo "WHERE deleted_at IS NULL\n";
echo "GROUP BY priority\n";
echo "LIMIT 100\n";

echo "\n--- Manual Test ---\n";
try {
    $results = DB::select("
        SELECT priority, COUNT(*) as aggregate
        FROM service_tickets
        WHERE deleted_at IS NULL
        GROUP BY priority
        LIMIT 100
    ");
    
    echo "Manual query results:\n";
    foreach ($results as $row) {
        echo "  Priority: {$row->priority}, Count: {$row->aggregate}\n";
    }
} catch (Exception $e) {
    echo "Manual query failed: {$e->getMessage()}\n";
}
