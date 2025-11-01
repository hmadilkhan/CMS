<style>
    .premium-stat-card {
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .premium-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, transparent 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .premium-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 48px rgba(0, 0, 0, 0.4);
    }
    .premium-stat-card:hover::before {
        opacity: 1;
    }
    .stat-icon-wrapper {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
        transition: all 0.3s ease;
    }
    .premium-stat-card:hover .stat-icon-wrapper {
        background: rgba(255, 255, 255, 0.15);
        transform: scale(1.05);
    }
    .stat-icon-wrapper i {
        font-size: 28px;
        color: #ffffff;
    }
    .stat-content {
        flex: 1;
    }
    .stat-label {
        font-size: 14px;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 8px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: -0.5px;
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
