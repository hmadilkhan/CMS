<div class="row g-4">
    <div class="col-md-12">
        <div class="card p-3">
            <div class="card-body text-dark d-flex align-items-center">
                <i class="icofont-dollar fs-3 me-3"></i>
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h6 class="mb-0 fw-bold fs-6">Rolling 12 months Revenue</h6>
                    <span class="text-dark fw-bold fs-5">$ {{number_format($totalContractAmount)}}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card p-3">
            <div class="card-body text-dark d-flex align-items-center">
                <i class="icofont-ruler-compass fs-3 me-3"></i>
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h6 class="mb-0 fw-bold fs-5">YTD Revenue</h6>
                    <span class="text-dark fw-bold fs-5">$ {{number_format($totalYtdrevenue)}}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card p-3">
            <div class="card-body text-dark d-flex align-items-center">
                <i class="icofont-price fs-3 me-3"></i>
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h6 class="mb-0 fw-bold fs-5">YTD Total Commission</h6>
                    <span class="text-dark fw-bold fs-5"> {{number_format($totalCommission)}}</span>
                </div>
            </div>
        </div>
    </div>
</div>
