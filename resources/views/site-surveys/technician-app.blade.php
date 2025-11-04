@extends('layouts.master')
@section('title', 'My Site Surveys')
@section('content')
<style>
    .survey-card {
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        border-left: 5px solid #2d3748;
    }
    .status-badge {
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 600;
    }
</style>

<div class="card premium-card">
    <div class="premium-header">
        <h3 class="fw-bold mb-0">
            <i class="icofont-calendar me-2"></i>My Site Surveys - {{ date('M d, Y') }}
        </h3>
    </div>

    <div class="card-body p-4">
        <div id="surveysContainer"></div>
    </div>
</div>

<script>
let watchId = null;

async function loadSurveys() {
    const response = await fetch('/api/technician/surveys?date={{ date("Y-m-d") }}', {
        headers: {
            'Authorization': 'Bearer {{ auth()->user()->createToken("app")->plainTextToken ?? "" }}'
        }
    });
    const surveys = await response.json();
    displaySurveys(surveys);
}

function displaySurveys(surveys) {
    const container = document.getElementById('surveysContainer');
    
    if (surveys.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">No surveys scheduled for today</p>';
        return;
    }

    let html = '';
    surveys.forEach(survey => {
        const statusColor = survey.status === 'scheduled' ? 'warning' : 'info';
        html += `
            <div class="card survey-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold">${survey.project.project_name}</h5>
                            <p class="mb-1"><i class="icofont-location-pin me-2"></i>${survey.customer_address}</p>
                            <p class="mb-0"><i class="icofont-clock-time me-2"></i>${survey.start_time} - ${survey.end_time}</p>
                        </div>
                        <span class="status-badge bg-${statusColor}">${survey.status}</span>
                    </div>
                    
                    <div class="d-flex gap-2">
                        ${survey.status === 'scheduled' ? 
                            `<button onclick="startSurvey(${survey.id})" class="btn btn-success">
                                <i class="icofont-play me-2"></i>Start Survey
                            </button>` : ''}
                        ${survey.status === 'in_progress' ? 
                            `<button onclick="completeSurvey(${survey.id})" class="btn btn-primary">
                                <i class="icofont-check me-2"></i>Complete Survey
                            </button>` : ''}
                        <button onclick="openMaps(${survey.customer_lat}, ${survey.customer_lng})" class="btn btn-dark">
                            <i class="icofont-navigation me-2"></i>Navigate
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

async function startSurvey(surveyId) {
    if (!confirm('Start this survey?')) return;

    const response = await fetch(`/site-surveys/${surveyId}/start`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    });

    if (response.ok) {
        startLocationTracking(surveyId);
        loadSurveys();
    }
}

async function completeSurvey(surveyId) {
    const notes = prompt('Enter survey notes:');
    if (notes === null) return;

    const response = await fetch(`/site-surveys/${surveyId}/complete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ notes })
    });

    if (response.ok) {
        stopLocationTracking();
        alert('Survey completed successfully!');
        loadSurveys();
    }
}

function startLocationTracking(surveyId) {
    if (navigator.geolocation) {
        watchId = navigator.geolocation.watchPosition(
            position => updateLocation(surveyId, position.coords.latitude, position.coords.longitude),
            error => console.error('Location error:', error),
            { enableHighAccuracy: true, maximumAge: 10000, timeout: 5000 }
        );
    }
}

function stopLocationTracking() {
    if (watchId) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }
}

async function updateLocation(surveyId, lat, lng) {
    await fetch('/site-surveys/update-location', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ survey_id: surveyId, lat, lng })
    });
}

function openMaps(lat, lng) {
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
}

loadSurveys();
setInterval(loadSurveys, 60000);
</script>
@endsection
