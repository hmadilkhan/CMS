<div class="card shadow-sm h-100">
    <div class="card-header bg-white border-0">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1">New Projects This Month</h5>
                <p class="text-muted small mb-0">Sales Partner Performance</p>
            </div>
            <div class="text-end">
                <span class="badge bg-primary rounded-pill fs-6">{{ $totalProjects }}</span>
                <p class="text-muted small mb-0">Total Projects</p>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
            @forelse($newProjects as $partner)
                <div class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 fw-bold">{{ $partner->sales_partner_name }}</h6>
                            <small class="text-muted">{{ $partner->project_count }}
                                {{ Str::plural('project', $partner->project_count) }}</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">{{ $partner->project_count }}</span>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted">
                    No new projects this month
                </div>
            @endforelse
        </div>
    </div>
    <div class="card-footer bg-primary">
        <div class="d-flex justify-content-between align-items-center ">
            <div>
                <h5 class="card-title mb-1 fw-bold text-white">Total Revenue</h5>
            </div>
            <div class="text-end">
                <span class="badge bg-primary fw-bold text-white rounded-pill fs-6">$ {{ number_format($totalContractAmount,2) }}</span>
            </div>
        </div>
    </div>
</div>
