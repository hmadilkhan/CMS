@extends('layouts.master')
@section('title', 'Schedule Site Survey')
@section('content')
<style>
    .survey-container {
        background: linear-gradient(135deg, #0d0d0e 0%, #2f2c33 100%);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4);
        margin-bottom: 2rem;
    }
    
    .survey-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .survey-card:hover {
        transform: translateY(-5px);
    }
    
    .info-card {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid #0d0d0e;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        font-size: 0.95rem;
    }
    
    .info-item i {
        font-size: 1.5rem;
        color: #0d0d0e;
        margin-right: 1rem;
        width: 30px;
    }
    
    .info-label {
        font-weight: 600;
        color: #2d3748;
        margin-right: 0.5rem;
    }
    
    .date-selector {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .date-input {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: all 0.3s;
        width: 100%;
    }
    
    .date-input:focus {
        border-color: #0d0d0e;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .search-btn {
        background: linear-gradient(135deg, #0d0d0e 0%, #282829 100%);
        border: none;
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
        color: white;
        font-weight: 600;
        width: 100%;
        margin-top: 1rem;
        transition: all 0.3s;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }
    
    .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    
    .slots-container {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        min-height: 400px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .technician-group {
        margin-bottom: 2rem;
        animation: fadeInUp 0.5s ease;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .tech-header {
        background: linear-gradient(135deg, #1f2020 0%, #1a202c 100%);
        color: white;
        padding: 0.75rem 1.25rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    
    .tech-header i {
        font-size: 1.5rem;
        margin-right: 0.75rem;
    }
    
    .slot-card {
        background: linear-gradient(135deg, #ffffff 0%, #f7fafc 100%);
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.25rem;
        margin: 0.75rem 0;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .slot-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
        transition: left 0.5s;
    }
    
    .slot-card:hover::before {
        left: 100%;
    }
    
    .slot-card:hover {
        border-color: #667eea;
        transform: translateX(5px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
    }
    
    .slot-card.selected {
        border-color: #667eea;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        transform: scale(1.02);
    }
    
    .slot-card.selected .badge {
        background: rgba(255,255,255,0.3) !important;
        color: white !important;
    }
    
    .slot-time {
        font-size: 1.1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
    }
    
    .slot-time i {
        font-size: 1.3rem;
        margin-right: 0.5rem;
    }
    
    .travel-badge {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(72, 187, 120, 0.3);
    }
    
    .distance-badge {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-left: 0.5rem;
        box-shadow: 0 2px 8px rgba(45, 55, 72, 0.3);
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #a0aec0;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
    }
    
    .section-title i {
        font-size: 1.5rem;
        margin-right: 0.75rem;
        color: #667eea;
    }
    
    .loading-container {
        position: relative;
        width: 80px;
        height: 80px;
        margin: 0 auto;
    }
    
    .loading-circle {
        position: absolute;
        width: 100%;
        height: 100%;
        border: 3px solid rgba(102, 126, 234, 0.2);
        border-radius: 50%;
    }
    
    .loading-icon {
        position: absolute;
        width: 24px;
        height: 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        animation: orbit 2s linear infinite;
        top: 50%;
        left: 50%;
        margin-left: -12px;
        margin-top: -12px;
    }
    
    @keyframes orbit {
        0% {
            transform: rotate(0deg) translateX(28px) rotate(0deg);
        }
        100% {
            transform: rotate(360deg) translateX(28px) rotate(-360deg);
        }
    }
    
    .suitability-score {
        position: absolute;
        top: 10px;
        right: 10px;
        background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%);
        color: white;
        padding: 0.3rem 0.6rem;
        border-radius: 15px;
        font-size: 0.75rem;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(237, 137, 54, 0.3);
    }
</style>

<div class="survey-container">
    <div class="text-center text-white mb-4">
        <h2 class="fw-bold mb-2">
            <i class="icofont-calendar me-2"></i>Schedule Site Survey
        </h2>
        <p class="mb-0 opacity-75">Find the perfect time slot for your site survey</p>
    </div>
    
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="survey-card">
                <div class="section-title">
                    <i class="icofont-building"></i>
                    Project Information
                </div>
                
                <div class="info-card">
                    <div class="info-item">
                        <i class="icofont-folder"></i>
                        <div>
                            <span class="info-label">Project:</span>
                            <span>{{ $project->project_name }}</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="icofont-user-alt-4"></i>
                        <div>
                            <span class="info-label">Customer:</span>
                            <span>{{ $project->customer->first_name }} {{ $project->customer->last_name }}</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="icofont-location-pin"></i>
                        <div>
                            <span class="info-label">Address:</span>
                            <span id="customerAddress">{{ $project->customer->street }}, {{ $project->customer->city }}, {{ $project->customer->state }} {{ $project->customer->zipcode }}</span>
                        </div>
                    </div>
                </div>

                @if($existingSurvey)
                <div class="date-selector" style="background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%); border: none; box-shadow: 0 8px 20px rgba(0,0,0,0.3);">
                    <div class="text-center">
                        <div style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; box-shadow: 0 8px 20px rgba(72, 187, 120, 0.4);">
                            <i class="icofont-check-circled" style="font-size: 2.5rem; color: white;"></i>
                        </div>
                        <h5 style="color: white; font-weight: 700; margin-bottom: 1.5rem; font-size: 1.3rem;">Survey Already Scheduled</h5>
                        
                        <div style="background: rgba(255,255,255,0.1); border-radius: 12px; padding: 1.25rem; margin-bottom: 1rem; backdrop-filter: blur(10px);">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <span style="color: rgba(255,255,255,0.7); font-size: 0.9rem;"><i class="icofont-calendar me-2"></i>Date</span>
                                <span style="color: white; font-weight: 600;">{{ \Carbon\Carbon::parse($existingSurvey->survey_date)->format('F d, Y') }}</span>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <span style="color: rgba(255,255,255,0.7); font-size: 0.9rem;"><i class="icofont-clock-time me-2"></i>Time</span>
                                <span style="color: white; font-weight: 600;">{{ $existingSurvey->start_time }} - {{ $existingSurvey->end_time }}</span>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="color: rgba(255,255,255,0.7); font-size: 0.9rem;"><i class="icofont-user-suited me-2"></i>Technician</span>
                                <span style="color: white; font-weight: 600;">{{ $existingSurvey->technician->name }}</span>
                            </div>
                        </div>
                        
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 1rem; margin-top: 1rem; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
                            <i class="icofont-info-circle" style="font-size: 1.2rem; color: white; margin-right: 0.5rem;"></i>
                            <span style="color: white; font-size: 0.9rem; font-weight: 500;">Technician reserved. Awaiting survey completion.</span>
                        </div>
                    </div>
                </div>
                @else
                <div class="date-selector">
                    <div class="section-title">
                        <i class="icofont-calendar"></i>
                        Select Survey Date
                    </div>
                    <input type="date" id="surveyDate" class="date-input" min="{{ date('Y-m-d') }}">
                    <button onclick="loadAvailableSlots()" class="search-btn">
                        <i class="icofont-search me-2"></i>Find Available Technicians
                    </button>
                </div>
                @endif
            </div>
        </div>

        <div class="col-lg-7">
            <div class="survey-card">
                <div class="section-title">
                    <i class="icofont-clock-time"></i>
                    Available Time Slots
                </div>
                
                <div class="slots-container">
                    <div id="availableSlots">
                        <div class="empty-state">
                            <i class="icofont-calendar"></i>
                            <p class="mb-0">Select a date to view available time slots</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedSlot = null;
const projectId = {{ $project->id }};
const customerAddress = document.getElementById('customerAddress').textContent;

async function geocodeAddress(address) {
    const response = await fetch(`https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(address)}&key={{ env('GOOGLE_MAPS_API_KEY') }}`);
    const data = await response.json();
    if (data.results && data.results[0]) {
        return data.results[0].geometry.location;
    }
    return null;
}

async function loadAvailableSlots() {
    const date = document.getElementById('surveyDate').value;
    if (!date) {
        alert('Please select a date');
        return;
    }

    const container = document.getElementById('availableSlots');
    container.innerHTML = `
        <div class="empty-state">
            <div class="loading-container">
                <div class="loading-circle"></div>
                <div class="loading-icon">
                    <i class="icofont-search"></i>
                </div>
            </div>
            <p class="mt-4 mb-0" style="color: #667eea; font-weight: 600;">Finding available technicians...</p>
        </div>`;

    const location = await geocodeAddress(customerAddress);
    if (!location) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="icofont-warning"></i>
                <p class="mb-0">Unable to locate address</p>
            </div>`;
        return;
    }

    const response = await fetch('/site-surveys/available-slots', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            date: date,
            customer_address: customerAddress,
            customer_lat: location.lat,
            customer_lng: location.lng
        })
    });

    const slots = await response.json();
    console.log('API Response:', slots); // Debug log
    displaySlots(slots, location);
}

function displaySlots(technicians, location) {
    const container = document.getElementById('availableSlots');
    
    // Check if technicians is an array
    if (!Array.isArray(technicians)) {
        console.error('Invalid response format:', technicians);
        container.innerHTML = `
            <div class="empty-state">
                <i class="icofont-warning"></i>
                <p class="mb-0">Error loading slots</p>
                <small>Please try again</small>
            </div>`;
        return;
    }
    
    if (technicians.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="icofont-close-circled"></i>
                <p class="mb-0">No available slots found for this date</p>
                <small>Try selecting a different date</small>
            </div>`;
        return;
    }

    let html = '';
    technicians.forEach((tech, index) => {
        html += `<div class="technician-group" style="animation-delay: ${index * 0.1}s">
            <div class="tech-header">
                <i class="icofont-user-suited"></i>
                <div>
                    <div class="fw-bold">${tech.technician.name}</div>
                    <small class="opacity-75">${tech.slots.length} slot${tech.slots.length > 1 ? 's' : ''} available</small>
                </div>
                ${tech.upcoming_nearby ? '<span class="suitability-score"><i class="icofont-star me-1"></i>Nearby Jobs</span>' : ''}
            </div>`;
        
        tech.slots.forEach(slot => {
            html += `<div class="slot-card" onclick="selectSlot(${tech.technician.id}, '${slot.start_time}', '${slot.end_time}', ${slot.travel_time}, ${slot.distance}, ${location.lat}, ${location.lng})">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="slot-time">
                        <i class="icofont-clock-time"></i>
                        ${slot.start_time} - ${slot.end_time}
                    </div>
                    <div>
                        <span class="travel-badge">
                            <i class="icofont-car-alt me-1"></i>${slot.travel_time} min
                        </span>
                        <span class="distance-badge">
                            <i class="icofont-map-pins me-1"></i>${slot.distance} km
                        </span>
                    </div>
                </div>
            </div>`;
        });
        
        html += '</div>';
    });

    container.innerHTML = html;
}

function selectSlot(technicianId, startTime, endTime, travelTime, distance, lat, lng) {
    document.querySelectorAll('.slot-card').forEach(card => card.classList.remove('selected'));
    event.target.closest('.slot-card').classList.add('selected');
    
    selectedSlot = {
        technician_id: technicianId,
        start_time: startTime,
        end_time: endTime,
        travel_time: travelTime,
        distance: distance,
        lat: lat,
        lng: lng
    };

    if (confirm('Schedule this time slot?')) {
        scheduleSurvey();
    }
}

async function scheduleSurvey() {
    if (!selectedSlot) return;

    const date = document.getElementById('surveyDate').value;

    const response = await fetch('/site-surveys/schedule', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            project_id: projectId,
            technician_id: selectedSlot.technician_id,
            survey_date: date,
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
        alert('Site survey scheduled successfully!');
        window.location.href = '/projects/' + projectId;
    }
}
</script>
@endsection
