<div class="card">
    <div class="card-body">
        @if (!empty($projectAcceptance) && $projectAcceptance->action_by != 0)
            <div class="row mt-4 mx-3">
                <div class="alert alert-{{ $projectAcceptance->status == 1 ? 'success' : 'danger' }} mb-3">
                    <strong>Current Status:</strong> {{ $projectAcceptance->status == 1 ? 'Approved' : 'Rejected' }}
                </div>
                <table class="table table-bordered">
                    <tr class="bg-light">
                        <th class="fw-bold">Approved By</th>
                        <th class="fw-bold">Status</th>
                        <th class="fw-bold">Action Date</th>
                        <th class="fw-bold">Reason</th>
                    </tr>
                    <tr>
                        <td>{{ $projectAcceptance->user->name }}</td>
                        <td><span class="badge bg-{{ $projectAcceptance->status == 1 ? 'success' : 'danger' }}">{{ $projectAcceptance->status == 1 ? 'Approved' : 'Rejected' }}</span></td>
                        <td>{{ date('d M Y', strtotime($projectAcceptance->approved_date)) . ' ' . date('H:i a', strtotime($projectAcceptance->approved_date)) }}
                        <td>{{ $projectAcceptance->reason }}</td>
                        </td>
                    </tr>
                </table>
            </div>
            

        @endif
        <div class="row">
            <div class="col-md-12 d-flex justify-content-center">
                <img src="{{ asset('storage/solen_logo.png') }}" width="250" height="200" alt=""
                    class="">
            </div>
            <div class="col-md-12 d-flex justify-content-center mb-2">
                <h4 class=" fw-bold flex-fill mb-0 mt-sm-0 text-center fs-10 text-uppercase">Project Acceptance
                    Review</h4>
            </div>
            <hr />
            <div class="row mx-4">
                <div class="col-md-12 ">
                    <h5 class="fs-10  flex-fill">Homeowner Name :
                        {{ $project->customer->first_name . ' ' . $project->customer->last_name }}</h5>
                </div>
                <div class="col-md-12 ">
                    <h5 class="fs-10  flex-fill">Address :
                        {{ $project->customer->state . ' ' . $project->customer->city . ' ' . $project->customer->street }}
                    </h5>
                </div>
                <div class="col-md-12 ">
                    <h5 class="fs-10  flex-fill">Phone : {{ $project->customer->phone }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-12 d-flex justify-content-center mx-3">
            <img src="{{ !empty($projectAcceptance) ? asset('storage/project-acceptance/' . $projectAcceptance->image) : '' }}"
                width="100%" alt="" class=" mx-auto d-block">
        </div>
        <div class="row mt-4">
            <div class="col-md-12 d-flex justify-content-center">
                <h5 class="fs-10 fw-bold text-decoration-underline">Total Adder Cost</h5>
            </div>
        </div>
        @php
            $basePrice = $projectAcceptance->inverter_base_price;
            $moduleQtyPrice = $projectAcceptance->module_qty_price;
            $modulesAmount = $projectAcceptance->modules_amount;
            $dealerFeeAmount = $projectAcceptance->dealer_fee_amount;
            $contractAmount = $projectAcceptance->contract_amount;
            $redlineCosts = $projectAcceptance->redline_costs;
            $addersAmount = $projectAcceptance->adders_amount;
            $commissionAmount = $projectAcceptance->commission_amount;
            $inverterName = $projectAcceptance->inverter_name;
            $panelQty = $projectAcceptance->panel_qty;
        @endphp
        <div class="row mt-4 mx-3 bg-light">
            <table class="table table-bordered table-striped">
                <tr>
                    <td>Inverter Base</td>
                    <td>{{ $inverterName }}</td>
                    <td>{{ number_format($basePrice, 2) }}</td>
                </tr>
                <tr>
                    <td>Dealer Fee </d>
                    <td>-</td>
                    <td>{{ number_format($dealerFeeAmount, 2) }}</td>
                </tr>
                <tr>
                    <td>Module Count </d>
                    <td>{{ $panelQty }} x {{ $moduleQtyPrice }}</td>
                    <td>{{ number_format($modulesAmount, 2) }}</td>
                </tr>
                <tr>
                    <td>Contract Price</d>
                    <td>-</td>
                    <td>{{ number_format($contractAmount, 2) }}</td>
                </tr>
                <tr>
                    <td>System Cost</d>
                    <td>-</td>
                    <td>{{ number_format($redlineCosts, 2) }}</td>
                </tr>
                <tr>
                    <td>Adder Total</d>
                    <td>-</td>
                    <td>{{ number_format($addersAmount, 2) }}</td>
                </tr>
                <tr>
                    <td>Commission</d>
                    <td>-</td>
                    <td>{{ number_format($commissionAmount, 2) }}</td>
                </tr>
            </table>
        </div>
        <div class="row mx-4">
            <div class="col-md-12 ">
                <h5 class="fs-10  flex-fill">Adders : {{ implode(', ', $projectAcceptance->adders_list) }}</h5>
            </div>
        </div>
        @if (!empty($projectAcceptance->notes))
            <div class="row mx-4 mt-3">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <h6 class="fw-bold mb-2"><i class="icofont-ui-note me-2"></i>Notes:</h6>
                        <p class="mb-0">{{ $projectAcceptance->notes }}</p>
                    </div>
                </div>
            </div>
        @endif
        @if (!empty($projectAcceptance) && $projectAcceptance->action_by == 0 && auth()->user()->hasAnyRole(['Manager', 'Sales Person']))
            <div class="row mx-4 border-bottom mt-3">
                <div class="col-md-12 ">
                    <label for="customer_id" class="form-label">Reason : </label>
                    <textarea class="form-control" id="reason" name="reason" rows="3"
                        placeholder="Enter Reason in case of Rejection"></textarea>
                    <span id="reason_message" class="text-danger"></span>
                </div>
            </div>
        @endif
        @if ($mode == 'view' && auth()->user()->hasAnyRole(['Manager', 'Sales Person']))
            @if (!empty($projectAcceptance) && $projectAcceptance->action_by == 0)
                <div class="row mt-4 mx-3">
                    <div class="col-md-12 ">
                        <button
                            onclick="acceptanceAction('2','{{ $projectAcceptance->id }}','{{ $projectAcceptance->project_id }}')"
                            type="button" class="btn btn-danger me-1 w-sm-100 float-right text-white"><i
                                class="mr-1 icofont-close me-2 fs-6"></i>Reject</button>
                        <button
                            onclick="acceptanceAction('1','{{ $projectAcceptance->id }}','{{ $projectAcceptance->project_id }}')"
                            type="button" class="btn btn-success me-1 w-sm-100 float-right text-white"><i
                                class="mr-1 icofont-tick-mark me-2 fs-6"></i> Approve</button>
                    </div>
                </div>
            @endif
        @endif
        
        @if(isset($rejectedAcceptances) && $rejectedAcceptances->count() > 0)
            <hr class="my-4">
            <div class="row mx-3">
                <h5 class="fw-bold mb-3"><i class="icofont-history me-2"></i>Rejection History ({{ $rejectedAcceptances->count() }})</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Rejected By</th>
                                <th>Date</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rejectedAcceptances as $index => $rejected)
                                <tr>
                                    <td>{{ $rejected->user->name ?? 'N/A' }}</td>
                                    <td>{{ $rejected->approved_date != "" ? date('d M Y H:i a', strtotime($rejected->approved_date)) : "N/A" }}</td>
                                    <td>{{ Str::limit($rejected->reason ?? 'No reason provided', 50) }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#rejectedModal{{ $index }}">
                                            <i class="icofont-eye"></i> View Details
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            @foreach($rejectedAcceptances as $index => $rejected)
                <div class="modal fade" id="rejectedModal{{ $index }}" tabindex="-1">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">Rejected Acceptance Details</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-danger">
                                    <strong>Rejected By:</strong> {{ $rejected->user->name ?? 'N/A' }} | 
                                    <strong>Date:</strong> {{  $rejected->approved_date != "" ? date('d M Y H:i a', strtotime($rejected->approved_date)) : "N/A" }}
                                </div>
                                <div class="mb-3">
                                    <strong>Reason:</strong>
                                    <p class="border p-2 bg-light">{{ $rejected->reason ?? 'No reason provided' }}</p>
                                </div>
                                <div class="text-center mb-3">
                                    <img src="{{ asset('storage/project-acceptance/' . $rejected->image) }}" class="img-fluid" style="max-height: 400px;">
                                </div>
                                <h6 class="fw-bold">Financial Details</h6>
                                <table class="table table-bordered table-sm">
                                    <tr><td>Inverter Base</td><td>{{ $rejected->inverter_name }}</td><td>{{ number_format($rejected->inverter_base_price, 2) }}</td></tr>
                                    <tr><td>Dealer Fee</td><td>-</td><td>{{ number_format($rejected->dealer_fee_amount, 2) }}</td></tr>
                                    <tr><td>Module Count</td><td>{{ $rejected->panel_qty }} x {{ $rejected->module_qty_price }}</td><td>{{ number_format($rejected->modules_amount, 2) }}</td></tr>
                                    <tr><td>Contract Price</td><td>-</td><td>{{ number_format($rejected->contract_amount, 2) }}</td></tr>
                                    <tr><td>System Cost</td><td>-</td><td>{{ number_format($rejected->redline_costs, 2) }}</td></tr>
                                    <tr><td>Adder Total</td><td>-</td><td>{{ number_format($rejected->adders_amount, 2) }}</td></tr>
                                    <tr><td>Commission</td><td>-</td><td>{{ number_format($rejected->commission_amount, 2) }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
