<?php

/**
 * Debug Script - Test "My Projects" Query
 * 
 * Run: php debug-my-projects.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AiChatService;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\Employee;

echo "=== Debug: 'My Projects' Query ===\n\n";

// Get test user
$user = User::first();

if (!$user) {
    echo "❌ No user found in database.\n";
    exit(1);
}

echo "User: {$user->name} (ID: {$user->id})\n";
echo "Email: {$user->email}\n";
echo "Roles: " . $user->roles->pluck('name')->join(', ') . "\n\n";

// Check user's data
echo "--- Database Check ---\n";

// Total projects
$totalProjects = Project::whereNull('deleted_at')->count();
echo "Total Projects in DB: $totalProjects\n";

// Check if user is employee
$employee = Employee::where('user_id', $user->id)->first();
if ($employee) {
    echo "User is Employee (ID: {$employee->id})\n";
    
    // Projects assigned via tasks
    $assignedProjects = Task::where('employee_id', $employee->id)
        ->distinct('project_id')
        ->count('project_id');
    echo "Projects assigned to this employee: $assignedProjects\n";
} else {
    echo "User is NOT an employee\n";
}

// Check sales partner projects
$salesProjects = Project::where('sales_partner_user_id', $user->id)
    ->whereNull('deleted_at')
    ->count();
echo "Projects as Sales Partner: $salesProjects\n";

// Check sub-contractor projects
$subContractorProjects = Project::where('sub_contractor_user_id', $user->id)
    ->whereNull('deleted_at')
    ->count();
echo "Projects as Sub-Contractor: $subContractorProjects\n\n";

// Test AI query
echo "--- Testing AI Query ---\n";
$service = app(AiChatService::class);

$testQuestions = [
    'How many projects assigned to me?',
    'My projects',
    'Show my projects',
    'Projects assigned to me',
];

foreach ($testQuestions as $question) {
    echo "\nQuestion: \"$question\"\n";
    
    try {
        $chat = $service->send($user, $question);
        $lastMessage = $chat->messages->last();
        
        $status = $lastMessage->metadata['status'] ?? 'unknown';
        $intent = $lastMessage->metadata['query_plan']['intent'] ?? 'N/A';
        
        echo "Status: $status\n";
        echo "Intent: $intent\n";
        
        // Show plan
        if (isset($lastMessage->metadata['query_plan'])) {
            $plan = $lastMessage->metadata['query_plan'];
            echo "Tables: " . json_encode($plan['tables'] ?? []) . "\n";
            echo "Filters: " . json_encode($plan['filters'] ?? []) . "\n";
        }
        
        // Show SQL
        if (isset($lastMessage->metadata['sql_preview']['sql'])) {
            $sql = $lastMessage->metadata['sql_preview']['sql'];
            echo "SQL: " . substr($sql, 0, 200) . "...\n";
            
            $bindings = $lastMessage->metadata['sql_preview']['bindings'] ?? [];
            echo "Bindings: " . json_encode($bindings) . "\n";
        }
        
        // Show result
        if ($status === 'success') {
            $rowCount = $lastMessage->metadata['query_execution']['row_count'] ?? 0;
            echo "✅ Result: $rowCount rows\n";
            echo "Response: " . substr($lastMessage->content, 0, 100) . "\n";
        } else {
            echo "❌ Failed\n";
            echo "Response: $lastMessage->content\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }
}

echo "\n=== Recommendations ===\n";

if ($totalProjects === 0) {
    echo "⚠️  No projects in database. Add some test data.\n";
}

if (!$employee && $salesProjects === 0 && $subContractorProjects === 0) {
    echo "⚠️  User has no assigned projects. Assign some projects to test.\n";
}

if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
    echo "ℹ️  User is Admin - will see all projects (no filtering).\n";
}

echo "\n=== SQL Query Template ===\n";
echo "For Employee role, expected SQL:\n";
echo "SELECT COUNT(*) as aggregate\n";
echo "FROM projects\n";
echo "LEFT JOIN tasks ON tasks.project_id = projects.id\n";
echo "LEFT JOIN employees ON tasks.employee_id = employees.id\n";
echo "WHERE employees.user_id = {$user->id}\n";
echo "AND projects.deleted_at IS NULL\n";
echo "LIMIT 100\n";
