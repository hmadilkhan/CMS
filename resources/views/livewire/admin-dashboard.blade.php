<div>
    <style>
        .admin-dashboard-page {
            color: #050505;
        }

        .admin-dashboard-page .premium-tabs {
            gap: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .admin-dashboard-page .premium-tabs .nav-link {
            border: none;
            color: #050505 !important;
            font-weight: 600;
            padding: 0 0 0.85rem;
            border-radius: 0;
            transition: all 0.3s ease;
            background: transparent !important;
            margin-right: 0;
            border-bottom: 2px solid transparent;
        }
        .admin-dashboard-page .premium-tabs .nav-link:hover {
            background: transparent !important;
            color: #050505 !important;
        }
        .admin-dashboard-page .premium-tabs .nav-link.active {
            background: transparent !important;
            color: #F19828 !important;
            box-shadow: none !important;
            border-bottom-color: #F19828;
        }
        .admin-dashboard-page .premium-header {
            background: transparent !important;
            padding: 0 0 1rem;
            border-radius: 0;
            margin-bottom: 1.5rem;
            box-shadow: none !important;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
        }
        .admin-dashboard-page .premium-header::before {
            content: none;
        }
        .admin-dashboard-page .premium-header h1 {
            color: #050505 !important;
            font-size: 1.65rem;
            font-weight: 700;
            margin: 0;
            text-shadow: none;
        }
        .admin-dashboard-page .dashboard-widget {
            height: 100%;
        }
        .admin-dashboard-page .dashboard-widget .card {
            border-radius: 8px;
            border: 1px solid #e5e7eb !important;
            box-shadow: none !important;
            transition: all 0.3s ease;
            height: 100%;
            background: #ffffff !important;
        }
        .admin-dashboard-page .dashboard-widget .card:hover {
            transform: none;
            box-shadow: 0 8px 22px rgba(5,5,5,0.06) !important;
        }
        .admin-dashboard-page .dashboard-widget .card-header {
            background: #ffffff !important;
            color: #050505 !important;
            border-radius: 8px 8px 0 0 !important;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e5e7eb !important;
        }
        .admin-dashboard-page .dashboard-widget .card-header *,
        .admin-dashboard-page .dashboard-widget .card-title,
        .admin-dashboard-page .dashboard-widget h1,
        .admin-dashboard-page .dashboard-widget h2,
        .admin-dashboard-page .dashboard-widget h3,
        .admin-dashboard-page .dashboard-widget h4,
        .admin-dashboard-page .dashboard-widget h5,
        .admin-dashboard-page .dashboard-widget h6 {
            color: #050505 !important;
        }
        .admin-dashboard-page .dashboard-widget .card-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin: 0;
        }
        .admin-dashboard-page .dashboard-widget .card-body {
            padding: 1.25rem;
        }
        .admin-dashboard-page .premium-filter-card {
            background: #ffffff !important;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            box-shadow: none !important;
            border: 1px solid #e5e7eb;
        }
        .admin-dashboard-page .premium-filter-card h5 {
            color: #050505 !important;
            font-weight: 700;
            margin: 0;
            font-size: 1rem;
        }
        .admin-dashboard-page .filter-input-group {
            background: #f8fafc;
            backdrop-filter: none;
            padding: 0.55rem 0.75rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        .admin-dashboard-page .filter-input-group:hover {
            background: #ffffff;
            border-color: #cbd5e1;
        }
        .admin-dashboard-page .filter-input-group label {
            color: #475569;
            font-weight: 600;
            margin: 0;
            font-size: 0.9rem;
        }
        .admin-dashboard-page .filter-input-group input {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-weight: 500;
            box-shadow: none;
        }
        .admin-dashboard-page .filter-input-group input:focus {
            outline: none;
            border-color: #1d4ed8;
            box-shadow: 0 0 0 0.15rem rgba(29,78,216,0.12);
        }
        .admin-dashboard-page .premium-apply-btn {
            background: var(--solen-gradient, linear-gradient(135deg, #ffc18f 0%, #ee8f45 56%, #c8642d 100%)) !important;
            color: #ffffff !important;
            border: none;
            padding: 0.65rem 1.25rem;
            border-radius: 8px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 6px 16px rgba(238, 143, 69, 0.22);
        }
        .admin-dashboard-page .premium-apply-btn:hover {
            transform: none;
            box-shadow: 0 8px 20px rgba(238, 143, 69, 0.3);
            background: var(--solen-gradient, linear-gradient(135deg, #ffc18f 0%, #ee8f45 56%, #c8642d 100%)) !important;
            color: #ffffff !important;
        }

        .admin-dashboard-page .badge.bg-danger {
            background: #1d4ed8 !important;
        }

        @media (max-width: 991px) {
            .admin-dashboard-page .premium-filter-card .d-flex,
            .admin-dashboard-page .premium-filter-card form {
                align-items: stretch !important;
                flex-direction: column;
            }
        }
    </style>
    <div class="container-xxl admin-dashboard-page">
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
                            <livewire:pto-approval-chart :startDate="$startDate" :endDate="$endDate" :key="'pto-approval-' . time()" />
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
                            <livewire:new-projects-card :startDate="$startDate" :endDate="$endDate" :key="'new-projects-' . time()" />
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
