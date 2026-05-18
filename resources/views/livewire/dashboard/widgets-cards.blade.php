
<div class="row g-4">
    <style>
        .premium-widget-card {
            background: #ffffff !important;
            border-radius: 12px;
            padding: 22px;
            box-shadow: none !important;
            border: 1px solid #e5e7eb !important;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .premium-widget-card::before {
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
        .premium-widget-card:hover {
            background: #ffffff !important;
            transform: translateY(-2px);
            border-color: #cbd5e1 !important;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06) !important;
        }
        .premium-widget-card:hover::before {
            opacity: 1;
        }
        .widget-icon-wrapper {
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
        .premium-widget-card:hover .widget-icon-wrapper {
            background: #ffffff !important;
            border-color: #bfdbfe !important;
            transform: scale(1.05);
        }
        .premium-widget-card .widget-icon-wrapper i {
            font-size: 28px !important;
            color: #1d4ed8 !important;
            line-height: 1;
        }
        .premium-widget-card .widget-content {
            flex: 1;
        }
        .premium-widget-card .widget-label {
            font-size: 14px !important;
            font-weight: 600 !important;
            color: #64748b !important;
            margin-bottom: 8px;
            letter-spacing: 0;
            line-height: 1.2;
            text-transform: uppercase;
        }
        .premium-widget-card .widget-value {
            font-size: 28px !important;
            font-weight: 700 !important;
            color: #050505 !important;
            letter-spacing: 0;
            line-height: 1.15;
        }
    </style>
    <div class="col-md-12">
        <div class="premium-widget-card">
            <div class="d-flex align-items-center">
                <div class="widget-icon-wrapper">
                    <i class="icofont-dollar"></i>
                </div>
                <div class="widget-content">
                    <div class="widget-label">Rolling 12 months Revenue</div>
                    <div class="widget-value">$ {{number_format($totalContractAmount)}}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="premium-widget-card">
            <div class="d-flex align-items-center">
                <div class="widget-icon-wrapper">
                    <i class="icofont-ruler-compass"></i>
                </div>
                <div class="widget-content">
                    <div class="widget-label">YTD Revenue</div>
                    <div class="widget-value">$ {{number_format($totalYtdrevenue)}}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="premium-widget-card">
            <div class="d-flex align-items-center">
                <div class="widget-icon-wrapper">
                    <i class="icofont-price"></i>
                </div>
                <div class="widget-content">
                    <div class="widget-label">YTD Total Commission</div>
                    <div class="widget-value">{{number_format($totalCommission)}}</div>
                </div>
            </div>
        </div>
        <div class="premium-widget-card">
            <div class="d-flex align-items-center">
                <div class="widget-icon-wrapper">
                    <i class="icofont-price"></i>
                </div>
                <div class="widget-content">
                    <div class="widget-label">Total AVG Commission</div>
                    <div class="widget-value">{{number_format($avgCommission)}}</div>
                </div>
            </div>
        </div>
    </div>
</div>
