<div class="row g-4">
    <div class="col-md-12">
        <div class="card p-3 border border-dark" style="border: 1px solid #000;">
            <div class="card-body text-dark d-flex align-items-center">
                <i class="icofont-paper fs-3 me-3"></i>
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h6 class="mb-0 fw-bold fs-5">Avg Permit Fees</h6>
                    <span class="text-dark fw-bold fs-5">$ {{ number_format($avgPermitFee->avg_permit_fee) }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card p-3">
            <div class="card-body text-dark d-flex align-items-center">
                <i class="icofont-labour fs-3 me-3"></i>
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h6 class="mb-0 fw-bold fs-5">Avg Labor Cost</h6>
                    <span class="text-dark fw-bold fs-5">$ {{ number_format($avgLaborFee->avg_labor_cost) }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card p-3">
            <div class="card-body text-dark d-flex align-items-center">
                <i class="icofont-gift-box fs-3 me-3"></i>
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h6 class="mb-0 fw-bold fs-5">Avg Material Cost</h6>
                    <span class="text-dark fw-bold fs-5">$
                        {{ number_format($avgMaterialFee->avg_material_cost) }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card p-3">
            <div class="card-body text-dark d-flex align-items-center">
                <i class="icofont-dollar fs-3 me-3"></i>
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h6 class="mb-0 fw-bold fs-5">Avg Contract Amount</h6>
                    <span class="text-dark fw-bold fs-5">$ {{ number_format($avgContractAmount) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
