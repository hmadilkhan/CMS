<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\AiChatService;

echo "=== Employee-Department Query Debug ===\n\n";

// Get admin user
$user = User::whereHas('roles', function ($q) {
    $q->where('name', 'Super Admin');
})->first();

if (!$user) {
    echo "❌ No Super Admin user found\n";
    exit(1);
}

echo "✅ User: {$user->name} (ID: {$user->id})\n\n";

// Check database state
echo "--- Database State ---\n";
$employeeCount = \App\Models\Employee::count();
$departmentCount = \App\Models\Department::count();
$employeeDeptCount = \App\Models\EmployeeDepartment::count();

echo "Employees: {$employeeCount}\n";
echo "Departments: {$departmentCount}\n";
echo "Employee-Department mappings: {$employeeDeptCount}\n\n";

// Sample data
echo "--- Sample Employee-Department Data ---\n";
$sampleEmployees = \App\Models\Employee::with('departments')->limit(3)->get();
foreach ($sampleEmployees as $emp) {
    $deptNames = $emp->departments->pluck('name')->join(', ');
    $deptCount = $emp->departments->count();
    echo "Employee: {$emp->name} | Departments: {$deptCount} | Names: {$deptNames}\n";
}
echo "\n";

// Test query
$question = "Employees ki list show kro or ye bhi btao k kis employee ko kitne departments allowed hain";
echo "--- Testing Query ---\n";
echo "Question: {$question}\n\n";

try {
    $chatService = app(AiChatService::class);
    $chat = $chatService->send($user, $question);
    
    echo "--- Chat Created ---\n";
    echo "Chat ID: {$chat->id}\n";
    echo "Messages Count: " . $chat->messages()->count() . "\n\n";
    
    // Get all messages
    $messages = $chat->messages()->orderBy('id', 'asc')->get();
    
    foreach ($messages as $msg) {
        echo "Message ID: {$msg->id} | Role: {$msg->role}\n";
        if ($msg->role === 'assistant') {
            echo "Content Length: " . strlen($msg->content ?? '') . "\n";
            echo "Has Plan: " . (isset($msg->plan) ? 'Yes' : 'No') . "\n";
            echo "Has SQL: " . (isset($msg->sql) ? 'Yes' : 'No') . "\n";
            echo "Has Execution: " . (isset($msg->execution) ? 'Yes' : 'No') . "\n";
            
            if (isset($msg->plan)) {
                echo "\n--- Plan Details ---\n";
                echo json_encode($msg->plan, JSON_PRETTY_PRINT) . "\n";
            }
            
            if (isset($msg->sql)) {
                echo "\n--- SQL Details ---\n";
                echo json_encode($msg->sql, JSON_PRETTY_PRINT) . "\n";
            }
            
            if (isset($msg->execution)) {
                echo "\n--- Execution Details ---\n";
                echo json_encode($msg->execution, JSON_PRETTY_PRINT) . "\n";
            }
            
            echo "\n--- Assistant Response ---\n";
            echo $msg->content . "\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";
