@extends('layouts.master')
@section('title', 'Schedule Site Survey')
@section('content')
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --dark-gradient: linear-gradient(135deg, #1f2020 0%, #1a202c 100%);
        --glass-bg: rgba(255, 255, 255, 0.95);
        --glass-border: 1px solid rgba(255, 255, 255, 0.2);
        --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.1);
        --shadow-md: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .survey-container {
        background: #f8fafc;
        min-height: 100vh;
        padding: 2rem;
    }

    .page-header {
        background: var(--dark-gradient);
        border-radius: 24px;
        padding: 3rem 2rem;
        margin-bottom: 3rem;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-lg);
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('data:image/svg+xml,<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><circle cx="2" cy="2" r="2" fill="rgba(255,255,255,0.05)"/></svg>');
        mask-image: linear-gradient(to bottom, black, transparent);
    }

    .header-content {
        position: relative;
        z-index: 1;
        text-align: center;
        color: white;
    }

    .header-title {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
        background: linear-gradient(to right, #fff, #a5b4fc);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .main-grid {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    .info-panel {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: var(--shadow-md);
        height: fit-content;
        position: sticky;
        top: 2rem;
    }

    .panel-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1a202c;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .info-row {
        margin-bottom: 1.25rem;
        padding-bottom: 1.25rem;
        border-bottom: 1px solid #edf2f7;
    }

    .info-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .info-label {
        font-size: 0.85rem;
        color: #718096;
        margin-bottom: 0.25rem;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
    }

    .info-value {
        font-size: 1rem;
        color: #2d3748;
        font-weight: 500;
    }

    .action-btn {
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 1rem 2rem;
        font-weight: 600;
        width: 100%;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        font-size: 1.1rem;
        cursor: pointer;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
    }

    .action-btn:active {
        transform: translateY(0);
    }

    .results-area {
        min-height: 500px;
    }

    .technician-card {
        background: white;
        border-radius: 20px;
        padding: 0;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-md);
        overflow: hidden;
        animation: slideUp 0.5s ease forwards;
        opacity: 0;
        transform: translateY(20px);
    }

    .tech-header {
        background: #f8fafc;
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #edf2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .tech-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .tech-avatar {
        width: 50px;
        height: 50px;
        background: var(--primary-gradient);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .tech-name {
        font-weight: 700;
        font-size: 1.1rem;
        color: #2d3748;
    }

    .tech-score {
        background: #ebf8ff;
        color: #3182ce;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .dates-grid {
        padding: 2rem;
        display: grid;
        gap: 2rem;
    }

    .date-group {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.5rem;
    }

    .date-header {
        font-weight: 700;
        color: #4a5568;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.1rem;
    }

    .slots-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }

    .time-slot {
        background: white;
        border: 2px solid #edf2f7;
        border-radius: 12px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }

    .time-slot:hover {
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
    }

    .time-slot.selected {
        background: var(--primary-gradient);
        border-color: transparent;
        color: white;
    }

    .time-slot.selected .slot-meta {
        color: rgba(255, 255, 255, 0.9);
    }

    .slot-time {
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        display: block;
    }

    .slot-meta {
        font-size: 0.85rem;
        color: #718096;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    @keyframes slideUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .loading-state {
        text-align: center;
        padding: 4rem;
        color: #718096;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #e2e8f0;
        border-top-color: #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 1.5rem;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .empty-state {
        text-align: center;
        padding: 4rem;
        background: white;
        border-radius: 20px;
        box-shadow: var(--shadow-md);
    }

    .empty-icon {
        font-size: 4rem;
        color: #cbd5e0;
        margin-bottom: 1.5rem;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .main-grid {
            grid-template-columns: 1fr;
        }
        .info-panel {
            position: static;
        }
    }
</style>

