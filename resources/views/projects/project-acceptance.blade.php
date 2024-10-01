<div class="card">
    <div class="card-body">
        @if (!empty($projectAcceptance) && $projectAcceptance->action_by != 0)
        <div class="row mt-4 mx-3">
            <table class="table table-bordered">
                <tr class="bg-light">
                    <th class="fw-bold">Approved By</th>
                    <th class="fw-bold">Status</th>
                    <th class="fw-bold">Action Date</th>
                </tr>
                <tr>
                    <td>{{$projectAcceptance->user->name}}</td>
                    <td>{{$projectAcceptance->status == 1 ? "Approved" : "Rejected" }}</td>
                    <td>{{date("d M Y" , strtotime($projectAcceptance->approved_date)). " ". date("H:i a" , strtotime($projectAcceptance->approved_date))}}</td>
                </tr>
            </table>
        </div>
        @endif
        <div class="row">
            <div class="col-md-12 d-flex justify-content-center">
                <img src="{{ asset('storage/solen_logo.png') }}" width="250" height="200" alt="" class="">
            </div>
            <div class="col-md-12 d-flex justify-content-center mb-2">
                <h4 class=" fw-bold flex-fill mb-0 mt-sm-0 text-center fs-10 text-uppercase">Project Acceptance
                    Review</h4>
            </div>
            <hr />
            @php
                $modulesAmount = $project->customer->panel_qty * $project->customer->module->amount;
            @endphp
            <div class="row mx-4">
                <div class="col-md-12 ">
                    <h5 class="fs-10  flex-fill">Homeowner Name :
                        {{ $project->customer->first_name . ' ' . $project->customer->last_name }}</h5>
                </div>
                <div class="col-md-12 ">
                    <h5 class="fs-10  flex-fill">Address : {{ $project->customer->state." ".$project->customer->city." ".$project->customer->street }}</h5>
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
        $basePrice = $project->customer->inverter->invertertyperates->base_cost + $project->overwrite_base_price;
        $moduleQtyPrice = $project->customer->module->amount + $project->overwrite_panel_price;
        @endphp
        <div class="row mt-4 mx-3 bg-light">
            <table class="table table-bordered table-striped">
                <tr>
                    <td>Inverter Base</td>
                    <td>{{ $project->customer->inverter->name }}</td>
                    <td>{{ number_format($basePrice, 2) }}</td>
                </tr>
                <tr>
                    <td>Dealer Fee </d>
                    <td>-</td>
                    <td>{{ number_format($project->customer->finances->dealer_fee_amount, 2) }}</td>
                </tr>
                <tr>
                    <td>Module Count </d>
                    <td>{{ $project->customer->panel_qty }} x {{ $moduleQtyPrice }}</td>
                    <td>{{ number_format($modulesAmount, 2) }}</td>
                </tr>
                <tr>
                    <td>Contract Price</d>
                    <td>-</td>
                    <td>{{ number_format($project->customer->finances->contract_amount, 2) }}</td>
                </tr>
                <tr>
                    <td>System Cost</d>
                    <td>-</td>
                    <td>{{ number_format($project->customer->finances->redline_costs, 2) }}</td>
                </tr>
                <tr>
                    <td>Adder Total</d>
                    <td>-</td>
                    <td>{{ number_format($project->customer->finances->adders, 2) }}</td>
                </tr>
                <tr>
                    <td>Commission</d>
                    <td>-</td>
                    <td>{{ number_format($project->customer->finances->commission, 2) }}</td>
                </tr>
            </table>
        </div>
        <div class="row mx-4">
            <div class="col-md-12 ">
                <h5 class="fs-10  flex-fill">Adders :
                    @foreach ($project->customer->adders as $adders)
                        {{ $adders->type->name }},
                    @endforeach
                </h5>
            </div>
        </div>
        @if ($mode == 'view')
            <div class="row mt-4 mx-3">
                @if (!auth()->user()->hasAnyRole(['Manager', 'Sales Person']))
                    <div class="col-md-12">
                        <button type="button" class="btn btn-dark me-1 w-sm-100 float-right">Send Email<i
                                class="icofont-arrow-right me-2 fs-6"></i></button>
                    </div>
                @else
                    @if (!empty($projectAcceptance) && $projectAcceptance->action_by == 0)
                        <div class="col-md-12">
                            <button onclick="acceptanceAction('2','{{ $projectAcceptance->id }}')" type="button"
                                class="btn btn-danger me-1 w-sm-100 float-right text-white"><i
                                    class="mr-1 icofont-close me-2 fs-6"></i>Reject</button>
                            <button onclick="acceptanceAction('1','{{ $projectAcceptance->id }}')" type="button"
                                class="btn btn-success me-1 w-sm-100 float-right text-white"><i
                                    class="mr-1 icofont-tick-mark me-2 fs-6"></i> Approve</button>
                        </div>
                    @endif
                @endif
            </div>
        @endif
    </div>
</div>
