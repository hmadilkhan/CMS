@extends('layouts.master')
@section('title', 'Edit Customer')
@section('content')
    <div class="card card-info">
        <div class="card-body">
            <div class="row clearfix">
                <div class="col-md-12">
                    <div class="card border-0 mb-4 no-bg">
                        <div
                            class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Edit Customer</h3>
                            <a href="{{ route('customers.index') }}" class="btn btn-dark me-1 mt-1 w-sm-100"
                                id="openemployee"><i class="icofont-arrow-left me-2 fs-6"></i>Back to List</a>
                        </div>
                    </div>
                </div>
            </div><!-- Row End -->
            <form id="form" method="post" action="{{ route('customers.update', $customer->id) }}"
                enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <input type="hidden" id="overwrite_base_price" name="overwrite_base_price"  value="{{$customer->project->overwrite_base_price}}"/>
                <input type="hidden" id="overwrite_panel_price" name="overwrite_panel_price"  value="{{$customer->project->overwrite_panel_price}}" />
                <div class="row g-3 mb-3">
                    <div class="col-sm-6 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                            placeholder="First Name" value="{{ $customer->first_name }}">
                        @error('first_name')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name"
                            value="{{ $customer->last_name }}">
                        @error('last_name')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">Street</label>
                        <input type="text" class="form-control" id="street" name="street" placeholder="Street"
                            value="{{ $customer->street }}">
                        @error('street')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city" placeholder="City"
                            value="{{ $customer->city }}">
                        @error('city')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">State</label>
                        <input type="text" class="form-control" id="state" name="state" placeholder="State"
                            value="{{ $customer->state }}">
                        @error('state')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">Zip Code</label>
                        <input type="text" class="form-control" id="zipcode" name="zipcode" placeholder="Zip Code"
                            value="{{ $customer->zipcode }}">
                        @error('zipcode')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="phone"
                            value="{{ $customer->phone }}">
                        @error('phone')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">Email</label>
                        <input type="text" class="form-control" id="email" name="email" placeholder="Email"
                            value="{{ $customer->email }}">
                        @error('email')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label for="sold_date" class="form-label">Sold Date</label>
                        <input type="date" class="form-control" id="sold_date" name="sold_date"
                            placeholder="Sold Date" value="{{ $customer->sold_date }}">
                        @error('sold_date')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Sales Partner</label>
                        <select class="form-select select2" aria-label="Default select Sales Partner"
                            id="sales_partner_id" name="sales_partner_id">
                            <option value="">Select Sales Partner</option>
                            @foreach ($partners as $partner)
                                <option {{ $customer->sales_partner_id == $partner->id ? 'selected' : '' }}
                                    value="{{ $partner->id }}">
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('sales_partner_id')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Sales Partner User</label>
                        <select class="form-select select2" aria-label="Default select Sales Partner"
                            id="sales_partner_user_id" name="sales_partner_user_id">
                            <option value="">Select Sales Partner User</option>
                            @foreach ($users as $user)
                                <option {{ $customer->project->sales_partner_user_id == $user->id ? 'selected' : '' }}
                                    value="{{ $user->id }}">
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('sales_partner_user_id')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-sm-4">
                        <label class="form-label">Module Type</label>
                        <select class="form-select select2" aria-label="Default select Module Type" id="module_type_id"
                            name="module_type_id" onchange="calculateSystemSize()">
                            <option value="">Select Module Type</option>
                            @foreach ($modules as $module)
                                <option {{ $customer->module_type_id == $module->id ? 'selected' : '' }}
                                    value="{{ $module->id }}">
                                    {{ $module->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('module_type_id')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Inverter Type</label>
                        <select class="form-select select2" aria-label="Default select Inverter Type"
                            id="inverter_type_id" name="inverter_type_id" onchange="getRedlineCost()">
                            <option value="">Select Inverter Type</option>
                            @foreach ($inverter_types as $inverter)
                                <option {{ $customer->inverter_type_id == $inverter->id ? 'selected' : '' }}
                                    value="{{ $inverter->id }}">
                                    {{ $inverter->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('inverter_type_id')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-sm-4">
                        <label for="code" class="form-label">Panel Qty</label>
                        <input type="text" class="form-control" id="panel_qty" name="panel_qty"
                            placeholder="Panel Qty" onblur="calculateSystemSizeAmount()"
                            value="{{ $customer->panel_qty }}">
                        @error('panel_qty')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">System Size</label>
                        <input type="text" class="form-control" id="module_qty" name="module_qty"
                            placeholder="System Size" value="{{ $customer->module_value }}">
                        @error('module_qty')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">Inverter Qty</label>
                        <input type="text" class="form-control" id="inverter_qty" name="inverter_qty"
                            placeholder="Inverter Qty" value="{{ $customer->inverter_qty }}">
                        @error('inverter_qty')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-sm-4 mt-4">
                        <label for="adu" class="form-label">Is ADU?</label></br>
                        <select class="form-select select2" aria-label="Default select ADU" id="adu"
                            name="adu">
                            <option value="">Select ADU</option>
                            <option @selected($customer->is_adu == 1) value="1">Yes</option>
                            <option @selected($customer->is_adu == 0) value="0">No</option>
                        </select>
                        @error('adu')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="loadIdDiv" class="col-sm-4 ">
                        <label for="exampleFormControlInput877" class="form-label">Loan Id</label>
                        <input type="text" class="form-control" id="loanId" name="loanId" placeholder="loan Id" value="{{$customer->loan_id}}">
                        @error('loanId')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="soldProductionValueDiv" class="col-sm-4 ">
                        <label for="exampleFormControlInput877" class="form-label">Sold Production Value</label>
                        <input type="text" class="form-control" id="sold_production_value"
                            name="sold_production_value" placeholder="Sold Production Value">
                        @error('sold_production_value')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-sm-4 mb-3">
                  
                    </div>
                </div>
                <div class="row clearfix">
                    <div class="col-md-12">
                        <div class="card border-0 mb-4 no-bg">
                            <div
                                class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                <h6 class=" fw-bold flex-fill mb-0 mt-sm-0">Adders Area</h6>
                            </div>
                        </div>
                    </div>
                </div><!-- Row End -->

                <div class="row g-3 mb-3">
                    <div class="col-sm-3 mb-3">
                        <label for="adders" class="form-label">Adders</label>
                        <select class="form-select select2" aria-label="Default select Adders" id="adders"
                            name="adders">
                            <option value="">Select Adders</option>
                            @foreach ($adders as $adder)
                                <option value="{{ $adder->id }}">
                                    {{ $adder->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
          
                    <div class="col-sm-3 mb-3">
                        <label for="uom" class="form-label">UOM</label>
                        <select class="form-select select2" aria-label="Default select UOM" id="uom">
                            <option value="">Select UOM</option>
                            @foreach ($uoms as $uom)
                                <option value="{{ $uom->id }}">
                                    {{ $uom->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('uom')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="text" class="form-control" id="amount" name="amount"
                            placeholder="Adders Amount" value="{{ $customer->amount }}">
                        @error('amount')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mt-5">
                        <button type="button" id="btnAdder" class="btn btn-primary"><i
                                class="icofont-save me-2 fs-6"></i>Add</button>
                    </div>
                </div>
                </hr>
                <table id="adderTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Adder</th>
                            <!-- <th>Sub Adders</th> -->
                            <th>Unit</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customer->adders as $key => $adder)
                            @php $index = ++$key; @endphp
                            <tr id="row{{ $key }}">
                                <input type="hidden" value="{{ $adder->adder_type_id }}" name="adders[]" />
                                <!-- <input type="hidden" value="{{ $adder->adder_sub_type_id }}" name="subadders[]" /> -->
                                <input type="hidden" value="{{ $adder->adder_unit_id }}" name="uom[]" />
                                <input type="hidden" value="{{ $adder->amount }}" name="amount[]" />
                                <td>{{ $index }}</td>
                                <td>{{ $adder->type->name }}</td>
                                {{-- <td>{{$adder->subtype->name}}</td> --}}
                                <td>{{ $adder->unit->name }}</td>
                                <td>{{ $adder->amount }}</td>
                                <td>
                                    <i style='cursor: pointer;' class='icofont-trash text-danger'
                                        onClick="deleteItem('{{ $index }}')"> Delete</i>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <!-- Adders Area End -->
                <div class="row clearfix">
                    <div class="col-md-12">
                        <div class="card border-0 mb-4 no-bg">
                            <div
                                class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                <h6 class=" fw-bold flex-fill mb-0 mt-sm-0">Customer Financing</h6>
                            </div>
                        </div>
                    </div>
                </div><!-- Row End -->
                <div class="row g-3 mb-3">
                    <div class="col-sm-3 mb-3">
                        <label for="finance_option_id" class="form-label">Finance Option</label>
                        <select class="form-select select2" aria-label="Default select Finance Option"
                            id="finance_option_id" name="finance_option_id">
                            <option value="">Select Finance Option</option>
                            @foreach ($financeoptions as $financeOption)
                                <option
                                    {{ $customer->finances->finance_option_id == $financeOption->id ? 'selected' : '' }}
                                    value="{{ $financeOption->id }}">
                                    {{ $financeOption->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('finance_option_id')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3 loandiv">
                        <label for="loan_term_id" class="form-label">Loan Term</label>
                        <select class="form-select select2" aria-label="Default select Loan Term" id="loan_term_id"
                            name="loan_term_id">
                            <option value="">Select Loan Term</option>
                        </select>
                        @error('loan_term_id')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3 loandiv">
                        <label for="loan_apr_id" class="form-label">Loan Apr</label>
                        <select class="form-select select2" aria-label="Default select Loan Apr" id="loan_apr_id"
                            name="loan_apr_id">
                            <option value="">Select Loan Apr</option>
                        </select>
                        @error('loan_apr_id')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="contract_amount" class="form-label">Contract Amount</label>
                        <input type="text" class="form-control" id="contract_amount" name="contract_amount"
                            placeholder="Contract Amount" onblur="dealerFee()"
                            value="{{ $customer->finances->contract_amount }}">
                        @error('contract_amount')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="redline_costs" class="form-label">Redline Costs</label>
                        <input type="text" class="form-control" id="redline_costs" name="redline_costs"
                            placeholder="Redline Costs" value="{{ $customer->finances->redline_costs }}">
                        @error('redline_costs')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="adders" class="form-label">Adders</label>
                        <input type="text" class="form-control" id="adders_amount" name="adders_amount"
                            placeholder="Adders" value="{{ $customer->finances->adders }}">
                        @error('adders')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="commission" class="form-label">Commission</label>
                        <input type="text" class="form-control" id="commission" name="commission"
                            placeholder="Commission" value="{{ $customer->finances->commission }}">
                        @error('commission')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="dealer_fee" class="form-label">Dealer Fee</label>
                        <input readonly type="text" class="form-control" id="dealer_fee" name="dealer_fee"
                            placeholder="Dealer Fee" value="{{ $customer->finances->dealer_fee }}">
                        @error('dealer_fee')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="dealer_fee_amount" class="form-label">Dealer Fee Amount</label>
                        <input type="text" class="form-control" id="dealer_fee_amount" name="dealer_fee_amount"
                            placeholder="Dealer Fee Amount" value="{{ $customer->finances->dealer_fee_amount }}">
                        @error('dealer_fee_amount')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="icofont-save me-2 fs-6"></i>Update</button>
            </form>

        </div>
    </div>
@endsection
@section('scripts')
    <script>
        var moduleCost = 0;
        var systemSize = 0;
        var baseCost = 0;
        $(document).ready(function() {
            $(".loandiv").css("display", "none");
            getFinanceOptionById({{ $customer->finances->finance_option_id }});
        });
       
        function getFinanceOptionById(id) {
            console.log(id);
            $.ajax({
                method: "POST",
                url: "{{ route('get.finance.option.by.id') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id,
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {
                        let finance = response.finance_options;
                        if (finance.loan_id == 1) {
                            $("#loadIdDiv").css("display", "block");
                        } else {
                            $("#loadIdDiv").css("display", "none");
                        }
                        if (finance.production_requirements == 1) {
                            $("#soldProductionValueDiv").css("display", "block");
                        } else {
                            $("#soldProductionValueDiv").css("display", "none");
                        }

                    } else {
                        console.log(response.message);
                    }
                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        }
        $("#finance_option_id").change(function() {
            getFinanceOptionById($(this).val());
            if ($(this).val() != 1 && $(this).val() != 5) {
                // $(".loandiv").css("display", "block");
                $.ajax({
                    method: "POST",
                    url: "{{ route('get.loan.terms') }}",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: $(this).val(),
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loan_term_id').empty();
                        $('#loan_term_id').append($('<option value="">Select Loan Term</soption>'));
                        $.each(response.terms, function(i, term) {
                            $('#loan_term_id').append($('<option  value="' + term.id + '">' +
                                term.year + '</option>'));
                        });
                    },
                    error: function(error) {
                        console.log(error.responseJSON.message);
                    }
                })
            } else {
                // $(".loandiv").css("display", "none");
                $("#dealer_fee").val(0);
                $("#dealer_fee_amount").val(0);
                calculateCommission()
            }
        });
        $("#loan_term_id").change(function() {
            if ($(this).val() != "") {
                $.ajax({
                    method: "POST",
                    url: "{{ route('get.loan.aprs') }}",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: $(this).val(),
                        finance_option_id: $("#finance_option_id").val()
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loan_apr_id').empty();
                        $('#loan_apr_id').append($('<option value="">Select Loan Apr</soption>'));
                        $.each(response.aprs, function(i, apr) {
                            $('#loan_apr_id').append($('<option  value="' + apr.id + '">' + (apr
                                .apr * 100).toFixed(2) + '%</option>'));
                        });
                    },
                    error: function(error) {
                        console.log(error.responseJSON.message);
                    }
                })
            }
        });
        $("#loan_apr_id").change(function() {
            if ($(this).val() != "") {
                getDealerFee($(this).val())
            }
        });

        function getDealerFee(value) {
            $.ajax({
                method: "POST",
                url: "{{ route('get.dealer.fee') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: value,
                },
                dataType: 'json',
                success: function(response) {
                    dealerFee(response.dealerfee);
                    calculateCommission()

                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        }

        function getRedlineCost() {
            let panelQty = $("#panel_qty").val();
            let inverterType = $("#inverter_type_id").val();
            let overwriteBaseCost = $("#overwrite_base_price").val();
            overwriteBaseCost = parseFloat(overwriteBaseCost);
            let overwritePanelCost = $("#overwrite_panel_price").val();
            overwritePanelCost = parseFloat(overwritePanelCost);

            $.ajax({
                method: "POST",
                url: "{{ route('get.redline.cost') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    qty: panelQty,
                    inverterType: inverterType,
                },
                dataType: 'json',
                success: function(response) {
                    $('#redline_costs').val('');
                    // if (response.modules.length > 0) {
                    //     $("#module_type_id").empty();
                    //     $('#module_type_id').append($('<option  value="">Select Module Type</option>'));
                    //     $.each(response.modules, function(i, user) {
                    //         $('#module_type_id').append($('<option  value="' + user.id + '">' + user.name + '</option>'));
                    //     });
                    // }
                    baseCost = response.redlinecost;
                    let redlinecost = response.redlinecost  + overwriteBaseCost;
                    $('#redline_costs').val(redlinecost);

                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
            setTimeout(() => {
                if (panelQty != "" && inverterType != "") {
                    let moduleQty = $("#module_qty").val();
                    $("#module_qty").val(panelQty * moduleQty);
                    let totalOverwritePanelCost = overwritePanelCost * panelQty;
                    let redlinecost = baseCost + (panelQty * moduleCost) + overwriteBaseCost +
                    totalOverwritePanelCost;
                    // console.log("Redline Cost", redlinecost);
                    $("#redline_costs").val(redlinecost);
                    // console.log(baseCost);
                }
            }, 2000);
            // }
            calculateCommission()
        }

        function dealerFee(value) {
            let dealerFee = (value != undefined ? value : parseFloat($("#dealer_fee").val()));
            let dealerPercentage = (dealerFee * 100).toFixed(2);
            let contractAmount = parseFloat($('#contract_amount').val());
            if (value != undefined) {
                $('#dealer_fee').val('');
                $('#dealer_fee').val(dealerPercentage);
            }
            if (contractAmount != "" && value != undefined) {
                $('#dealer_fee_amount').val(value * contractAmount);
            } else {
                $('#dealer_fee_amount').val((dealerFee / 100) * contractAmount);
            }
            calculateCommission()
        }

        function calculateCommission() {
            let contractAmount = parseFloat($("#contract_amount").val());
            let dealerFeeAmount = parseFloat($("#dealer_fee_amount").val());
            let redlineFee = parseFloat($("#redline_costs").val());
            let adders = parseFloat($("#adders_amount").val());
            let commission = contractAmount - dealerFeeAmount - redlineFee - adders;
            $("#commission").val(commission.toFixed(2));
        }


        function calculateSystemSize() {
            let moduleQty = $("#module_qty").val();
            modulesType($("#module_type_id").val());
            let panelQty = $("#panel_qty").val();
            let inverterType = $("#inverter_type_id").val();
            let overwritePanelCost = $("#overwrite_panel_price").val();
            let overwriteBaseCost = $("#overwrite_base_price").val();
            let totalOverwritePanelCost = overwritePanelCost * panelQty;
            overwritePanelCost = parseFloat(overwritePanelCost);
            overwriteBaseCost = parseFloat(overwriteBaseCost);

            $("#module_qty").val(panelQty * systemSize);
            let redlinecost = baseCost + (panelQty * moduleCost)+ overwriteBaseCost + totalOverwritePanelCost;
            // console.log("Redline Cost", redlinecost);
            $("#redline_costs").val(redlinecost);
            // console.log("Base Cost", baseCost);
        }

        function calculateSystemSizeAmount() {
            let panelQty = $("#panel_qty").val();
            let moduleQty = $("#module_qty").val();
            let overwritePanelCost = $("#overwrite_panel_price").val();
            overwritePanelCost = parseFloat(overwritePanelCost);
            let totalOverwritePanelCost = overwritePanelCost * panelQty;
            calculateSystemSize();
            getRedlineCost();
            $("#module_qty").val(panelQty * systemSize);
            // console.log(baseCost + " | " + panelQty + " | " + moduleCost);
            let redlinecost = baseCost + (panelQty * moduleCost) + totalOverwritePanelCost;
            $("#redline_costs").val(redlinecost);
        }
        $("#adders").change(function() {
            if ($(this).val() != "") {
                $.ajax({
                    method: "POST",
                    url: "{{ route('get.adders') }}",
                    data: {
                        _token: "{{ csrf_token() }}",
                        // subadder: $(this).val(),
                        adder: $(this).val(),
                    },
                    dataType: 'json',
                    success: function(response) {
                        $("#uom").val(response.adders.adder_unit_id).change();
                        $("#amount").val(response.adders.price);
                    },
                    error: function(error) {
                        console.log(error.responseJSON.message);
                    }
                })
            }
        })

        $("#btnAdder").click(function() {
            let rowLength = $('#adderTable tbody').find('tr').length;
            let adders_id = $("#adders").val();
            // let subadder_id = $("#sub_type").val();
            let unit_id = $("#uom").val();
            let adders_name = $.trim($("#adders option:selected").text());
            // let subadder_name = $.trim($("#sub_type option:selected").text());
            let unit_name = $.trim($("#uom option:selected").text());
            let amount = $("#amount").val();
            if (unit_id == 3) {
                let moduleQty = $('#module_qty').val();
                let panelQty = $('#panel_qty').val();
                amount = amount * moduleQty; //* panelQty;
            }
            let result = checkExistence(adders_id, unit_id);
            if (result == false) {
                let newRow = "<tr id='row" + (rowLength + 1) + "'>" +
                    '<input type="hidden" value="' + adders_id + '" name="adders[]" />' +
                    // '<input type="hidden" value="' + subadder_id + '" name="subadders[]" />' +
                    '<input type="hidden" value="' + unit_id + '" name="uom[]" />' +
                    '<input type="hidden" value="' + amount + '" name="amount[]" />' +


                    "<td>" + (rowLength + 1) + "</td>" +
                    "<td>" + adders_name + "</td>" +
                    // "<td>" + subadder_name + "</td>" +
                    "<td>" + unit_name + "</td>" +
                    "<td>" + amount + "</td>" +

                    "<td colspan='4'>&nbsp;&nbsp;<i style='cursor: pointer;' class='icofont-trash text-danger' onClick=deleteItem(" +
                    (rowLength + 1) + ")>Delete</i></td>" +
                    "</tr>";

                $("#adderTable > tbody").append(newRow);
                calculateAddersAmount();
                emptyControls();
            } else {
                alert("already added")
            }
        });

        function addToTable() {

        }

        function deleteItem(id) {
            $("#row" + id).remove();
            calculateAddersAmount();
        }

        function editItem(id, addersId, uomId, amount) {
            $("#adders").val(addersId).change();
            // $("#sub_type").val(subAdderId).change()
            $("#uom").val(uomId).change();
            $("#amount").val(amount).change();

        }

        function checkExistence(firstval, thirdval) {
            let result = false;
            $("#adderTable tbody tr").each(function(index) {
                let first = $(this).children().eq(0).val();
                // let second = $(this).children().eq(1).val();
                let third = $(this).children().eq(2).val();
                if (firstval == first && thirdval == third) { // && secondval == second
                    result = true;
                } else {
                    result = false;
                }
            });
            return result;
        }

        function calculateAddersAmount() {
            let adders_amount = 0;
            $("#adderTable tbody tr").each(function(index) {
                adders_amount += $(this).children().eq(6).text() * 1;
            });
            $("#adders_amount").val(adders_amount);
            calculateCommission();
        }

        function emptyControls() {
            $("#adders").val('').change();
            // $("#sub_type").val('').change();
            $("#uom").val('').change();
            $("#amount").val('');
        }

        $("#module_type_id").change(function() {
            modulesType($(this).val());
        });

        function modulesType(id) {
            if (id != "") {
                $("#inverter_type_id").prop("disabled", false)
                $.ajax({
                    method: "POST",
                    url: "{{ route('get.module.types') }}",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id,
                        inverterTypeId: $("#inverter_type_id").val()
                    },
                    dataType: 'json',
                    success: function(response) {
                        // console.log(response);
                        moduleCost = response.types.amount;
                        systemSize = response.types.value;
                        $("#module_qty").val(response.types.value);
                    },
                    error: function(error) {
                        console.log(error.responseJSON.message);
                    }
                })
            } else {
                // $("#inverter_type_id").prop("disabled", true)
            }
        }
    </script>
@endsection
