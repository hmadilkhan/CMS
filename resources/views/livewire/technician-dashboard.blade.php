<div>
    @section('title', 'Technician Dashboard')

    <div class="container-fluid">
        <div class="row clearfix g-3">
            <div class="col-lg-12 col-md-12 flex-column">
                <div class="row g-3 row-deck">
                    <!-- Map Section -->
                    <div class="col-xxl-6 col-xl-6 col-lg-12">
                        <div class="card mb-3">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-transparent border-bottom-0">
                                <h6 class="mb-0 fw-bold ">Daily Route Map</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <div id="route-summary"></div>
                                    @if($routeUrl)
                                    <a href="{{ $routeUrl }}" target="_blank" class="btn btn-primary btn-sm btn-sn">
                                        <i class="icofont-google-map me-1"></i> Open Route
                                    </a>
                                    @endif
                                    <span class="badge bg-primary">{{ count($todaySurveys) }} Stops</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="technician-map" style="height: 500px; width: 100%; border-radius: 8px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Widgets / Schedule Section -->
                    <div class="col-xxl-6 col-xl-6 col-lg-12">
                        <div class="card mb-3">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-transparent border-bottom-0">
                                <h6 class="mb-0 fw-bold ">Today's Schedule ({{ \Carbon\Carbon::parse($todayDate)->format('M d, Y') }})</h6>
                            </div>
                            <div class="card-body">
                                @if($todaySurveys->isEmpty())
                                <div class="text-center py-5">
                                    <i class="icofont-ui-calendar fs-1 text-muted"></i>
                                    <p class="mt-3 text-muted">No surveys assigned for today.</p>
                                </div>
                                @else
                                <div class="timeline-item-con">
                                    @foreach($todaySurveys as $index => $survey)
                                    <div class="timeline-item d-flex ps-3 border-start border-3 {{ $loop->last ? 'border-transparent' : 'pb-4' }} {{ $survey->status == 'completed' ? 'border-success' : ($survey->status == 'in_progress' ? 'border-warning' : 'border-primary') }}" style="border-color: var(--primary-color);">
                                        <div class="icon-box me-3">
                                            <span class="avatar rounded-circle bg-light text-primary fw-bold text-center d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                {{ $index + 1 }}
                                            </span>
                                        </div>
                                        <div class="content-box w-100">
                                            <div class="card mb-0 shadow-sm hover-elevate">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="badge {{ $survey->status == 'completed' ? 'bg-success' : ($survey->status == 'in_progress' ? 'bg-warning' : 'bg-primary') }}">
                                                            {{ ucfirst(str_replace('_', ' ', $survey->status)) }}
                                                        </span>
                                                        <small class="text-muted"><i class="icofont-clock-time me-1"></i> {{ \Carbon\Carbon::parse($survey->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($survey->end_time)->format('h:i A') }}</small>
                                                    </div>
                                                    <h6 class="fw-bold mb-1">
                                                        @if($survey->project && $survey->project->customer)
                                                        {{ $survey->project->customer->first_name }} {{ $survey->project->customer->last_name }}
                                                        @else
                                                        Unknown Customer
                                                        @endif
                                                    </h6>
                                                    <p class="text-muted small mb-2"><i class="icofont-location-pin me-1"></i> {{ $survey->customer_address }}</p>

                                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $survey->customer_lat }},{{ $survey->customer_lng }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="icofont-navigate me-1"></i> Navigate
                                                        </a>
                                                        @if($survey->status != 'completed')
                                                        <a href="{{ route('projects.show', $survey->project_id) }}" class="btn btn-sm btn-primary">
                                                            Details <i class="icofont-long-arrow-right ms-1"></i>
                                                        </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Define global init function
        window.initTechnicianMap = async function() {
            // Check if Google Maps is actually loaded
            if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                return; // Wait for callback
            }

            // Import libraries
            const {
                Map
            } = await google.maps.importLibrary("maps");
            const {
                AdvancedMarkerElement,
                PinElement
            } = await google.maps.importLibrary("marker");

            // Safe JSON injection
            const techInfo = @json($techMapData);
            const surveys = @json($todaySurveys);

            const mapOptions = {
                zoom: 12,
                center: {
                    lat: techInfo.lat,
                    lng: techInfo.lng
                },
                mapId: "DEMO_MAP_ID", // Required for AdvancedMarkerElement
                // Styles removed to prevent conflict with mapId
            };

            const mapElement = document.getElementById("technician-map");
            if (!mapElement) return;

            const map = new Map(mapElement, mapOptions);

            const directionsService = new google.maps.DirectionsService();
            const directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: true
            });

            const markers = [];
            const waypoints = [];
            const bounds = new google.maps.LatLngBounds();

            // 1. Add Technician Home Marker (Start)
            const techHome = {
                lat: techInfo.lat,
                lng: techInfo.lng
            };

            if (techInfo.hasLocation) {
                // Create custom pin for Home
                const homePin = new PinElement({
                    glyphText: "H",
                    background: "#4285F4",
                    borderColor: "#ffffff",
                    glyphColor: "#ffffff"
                });

                const homeMarker = new AdvancedMarkerElement({
                    position: techHome,
                    map: map,
                    title: "Home Base",
                    content: homePin.element
                });
                markers.push(homeMarker);
                bounds.extend(techHome);
            }

            // 2. Add Survey Markers
            if (Array.isArray(surveys)) {
                surveys.forEach((survey, index) => {
                    if (!survey.customer_lat || !survey.customer_lng) return;

                    const pos = {
                        lat: parseFloat(survey.customer_lat),
                        lng: parseFloat(survey.customer_lng)
                    };

                    // Customize pin based on status? Or just numbers as requested "like Google Maps app"
                    // Google Maps app just uses numbers for waypoints.
                    const pin = new PinElement({
                        glyphText: (index + 1).toString(),
                        background: "#EA4335", // Google Red
                        borderColor: "#B31412",
                        glyphColor: "#ffffff"
                    });

                    const marker = new AdvancedMarkerElement({
                        position: pos,
                        map: map,
                        title: survey.project?.customer?.first_name || "Job " + (index + 1),
                        content: pin.element
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: `<strong>Job ${index + 1}</strong><br>${survey.project?.customer?.first_name ?? 'Client'}<br>${survey.start_time}`
                    });

                    marker.addListener("click", () => {
                        infoWindow.open(map, marker);
                    });

                    markers.push(marker);
                    bounds.extend(pos);

                    // Add to waypoints for routing
                    waypoints.push({
                        location: pos,
                        stopover: true
                    });
                });
            }

            // 3. Draw Route
            if (markers.length > 0) {
                // For bounds with AdvancedMarker, we accept LatLng or LatLngLiteral
                map.fitBounds(bounds);

                if (waypoints.length > 0) {
                    let origin = techInfo.hasLocation ? techHome : waypoints[0].location;
                    let destination = techInfo.hasLocation ? techHome : waypoints[waypoints.length - 1].location;

                    let routeWaypoints = [...waypoints];
                    if (!techInfo.hasLocation) {
                        routeWaypoints.shift();
                        routeWaypoints.pop();
                    }

                    const request = {
                        origin: origin,
                        destination: destination,
                        waypoints: routeWaypoints,
                        optimizeWaypoints: false,
                        travelMode: google.maps.TravelMode.DRIVING
                    };

                    directionsService.route(request, function(result, status) {
                        if (status == google.maps.DirectionsStatus.OK) {
                            directionsRenderer.setDirections(result);

                            // Calculate Total Distance and Duration
                            let totalDistance = 0;
                            let totalDuration = 0;

                            const route = result.routes[0];
                            for (let i = 0; i < route.legs.length; i++) {
                                totalDistance += route.legs[i].distance.value; // in meters
                                totalDuration += route.legs[i].duration.value; // in seconds
                            }

                            // Convert to readable format
                            const distanceMiles = (totalDistance * 0.000621371).toFixed(1);
                            const durationMinutes = Math.round(totalDuration / 60);
                            const hours = Math.floor(durationMinutes / 60);
                            const minutes = durationMinutes % 60;

                            const durationText = hours > 0 ? `${hours} hr ${minutes} min` : `${minutes} min`;

                            // Update UI
                            const summaryElement = document.getElementById('route-summary');
                            if (summaryElement) {
                                summaryElement.innerHTML = `
                                    <div class="d-flex gap-3 text-muted">
                                        <span><i class="icofont-road me-1"></i> ${distanceMiles} mi</span>
                                        <span><i class="icofont-clock-time me-1"></i> ${durationText} (Travel)</span>
                                    </div>
                                `;
                            }
                        } else {
                            console.error("Directions request failed due to " + status);
                        }
                    });
                }
            }
        };

        // Handle Livewire Navigation
        document.addEventListener('livewire:initialized', function() {
            if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                initTechnicianMap();
            }
        });
    </script>

    <!-- Google Maps Scripts with Callback -->
    <script async
        src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places,marker&loading=async&callback=initTechnicianMap">
    </script>
</div>