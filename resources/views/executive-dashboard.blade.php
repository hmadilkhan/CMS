@extends("layouts.master")
@section("content")
<style>
    .dashboard-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 600;
        padding: 1rem 2rem;
        border-radius: 12px 12px 0 0;
        transition: all 0.3s ease;
        background: #f8f9fa;
        margin-right: 0.5rem;
    }
    .dashboard-tabs .nav-link:hover {
        background: #e9ecef;
        color: #2c3e50;
    }
    .dashboard-tabs .nav-link.active {
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        color: white;
    }
</style>
<div class="container-xxl">
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs dashboard-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#dashboard" role="tab">
                        <i class="icofont-dashboard me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#service-tickets" role="tab">
                        <i class="icofont-ticket me-2"></i>Service Tickets
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
            <div class="row g-4">
                <div class="col-md-4">
                    <livewire:new-projects-card />
                </div>
                <div class="col-md-4">
                    <livewire:department-time-chart :key="1" />
                </div>
                <div class="col-md-4">
                    <livewire:installation-chart :key="2" />
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="service-tickets" role="tabpanel">
            @include('service-tickets.admin-dashboard-content')
        </div>
    </div>
</div>
@endsection
