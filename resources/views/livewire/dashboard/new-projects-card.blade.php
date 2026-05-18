<style>
    .premium-projects-card {
        background: #ffffff !important;
        border-radius: 12px;
        box-shadow: none !important;
        border: 1px solid #e5e7eb !important;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .premium-projects-card:hover {
        background: #ffffff !important;
        border-color: #cbd5e1 !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06) !important;
    }
    .premium-projects-header {
        padding: 20px 22px;
        border-bottom: 1px solid #e5e7eb;
        background: #ffffff !important;
    }
    .premium-projects-title {
        font-size: 18px;
        font-weight: 700;
        color: #050505 !important;
        margin-bottom: 4px;
        letter-spacing: 0;
    }
    .premium-projects-subtitle {
        font-size: 13px;
        color: #64748b !important;
        margin: 0;
    }
    .premium-badge {
        background: #eff6ff !important;
        color: #1d4ed8 !important;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 16px;
        border: 1px solid #bfdbfe;
    }
    .premium-projects-list {
        max-height: 300px;
        overflow-y: auto;
        padding: 0;
    }
    .premium-projects-list::-webkit-scrollbar {
        width: 6px;
    }
    .premium-projects-list::-webkit-scrollbar-track {
        background: #f8fafc;
    }
    .premium-projects-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    .premium-projects-list::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .premium-project-item {
        padding: 16px 22px;
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .premium-project-item:hover {
        background: #f8fafc !important;
    }
    .premium-project-item:last-child {
        border-bottom: none;
    }
    .premium-partner-name {
        font-size: 15px;
        font-weight: 600;
        color: #050505 !important;
        margin-bottom: 4px;
    }
    .premium-project-count {
        font-size: 12px;
        color: #64748b !important;
    }
    .premium-count-badge {
        background: #ffffff !important;
        color: #1d4ed8 !important;
        padding: 6px 12px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 14px;
        border: 1px solid #dbeafe;
    }
    .premium-projects-footer {
        padding: 18px 22px;
        background: #ffffff !important;
        border-top: 1px solid #e5e7eb;
    }
    .premium-revenue-label {
        font-size: 16px;
        font-weight: 700;
        color: #050505 !important;
        margin: 0;
    }
    .premium-revenue-badge {
        background: #050505 !important;
        color: #ffffff !important;
        padding: 10px 20px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 18px;
        border: 1px solid #050505;
    }
    .premium-empty-state {
        padding: 40px 24px;
        text-align: center;
        color: #64748b !important;
        font-size: 14px;
    }
</style>

<div class="premium-projects-card h-100">
    <div class="premium-projects-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="premium-projects-title">New Projects This Month</h5>
                <p class="premium-projects-subtitle">Sales Partner Performance</p>
            </div>
            <div class="text-end">
                <span class="premium-badge">{{ $totalProjects }}</span>
                <p class="premium-projects-subtitle mt-1">Total Projects</p>
            </div>
        </div>
    </div>
    <div class="premium-projects-list">
        @forelse($newProjects as $partner)
            <div class="premium-project-item">
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <div>
                        <h6 class="premium-partner-name">{{ $partner->sales_partner_name }}</h6>
                        <small class="premium-project-count">{{ $partner->project_count }}
                            {{ Str::plural('project', $partner->project_count) }}</small>
                    </div>
                    <span class="premium-count-badge">{{ $partner->project_count }}</span>
                </div>
            </div>
        @empty
            <div class="premium-empty-state">
                No new projects this month
            </div>
        @endforelse
    </div>
    <div class="premium-projects-footer">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="premium-revenue-label">Total Revenue</h5>
            <span class="premium-revenue-badge">$ {{ number_format($totalContractAmount,2) }}</span>
        </div>
    </div>
</div>
