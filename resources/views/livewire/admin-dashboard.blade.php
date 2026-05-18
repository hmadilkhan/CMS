<div>
    <style>
        .premium-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 12px 12px 0 0;
            transition: all 0.3s ease;
            background: #f8f9fa;
            margin-right: 0.5rem;
        }
        .premium-tabs .nav-link:hover {
            background: #e9ecef;
            color: #2c3e50;
        }
        .premium-tabs .nav-link.active {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .premium-header {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }
        .premium-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        .premium-header h1 {
            color: #fff;
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .dashboard-widget {
            height: 100%;
        }
        .dashboard-widget .card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
        }
        .dashboard-widget .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        .dashboard-widget .card-header {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            color: white;
            border-radius: 16px 16px 0 0 !important;
            padding: 1.25rem 1.5rem;
            border: none;
        }
        .dashboard-widget .card-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin: 0;
        }
        .dashboard-widget .card-body {
            padding: 1.5rem;
        }
        .premium-filter-card {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            border-radius: 16px;
            padding: 1.5rem 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            border: none;
        }
        .premium-filter-card h5 {
            color: white;
            font-weight: 700;
            margin: 0;
            font-size: 1.2rem;
        }
        .filter-input-group {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            border: 2px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
        }
        .filter-input-group:hover {
            background: rgba(255,255,255,0.15);
            border-color: rgba(255,255,255,0.3);
        }
        .filter-input-group label {
            color: rgba(255,255,255,0.9);
            font-weight: 600;
            margin: 0;
            font-size: 0.9rem;
        }
        .filter-input-group input {
            background: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .filter-input-group input:focus {
            outline: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .premium-apply-btn {
            background: white;
            color: #2c3e50;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255,255,255,0.3);
        }
        .premium-apply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,255,255,0.4);
            background: #f8f9fa;
            color: #000;
        }
    </style>
    <div class="container-xxl">
        <div class="premium-header">
            <h1><i class="icofont-dashboard me-3"></i>Admin Dashboard</h1>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <ul class="nav nav-tabs premium-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#dashboard-tab" role="tab">
                            <i class="icofont-chart-bar-graph me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#follow-ups-tab" role="tab">
                            <i class="icofont-calendar me-2"></i>Follow Ups
                            @if(count($followUps) > 0)
                                <span class="badge bg-danger ms-1">{{ count($followUps) }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#service-tickets-tab" role="tab">
                            <i class="icofont-ticket me-2"></i>Service Tickets
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="dashboard-tab" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="premium-filter-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5><i class="icofont-filter me-2"></i>Date Range Filter</h5>
                                <form wire:submit.prevent="updateDates" class="d-flex align-items-center gap-3">
                                    <div class="filter-input-group d-flex align-items-center gap-2">
                                        <label for="startDate"><i class="icofont-calendar me-1"></i>From:</label>
                                        <input type="date" class="form-control" id="startDate"
                                            wire:model="startDate" style="width: 160px;">
                                    </div>
                                    <div class="filter-input-group d-flex align-items-center gap-2">
                                        <label for="endDate"><i class="icofont-calendar me-1"></i>To:</label>
                                        <input type="date" class="form-control" id="endDate"
                                            wire:model="endDate" style="width: 160px;">
                                    </div>
                                    <button type="submit" class="premium-apply-btn">
                                        <i class="icofont-check-circled me-2"></i>Apply Filter
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-4 mt-2">
                    <div class="col-md-4">
                        <div class="dashboard-widget">
                            <livewire:new-projects-card :startDate="$startDate" :endDate="$endDate" :key="'new-projects-' . time()" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-widget">
                            <livewire:department-time-chart :startDate="$startDate" :endDate="$endDate" :key="'department-time-' . time()" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-widget">
                            <livewire:installation-chart :startDate="$startDate" :endDate="$endDate" :key="'installation-' . time()" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-widget">
                            <livewire:pto-approval-chart :startDate="$startDate" :endDate="$endDate" :key="'pto-approval-' . time()" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-widget">
                            <livewire:dashboard.stats-cards :startDate="$startDate" :endDate="$endDate" :key="'stats-cards-' . time()" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-widget">
                            <livewire:dashboard.widgets-cards :startDate="$startDate" :endDate="$endDate" :key="'widget-cards-' . time()" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="follow-ups-tab" role="tabpanel">
                <div class="dashboard-widget">
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <div class="info-header">
                                <h6 class="mb-0 fw-bold">
                                    <i class="icofont-calendar me-2"></i>Follow Up Tasks
                                </h6>
                            </div>
                            <span class="badge bg-light text-primary rounded-pill">{{ count($followUps) }}</span>
                        </div>
                        <div class="card-body p-0">
                            @if(count($followUps) > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" style="width:100%">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="border-0 fw-semibold text-muted">Project</th>
                                                <th class="border-0 fw-semibold text-muted">Notes</th>
                                                <th class="border-0 fw-semibold text-muted">Follow Up Date</th>
                                                <th class="border-0 fw-semibold text-muted">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($followUps as $followUp)
                                                <tr class="border-bottom">
                                                    <td class="py-3">
                                                        <a href="{{ route('projects.show', $followUp->project->id) }}"
                                                           class="text-decoration-none fw-semibold text-primary hover-underline">
                                                            {{ $followUp->project->project_name }}
                                                        </a>
                                                        <div class="small text-muted mt-1">
                                                            {{ optional($followUp->project->customer)->first_name }}
                                                            {{ optional($followUp->project->customer)->last_name }}
                                                        </div>
                                                    </td>
                                                    <td class="py-3">
                                                        <div class="text-truncate" style="max-width: 240px;" title="{{ $followUp->notes }}">
                                                            {{ $followUp->notes }}
                                                        </div>
                                                    </td>
                                                    <td class="py-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="icofont-calendar text-muted me-2"></i>
                                                            <span class="{{ \Carbon\Carbon::parse($followUp->follow_up_date)->isPast() ? 'text-danger fw-semibold' : 'text-dark' }}">
                                                                {{ \Carbon\Carbon::parse($followUp->follow_up_date)->format('M d, Y') }}
                                                            </span>
                                                        </div>
                                                        @if(\Carbon\Carbon::parse($followUp->follow_up_date)->isToday())
                                                            <small class="text-warning">Due Today</small>
                                                        @elseif(\Carbon\Carbon::parse($followUp->follow_up_date)->isPast())
                                                            <small class="text-danger">Overdue</small>
                                                        @endif
                                                    </td>
                                                    <td class="py-3">
                                                        <select class="form-select form-select-sm admin-followup-status-select"
                                                                data-followup-id="{{ $followUp->id }}"
                                                                style="width: auto; min-width: 100px;">
                                                            <option value="Pending" selected>Pending</option>
                                                            <option value="Resolved">Resolved</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="icofont-calendar text-muted" style="font-size: 3rem;"></i>
                                    <h6 class="text-muted mt-3">No follow-up tasks scheduled</h6>
                                    <p class="text-muted small">Follow-up tasks assigned to you will appear here.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="service-tickets-tab" role="tabpanel">
                @include('service-tickets.admin-dashboard-content')
            </div>
        </div>
    </div>

    <script>
        $(document).off('change', '.admin-followup-status-select').on('change', '.admin-followup-status-select', function() {
            const selectElement = $(this);
            const newStatus = selectElement.val();

            $.ajax({
                url: '{{ route("followup.status.update") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    followup_id: selectElement.data('followup-id'),
                    status: newStatus
                },
                success: function(response) {
                    if (response.status === 200 && newStatus === 'Resolved') {
                        location.reload();
                    }
                },
                error: function() {
                    selectElement.val('Pending');
                    Swal.fire('Error!', 'Failed to update follow-up status.', 'error');
                }
            });
        });
    </script>
</div>
