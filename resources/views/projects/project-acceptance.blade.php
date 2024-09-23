<div class="card">
    <div class="card-body">
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
                    <h5 class="fs-10  flex-fill">Address : {{ $project->customer->address }}</h5>
                </div>
                <div class="col-md-12 ">
                    <h5 class="fs-10  flex-fill">Phone : {{ $project->customer->phone }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-12 d-flex justify-content-center mx-3">
            <img src="{{ !empty($image) ? asset('storage/project-acceptance/' . $image) : '' }}" height="400"
                width="100%" alt="" class=" mx-auto d-block">
        </div>
        <div class="row mt-4">
            <div class="col-md-12 d-flex justify-content-center">
                <h5 class="fs-10 fw-bold text-decoration-underline">Total Adder Cost</h5>
            </div>
        </div>
        <div class="row mt-4 mx-3 bg-light">
            <table class="table table-bordered table-striped">
                <tr>
                    <td>Inverter Base</td>
                    <td>{{ $project->customer->inverter->name }}</td>
                    <td>{{ number_format($project->customer->inverter->invertertyperates->base_cost, 2) }}</td>
                </tr>
                <tr>
                    <td>Dealer Fee </d>
                    <td>-</td>
                    <td>{{ number_format($project->customer->finances->dealer_fee_amount, 2) }}</td>
                </tr>
                <tr>
                    <td>Module Count </d>
                    <td>{{ $project->customer->panel_qty }} x {{ $project->customer->module->amount }}</td>
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
                    <div class="col-md-12">
                        <button type="button" class="btn btn-success me-1 w-sm-100 float-right text-white">Approve<i
                                class="icofont-arrow-right me-2 fs-6"></i></button>
                        <button type="button" class="btn btn-danger me-1 w-sm-100 float-right text-white">Reject<i
                                class="icofont-arrow-right me-2 fs-6"></i></button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
