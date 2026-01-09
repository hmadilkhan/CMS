<?php

use App\Models\User;
use App\Models\Project;
use App\Models\Customer;
use App\Models\SiteSurvey;
use Carbon\Carbon;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$techId = 74;
$technician = User::find($techId);

if (!$technician) {
    echo "User $techId not found!\n";
    exit(1);
}

echo "Generating schedule for {$technician->name} (ID: $techId) for TOMORROW...\n";

// Coordinates roughly around Atlanta
$locations = [
    [
        'lat' => 33.7490,
        'lng' => -84.3880,
        'address' => 'Downtown Atlanta, GA',
        'desc' => 'Downtown Inspection'
    ],
    [
        'lat' => 33.7850,
        'lng' => -84.3850,
        'address' => 'Midtown Atlanta, GA',
        'desc' => 'Midtown Survey'
    ],
    [
        'lat' => 33.8500,
        'lng' => -84.3600,
        'address' => 'Buckhead, Atlanta, GA',
        'desc' => 'Buckhead Residential'
    ],
    [
        'lat' => 33.7600,
        'lng' => -84.3000,
        'address' => 'Decatur, GA',
        'desc' => 'Decatur Commercial'
    ],
];

$tomorrow = Carbon::tomorrow()->format('Y-m-d');
$startTime = Carbon::now()->tomorrow()->setHour(9)->setMinute(0)->setSecond(0);

foreach ($locations as $index => $loc) {
    // 1. Create Dummy Customer
    // Use try-catch or checks to avoid duplicating if running multiple times? 
    // For test data, we'll just create new ones to ensure clean linkage.
    $customer = Customer::create([
        'first_name' => 'AutoTest',
        'last_name' => 'Client ' . ($index + 1),
        'email' => 'auto_test_' . uniqid() . '@example.com',
        'phone' => '555-010-' . str_pad($index, 4, '0', STR_PAD_LEFT),
        'street' => $loc['address'],
        'city' => 'Atlanta',
        'state' => 'GA',
        'zipcode' => '30301',
        // Add other required fields with dummy data if needed
    ]);

    // 2. Create Dummy Project
    $project = Project::create([
        'customer_id' => $customer->id,
        'project_name' => $loc['desc'] . ' - Tomorrow',
        'description' => 'Generated test project for map route verification.'
    ]);

    // 3. Create Site Survey
    $surveyEndTime = $startTime->copy()->addHour();

    $survey = SiteSurvey::create([
        'project_id' => $project->id,
        'technician_id' => $technician->id,
        'survey_date' => $tomorrow,
        'start_time' => $startTime->format('H:i:s'),
        'end_time' => $surveyEndTime->format('H:i:s'),
        'status' => 'scheduled',
        'customer_address' => $loc['address'],
        'customer_lat' => $loc['lat'],
        'customer_lng' => $loc['lng'],
    ]);

    echo "[$index] Scheduled: {$loc['desc']} at {$startTime->format('g:i A')}\n";

    // Add travel buffer (e.g., 2 hours between start times)
    $startTime->addHours(2);
}

echo "\nSuccess! Generated " . count($locations) . " surveys for tomorrow ($tomorrow).\n";