<div class="survey-container">
    <div class="page-header">
        <div class="header-content">
            <h1 class="header-title">Schedule Site Survey</h1>
            <p class="opacity-75">AI-Powered Technician Matching & Route Optimization</p>
        </div>
    </div>

    <div class="main-grid">
        <!-- Left Panel: Project Info -->
        <div class="info-panel">
            <div class="panel-title">
                <i class="icofont-building-alt" style="color: #667eea;"></i>
                Project Details
            </div>

            <div class="info-row">
                <span class="info-label">Project Name</span>
                <div class="info-value">{{ $project->project_name }}</div>
            </div>

            <div class="info-row">
                <span class="info-label">Customer</span>
                <div class="info-value">{{ $project->customer->first_name }} {{ $project->customer->last_name }}</div>
            </div>

            <div class="info-row">
                <span class="info-label">Location</span>
                <div class="info-value" id="customerAddress">
                    {{ $project->customer->street }}, {{ $project->customer->city }}, {{ $project->customer->state }} {{ $project->customer->zipcode }}
                </div>
            </div>

            @if($existingSurvey)
                <div class="mt-4 p-4 rounded-xl bg-green-50 border border-green-100">
                    <div class="flex items-center gap-3 mb-2 text-green-700 font-bold">
                        <i class="icofont-check-circled text-xl"></i>
                        Survey Scheduled
                    </div>
                    <div class="text-sm text-green-600">
                        {{ \Carbon\Carbon::parse($existingSurvey->survey_date)->format('F d, Y') }}<br>
                        {{ $existingSurvey->start_time }} - {{ $existingSurvey->end_time }}
                    </div>
                </div>
            @else
                <button onclick="findTechnicians()" class="action-btn mt-4" id="findBtn">
                    <i class="icofont-search-job"></i>
                    Find Technicians
                </button>
            @endif
        </div>

        <!-- Right Panel: Results -->
        <div class="results-area" id="resultsArea">
            <div class="empty-state">
                <i class="icofont-calendar empty-icon"></i>
                <h3 class="font-bold text-gray-700 mb-2">Ready to Schedule</h3>
                <p class="text-gray-500">Click "Find Technicians" to search for the best available slots over the next 7 days.</p>
            </div>
        </div>
    </div>
</div>

<script>
let selectedSlot = null;
const projectId = {{ $project->id }};
const customerAddress = document.getElementById('customerAddress').innerText.trim();

async function geocodeAddress(address) {
    try {
        const response = await fetch(`https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(address)}&key={{ env('GOOGLE_MAPS_API_KEY') }}`);
        const data = await response.json();
        if (data.results && data.results[0]) {
            return data.results[0].geometry.location;
        }
    } catch (e) {
        console.error('Geocoding error:', e);
    }
    return null;
}

