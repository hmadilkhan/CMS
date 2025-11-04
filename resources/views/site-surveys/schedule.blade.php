@extends('layouts.master')
@section('title', 'Schedule Site Survey')
@section('content')
<style>
    .slot-card {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 15px;
        margin: 10px 0;
        cursor: pointer;
        transition: all 0.3s;
    }
    .slot-card:hover {
        border-color: #2d3748;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .slot-card.selected {
        border-color: #2d3748;
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    }
</style>

<div class="card premium-card">
    <div class="premium-header">
        <h3 class="fw-bold mb-0">
            <i class="icofont-calendar me-2"></i>Schedule Site Survey
        </h3>
    </div>

    <div class="card-body p-4">
        <div class="row">
            <div class="col-md-6">
                <div class="premium-section">
                    <h5 class="fw-bold mb-3"><i class="icofont-info-circle me-2"></i>Project Details</h5>
                    <p><strong>Project:</strong> {{ $project->project_name }}</p>
                    <p><strong>Customer:</strong> {{ $project->customer->first_name }} {{ $project->customer->last_name }}</p>
                    <p><strong>Address:</strong> <span id="customerAddress">{{ $project->customer->street }}, {{ $project->customer->city }}, {{ $project->customer->state }} {{ $project->customer->zipcode }}</span></p>
                </div>

                <div class="premium-section">
                    <h5 class="fw-bold mb-3"><i class="icofont-calendar me-2"></i>Select Date</h5>
                    <input type="date" id="surveyDate" class="form-control premium-input" min="{{ date('Y-m-d') }}">
                    <button onclick="loadAvailableSlots()" class="btn premium-btn mt-3 w-100">
                        <i class="icofont-search me-2"></i>Find Available Slots
                    </button>
                </div>
            </div>

            <div class="col-md-6">
                <div class="premium-section">
                    <h5 class="fw-bold mb-3"><i class="icofont-clock-time me-2"></i>Available Time Slots</h5>
                    <div id="availableSlots">
                        <p class="text-muted text-center">Select a date to view available slots</p>
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

    const location = await geocodeAddress(customerAddress);
    if (!location) {
        alert('Unable to geocode address');
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
    displaySlots(slots, location);
}

function displaySlots(technicians, location) {
    const container = document.getElementById('availableSlots');
    
    if (technicians.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">No available slots for this date</p>';
        return;
    }

    let html = '';
    technicians.forEach(tech => {
        html += `<div class="mb-3">
            <h6 class="fw-bold">${tech.technician.name}</h6>`;
        
        tech.slots.forEach(slot => {
            html += `<div class="slot-card" onclick="selectSlot(${tech.technician.id}, '${slot.start_time}', '${slot.end_time}', ${slot.travel_time}, ${slot.distance}, ${location.lat}, ${location.lng})">
                <div class="d-flex justify-content-between">
                    <span><i class="icofont-clock-time me-2"></i>${slot.start_time} - ${slot.end_time}</span>
                    <span class="badge bg-info">${slot.travel_time} min travel</span>
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
