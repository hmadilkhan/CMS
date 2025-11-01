<style>
    .premium-projects-card {
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .premium-projects-card:hover {
        box-shadow: 0 12px 48px rgba(0, 0, 0, 0.4);
    }
    .premium-projects-header {
        padding: 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.05);
    }
    .premium-projects-title {
        font-size: 18px;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 4px;
        letter-spacing: 0.3px;
    }
    .premium-projects-subtitle {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.6);
        margin: 0;
    }
    .premium-badge {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        color: #ffffff;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 16px;
        border: 1px solid rgba(255, 255, 255, 0.2);
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
        background: rgba(255, 255, 255, 0.05);
    }
    .premium-projects-list::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }
    .premium-projects-list::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    .premium-project-item {
        padding: 16px 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .premium-project-item:hover {
        /* background: rgba(255, 255, 255, 0.05); */
    }
    .premium-project-item:last-child {
        border-bottom: none;
    }
    .premium-partner-name {
        font-size: 15px;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 4px;
    }
    .premium-project-count {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.6);
    }
    .premium-count-badge {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        padding: 6px 12px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 14px;
    }
    .premium-projects-footer {
        padding: 20px 24px;
        background: rgba(255, 255, 255, 0.08);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    .premium-revenue-label {
        font-size: 16px;
        font-weight: 600;
        color: #ffffff;
        margin: 0;
    }
    .premium-revenue-badge {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        color: #ffffff;
        padding: 10px 20px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 18px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .premium-empty-state {
        padding: 40px 24px;
        text-align: center;
        color: rgba(255, 255, 255, 0.5);
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
