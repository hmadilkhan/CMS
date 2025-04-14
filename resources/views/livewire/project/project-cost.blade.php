<div>
    <div x-data="{ show: @entangle('showMessage') }" x-show="show" x-transition x-init="$watch('show', value => { if (value) setTimeout(() => show = false, 3000) })"
        class="alert alert-{{ $messageType == 'success' ? 'success' : 'danger' }}">
        {{ $message }}
    </div>
    <div class="card mt-1">
        <div class="card-body">
            <div class="card-header py-3 px-0 d-sm-flex align-items-center  border-bottom">
                <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 text-center" data-bs-toggle="collapse"
                    data-bs-target="#adderTable" aria-expanded="false" aria-controls="adderTable">Pre & Post Project Cost
                </h3>
            </div>
            <div class="row g-4 mb-3">
                <div class="col-lg-6 col-md-6 col-sm-6 ">
                    <div class="card-header py-6 px-0 d-sm-flex align-items-center border-bottom">
                        <h4 class=" fw-bold flex-fill mb-0 mt-sm-0 text-center" data-bs-toggle="collapse"
                            data-bs-target="#adderTable" aria-expanded="false" aria-controls="adderTable">Pre Project
                            Cost
                        </h4>
                    </div>
                    <table class="table py-3">
                        <tr>
                            <td>Internal Contract Amount</td>
                            <td><input type="text" class="form-control w-50" readonly
                                    value="{{ $internalContractAmount }}" /></td>
                        </tr>
                        <tr>
                            <td>Pre-Estimated Material Cost</td>
                            <td><input type="text" class="form-control w-50" wire:model="preEstimateMaterialCost" />
                            </td>
                        </tr>
                        <tr>
                            <td>Pre-Estimated Labor Cost</td>
                            <td><input type="text" class="form-control w-50" wire:model="preEstimateLaborCost" />
                            </td>
                        </tr>
                        <tr>
                            <td>Pre-Estimated Permit Cost</td>
                            <td><input type="text" class="form-control w-50" wire:model="preEstimatePermitCost" />
                            </td>
                        </tr>
                        <tr>
                            <td>Pre-Estimated Profit</td>
                            <td><input type="text" class="form-control w-50" readonly
                                    value="{{ number_format($preEstimateProfit, 2) }}" /></td>
                        </tr>
                        <tr>
                            <td>Pre-Estimated Profit %</td>
                            <td><input type="text" class="form-control w-50" readonly
                                    value="{{ number_format($preEstimateProfitPercentage, 2) }}" /></td>
                        </tr>
                    </table>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 border-start">
                    <div class="card-header py-6 px-0 d-sm-flex align-items-center border-bottom">
                        <h4 class=" fw-bold flex-fill mb-0 mt-sm-0 text-center" data-bs-toggle="collapse"
                            data-bs-target="#adderTable" aria-expanded="false" aria-controls="adderTable">Post Project
                            Cost
                        </h4>
                    </div>
                    <table class="table py-3">
                        <tr>
                            <td>Internal Contract Amount</td>
                            <td><input type="text" class="form-control w-50" readonly
                                    value="{{ $internalContractAmount }}" /></td>
                        </tr>
                        <tr>
                            <td>Post-Estimated Material Cost</td>
                            <td><input type="text" class="form-control w-50" wire:model="postEstimateMaterialCost" />
                            </td>
                        </tr>
                        <tr>
                            <td>Post-Estimated Labor Cost</td>
                            <td><input type="text" class="form-control w-50" wire:model="postEstimateLaborCost" />
                            </td>
                        </tr>
                        <tr>
                            <td>Post-Estimated Permit Cost</td>
                            <td><input type="text" class="form-control w-50" wire:model="postEstimatePermitCost" />
                            </td>
                        </tr>
                        <tr>
                            <td>Post-Estimated Profit</td>
                            <td><input type="text" class="form-control w-50" readonly
                                    value="{{ number_format($postEstimateProfit, 2) }}" /></td>
                        </tr>
                        <tr>
                            <td>Post-Estimated Profit %</td>
                            <td><input type="text" class="form-control w-50" readonly
                                    value="{{ number_format($postEstimateProfitPercentage, 2) }}" /></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="row g-4 mb-3">
                <div class="col-lg-6 col-md-6 col-sm-6 ">
                    <button wire:click="saveToDatabase" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>
