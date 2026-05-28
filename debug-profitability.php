<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test profitability report query with date filter
$query = "Profitability report show kro from 1st April 2026 to 30th April 2026";

$user = \App\Models\User::first();

$planner = app(\App\Services\AiQueryPlannerService::class);
$result = $planner->plan($query, $user);

echo "=== QUERY PLAN ===\n";
echo json_encode($result['plan'], JSON_PRETTY_PRINT) . "\n\n";

$builder = app(\App\Services\AiSqlBuilderService::class);
$sql = $builder->build($result['plan'], $user);

echo "=== SQL QUERY ===\n";
echo $sql['sql'] . "\n\n";
echo "=== BINDINGS ===\n";
print_r($sql['bindings']);
echo "\n";

// Execute the query
try {
    $results = DB::select($sql['sql'], $sql['bindings']);
    echo "=== RESULTS ===\n";
    echo "Count: " . count($results) . "\n";
    if (count($results) > 0) {
        print_r($results);
    } else {
        echo "No results found\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Check if there's any data in project_acceptances table
echo "\n=== PROJECT ACCEPTANCES DATA ===\n";
$acceptances = DB::table('project_acceptances')
    ->select('id', 'project_id', 'status', 'approved_date', 'created_at')
    ->limit(5)
    ->get();
echo "Total records: " . DB::table('project_acceptances')->count() . "\n";
echo "Sample records:\n";
print_r($acceptances->toArray());