async function findTechnicians() {
    const container = document.getElementById('resultsArea');
    const btn = document.getElementById('findBtn');
    
    // UI Loading State
    btn.disabled = true;
    btn.innerHTML = '<i class="icofont-spinner-alt-4 fa-spin"></i> Searching...';
    
    container.innerHTML = `
        <div class="loading-state">
            <div class="spinner"></div>
            <h4 class="font-bold text-gray-700">Analyzing Schedules...</h4>
            <p class="text-gray-500 mt-2">Checking availability and travel times for all technicians</p>
        </div>
    `;

    try {
        const location = await geocodeAddress(customerAddress);
        
        if (!location) {
            throw new Error('Could not locate customer address');
        }

        const response = await fetch('/site-surveys/available-slots', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                customer_address: customerAddress,
                customer_lat: location.lat,
                customer_lng: location.lng
            })
        });

        const data = await response.json();
        displayResults(data, location);

    } catch (error) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="icofont-warning-alt empty-icon text-red-400"></i>
                <h3 class="font-bold text-gray-700">Search Failed</h3>
                <p class="text-gray-500">${error.message}</p>
                <button onclick="findTechnicians()" class="mt-4 text-blue-600 font-semibold hover:underline">Try Again</button>
            </div>
        `;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="icofont-search-job"></i> Find Technicians';
    }
}

function displayResults(technicians, location) {
    const container = document.getElementById('resultsArea');
    
    if (!technicians || technicians.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="icofont-sad empty-icon"></i>
                <h3 class="font-bold text-gray-700">No Slots Available</h3>
                <p class="text-gray-500">We couldn't find any technicians who can reach the site and return home within the 1-hour limit over the next 7 days.</p>
            </div>
        `;
        return;
    }

    let html = '';
    
    technicians.forEach((techData, index) => {
        const tech = techData.technician;
        
        html += `
            <div class="technician-card" style="animation-delay: ${index * 0.1}s">
                <div class="tech-header">
                    <div class="tech-info">
                        <div class="tech-avatar">
                            <i class="icofont-user-suited"></i>
                        </div>
                        <div>
                            <div class="tech-name">${tech.name}</div>
                            <div class="text-sm text-gray-500">
                                <i class="icofont-star text-yellow-500"></i> Match Score: ${techData.total_score}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="dates-grid">
        `;

        techData.dates.forEach(dateGroup => {
            const dateObj = new Date(dateGroup.date);
            const dateStr = dateObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
            
            html += `
                <div class="date-group">
                    <div class="date-header">
                        <i class="icofont-calendar text-blue-500"></i>
                        ${dateStr}
                    </div>
                    <div class="slots-grid">
            `;

            dateGroup.slots.forEach(slot => {
                html += `
                    <div class="time-slot" onclick="selectSlot(this, ${tech.id}, '${dateGroup.date}', '${slot.start_time}', '${slot.end_time}', ${slot.travel_time}, ${slot.distance}, ${location.lat}, ${location.lng})">
                        <span class="slot-time">${slot.start_time} - ${slot.end_time}</span>
                        <div class="slot-meta">
                            <i class="icofont-car-alt-1"></i> ${slot.travel_time} min
                        </div>
                        <div class="slot-meta">
                            <i class="icofont-map-pins"></i> ${slot.distance} mi
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function selectSlot(element, techId, date, start, end, travel, dist, lat, lng) {
    // Deselect all
    document.querySelectorAll('.time-slot').forEach(el => el.classList.remove('selected'));
    
    // Select clicked
    element.classList.add('selected');
    
    selectedSlot = {
        technician_id: techId,
        survey_date: date,
        start_time: start,
        end_time: end,
        travel_time: travel,
        distance: dist,
        lat: lat,
        lng: lng
    };

    if(confirm(`Schedule survey for ${date} at ${start}?`)) {
        submitSchedule();
    }
}

async function submitSchedule() {
    if (!selectedSlot) return;

    try {
        const response = await fetch('/site-surveys/schedule', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                project_id: projectId,
                technician_id: selectedSlot.technician_id,
                survey_date: selectedSlot.survey_date,
                start_time: selectedSlot.start_time,
                end_time: selectedSlot.end_time,
                customer_address: customerAddress,
                customer_lat: selectedSlot.lat,
                customer_lng: selectedSlot.lng,
                estimated_travel_time: selectedSlot.travel_time,
                estimated_distance: selectedSlot.distance
            })
        });

        const result = await response.json();
        
        if (result.success) {
            // Show success animation or toast
            const container = document.getElementById('resultsArea');
            container.innerHTML = `
                <div class="empty-state">
                    <div style="width: 80px; height: 80px; background: #48bb78; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
                        <i class="icofont-check text-white text-4xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-2xl mb-2">Scheduled Successfully!</h3>
                    <p class="text-gray-500 mb-6">The technician has been notified.</p>
                    <a href="/projects/${projectId}" class="action-btn" style="max-width: 200px; margin: 0 auto;">Return to Project</a>
                </div>
            `;
        } else {
            alert(result.message || 'Failed to schedule survey');
        }
    } catch (e) {
        alert('An error occurred while scheduling');
    }
}
</script>
@endsection
