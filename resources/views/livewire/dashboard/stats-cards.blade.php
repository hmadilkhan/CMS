<style>
    .premium-stat-card {
        background: #ffffff !important;
        border-radius: 12px;
        padding: 22px;
        box-shadow: none !important;
        border: 1px solid #e5e7eb !important;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .premium-stat-card::before {
        content: none;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: transparent;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .premium-stat-card:hover {
        background: #ffffff !important;
        transform: translateY(-2px);
        border-color: #cbd5e1 !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06) !important;
    }
    .premium-stat-card:hover::before {
        opacity: 1;
    }
    .stat-icon-wrapper {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        background: #ffffff !important;
        border: 1px solid #dbeafe !important;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
        transition: all 0.3s ease;
    }
    .premium-stat-card:hover .stat-icon-wrapper {
        background: #ffffff !important;
        border-color: #bfdbfe !important;
        transform: scale(1.05);
    }
    .stat-icon-wrapper i {
        font-size: 28px;
        color: #1d4ed8 !important;
    }
    .stat-content {
        flex: 1;
    }
    .stat-label {
        font-size: 14px;
        font-weight: 600;
        color: #64748b !important;
        margin-bottom: 8px;
        letter-spacing: 0;
        text-transform: uppercase;
    }
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #050505 !important;
        letter-spacing: 0;
    }
</style>

<div class="row g-4">
    <div class="col-md-12">
        <div class="premium-stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon-wrapper">
                    <i class="icofont-paper"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Avg Permit Fees</div>
                    <div class="stat-value">$ {{ number_format($avgPermitFee->avg_permit_fee) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="premium-stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon-wrapper">
                    <i class="icofont-labour"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Avg Labor Cost</div>
                    <div class="stat-value">$ {{ number_format($avgLaborFee->avg_labor_cost) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="premium-stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon-wrapper">
                    <i class="icofont-gift-box"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Avg Material Cost</div>
                    <div class="stat-value">$ {{ number_format($avgMaterialFee->avg_material_cost) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="premium-stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon-wrapper">
                    <i class="icofont-dollar"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Avg Contract Amount</div>
                    <div class="stat-value">$ {{ number_format($avgContractAmount) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
