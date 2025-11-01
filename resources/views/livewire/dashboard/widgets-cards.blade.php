
<div class="row g-4">
    <style>
        .premium-widget-card {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .premium-widget-card::before {
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
        .premium-widget-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.4);
        }
        .premium-widget-card:hover::before {
            opacity: 1;
        }
        .widget-icon-wrapper {
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
        .premium-widget-card:hover .widget-icon-wrapper {
            background: rgba(255, 255, 255, 0.15);
            transform: scale(1.05);
        }
        .widget-icon-wrapper i {
            font-size: 28px;
            color: #ffffff;
        }
        .widget-content {
            flex: 1;
        }
        .widget-label {
            font-size: 14px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .widget-value {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
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
