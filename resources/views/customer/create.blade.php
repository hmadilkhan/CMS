@extends('layouts.master')
@section('title', 'Create Customer')
@section('content')
    <div class="card card-info">
        <div class="card-body">
            <div class="row clearfix">
                <div class="col-md-12">
                    <div class="card border-0 mb-4 no-bg">
                        <div
                            class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Customer</h3>
                            <a href="{{ route('customers.index') }}" class="btn btn-dark me-1 mt-1 w-sm-100"
                                id="openemployee"><i class="icofont-arrow-left me-2 fs-6"></i>Back to List</a>
                        </div>
                    </div>
                </div>
            </div><!-- Row End -->
            <form id="form" method="post" action="{{ route('customers.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="overwrite_base_price" name="overwrite_base_price" />
                <input type="hidden" id="overwrite_panel_price" name="overwrite_panel_price" />
                <div class="row g-3 mb-3">
                    <div class="col-sm-6 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                            placeholder="First Name">
                        @error('first_name')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name">
                        @error('last_name')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">Street</label>
                        <input type="text" class="form-control" id="street" name="street" placeholder="Street">
                        @error('street')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city" placeholder="City">
                        @error('city')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">State</label>
                        <input type="text" class="form-control" id="state" name="state" placeholder="State">
                        @error('state')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">Zip Code</label>
                        <input type="text" class="form-control" id="zipcode" name="zipcode" placeholder="Zip Code">
                        @error('zipcode')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="phone">
                        @error('phone')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">Email</label>
                        <input type="text" class="form-control" id="email" name="email" placeholder="Email">
                        @error('email')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label for="sold_date" class="form-label">Sold Date</label>
                        <input type="date" class="form-control" id="sold_date" name="sold_date"
                            placeholder="Sold Date">
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
                                <option value="{{ $partner->id }}">
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
                        </select>
                        @error('sales_partner_user_id')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-sm-4">
                        <label class="form-label">Inverter Type</label>
                        <select class="form-select select2" aria-label="Default select Inverter Type"
                            id="inverter_type_id" name="inverter_type_id" onchange="getRedlineCost()">
                            <option value="">Select Inverter Type</option>
                            @foreach ($inverter_types as $inverter)
                                <option value="{{ $inverter->id }}">
                                    {{ $inverter->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('inverter_type_id')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-sm-4">
                        <label class="form-label">Module Type</label>
                        <select class="form-select select2" aria-label="Default select Module Type" id="module_type_id"
                            name="module_type_id" onchange="calculateSystemSize()">
                            <option value="">Select Module Type</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module->id }}">
                                    {{ $module->inverter->name . ' ' . $module->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('module_type_id')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>


                    <div class="col-sm-4">
                        <label for="code" class="form-label">Panel Qty</label>
                        <input type="text" class="form-control" id="panel_qty" name="panel_qty"
                            placeholder="Panel Qty" onblur="calculateSystemSizeAmount()">
                        @error('panel_qty')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- <div class="col-sm-4"> -->
                    <!-- <label class="form-label">Battery Type</label>
                                                        <select class="form-select select2" aria-label="Default select Battery Type" id="battery_type_id" name="battery_type_id">
                                                            <option value="">Select Battery Type</option>
                                                            @foreach ($battery_types as $battery)
    <option value="{{ $battery->id }}">
                                                                {{ $battery->name }}
                                                            </option>
    @endforeach
                                                        </select>
                                                        @error('battery_type_id')
        <div class="text-danger message mt-2">{{ $message }}</div>
    @enderror -->
                    <!-- </div> -->
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">System Size</label>
                        <input type="text" class="form-control" id="module_qty" name="module_qty"
                            placeholder="System Size">
                        @error('module_qty')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="exampleFormControlInput877" class="form-label">Inverter Qty</label>
                        <input type="text" class="form-control" id="inverter_qty" name="inverter_qty"
                            placeholder="Inverter Qty">
                        @error('inverter_qty')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-sm-4 mt-4">
                        <label for="adu" class="form-label">Is ADU?</label></br>
                        <select class="form-select select2" aria-label="Default select ADU" id="adu"
                            name="adu">
                            <option value="">Select ADU</option>
                            <option value="1">Yes</option>
                            <option selected value="0">No</option>
                        </select>
                        @error('adu')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="loadIdDiv" class="col-sm-4 ">
                        <label for="exampleFormControlInput877" class="form-label">Loan Id</label>
                        <input type="text" class="form-control" id="loanId" name="loanId" placeholder="loan Id">
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
                        <!-- <label for="exampleFormControlInput877" class="form-label">Battery Qty</label>
                                                        <input type="text" class="form-control" id="battery_qty" name="battery_qty" placeholder="Battery Qty">
                                                        @error('battery_qty')
        <div class="text-danger message mt-2">{{ $message }}</div>
    @enderror -->
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
                    <!-- <div class="col-sm-3 mb-3">
                                                        <label for="sub_type" class="form-label">Sub Type</label>
                                                        <select class="form-select select2" aria-label="Default select Sub Type" id="sub_type" name="sub_type">
                                                            <option value="">Select Sub Type</option>
                                                        </select>
                                                    </div> -->
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
                            placeholder="Adders Amount">
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
                                <option value="{{ $financeOption->id }}">
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
                            placeholder="Contract Amount" onblur="dealerFee()" value="0">
                        @error('contract_amount')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="redline_costs" class="form-label">Redline Costs</label>
                        <input type="text" class="form-control" id="redline_costs" name="redline_costs"
                            placeholder="Redline Costs" value="0">
                        @error('redline_costs')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="adders" class="form-label">Adders</label>
                        <input type="text" class="form-control" id="adders_amount" name="adders_amount"
                            placeholder="Adders" value="0">
                        @error('adders')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="commission" class="form-label">Commission</label>
                        <input type="text" class="form-control" id="commission" name="commission"
                            placeholder="Commission" value="0">
                        @error('commission')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="dealer_fee" class="form-label">Dealer Fee</label>
                        <input readonly type="text" class="form-control" id="dealer_fee" name="dealer_fee"
                            placeholder="Dealer Fee" value="0">
                        @error('dealer_fee')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="dealer_fee_amount" class="form-label">Dealer Fee Amount</label>
                        <input type="text" class="form-control" id="dealer_fee_amount" name="dealer_fee_amount"
                            placeholder="Dealer Fee Amount" value="0">
                        @error('dealer_fee_amount')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="icofont-save me-2 fs-6"></i>Create</button>
            </form>

        </div>
    </div>
@endsection
@section('scripts')
    <script>
        var moduleCost = 0;
        var systemSize = 0;
        $(document).ready(function() {
            $(".loandiv").css("display", "none");
        });

        function getFinanceOptionById(id) {
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
                        if (finance.dealer_fee == 1) {
                            $(".loandiv").css("display", "block");
                            getLoanTerms(id);
                        } else {
                            $(".loandiv").css("display", "none");
                            $("#dealer_fee").val(0);
                            $("#dealer_fee_amount").val(0);
                            calculateCommission()
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
            getFinanceOptionById($(this).val())
            // if ($(this).val() != 1 && $(this).val() != 5) {
            //     $(".loandiv").css("display", "block");
            // } else {
            //     $(".loandiv").css("display", "none");
            // }
            // if ($(this).val() != 1 && $(this).val() != 5) {
            //     $.ajax({
            //         method: "POST",
            //         url: "{{ route('get.loan.terms') }}",
            //         data: {
            //             _token: "{{ csrf_token() }}",
            //             id: $(this).val(),
            //         },
            //         dataType: 'json',
            //         success: function(response) {
            //             $('#loan_term_id').empty();
            //             $('#loan_term_id').append($('<option value="">Select Loan Term</soption>'));
            //             $.each(response.terms, function(i, term) {
            //                 $('#loan_term_id').append($('<option  value="' + term.id + '">' +
            //                     term.year + '</option>'));
            //             });
            //         },
            //         error: function(error) {
            //             console.log(error.responseJSON.message);
            //         }
            //     })
            // } else {
            //     $("#dealer_fee").val(0);
            //     $("#dealer_fee_amount").val(0);
            //     calculateCommission()
            // }
        });

        function getLoanTerms(id) {
            $.ajax({
                method: "POST",
                url: "{{ route('get.loan.terms') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id,
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
        }
        $("#loan_term_id").change(function() {
            if ($(this).val() != "") {
                $.ajax({
                    method: "POST",
                    url: "{{ route('get.loan.aprs') }}",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: $(this).val(),
                        finance_option_id: $("#finance_option_id").val(),
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
        var baseCost = 0;

        function getRedlineCost() {
            let panelQty = $("#panel_qty").val();
            let inverterType = $("#inverter_type_id").val();
            let overwriteBaseCost = $("#overwrite_base_price").val();
            overwriteBaseCost = parseFloat(overwriteBaseCost);
            let overwritePanelCost = $("#overwrite_panel_price").val();
            overwritePanelCost = parseFloat(overwritePanelCost);
            // console.log(overwriteBaseCost);
            // overwriteBaseCost = (overwriteBaseCost != 0.00 )


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
                    if (response.modules.length > 0) {
                        $("#module_type_id").empty();
                        $('#module_type_id').append($('<option  value="">Select Module Type</option>'));
                        $.each(response.modules, function(i, user) {
                            $('#module_type_id').append($('<option  value="' + user.id + '">' + user
                                .name + '</option>'));
                        });
                    }
                    baseCost = response.redlinecost;
                    let redlinecost = response.redlinecost + overwriteBaseCost;
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
            let redlinecost = baseCost + (panelQty * moduleCost) + overwriteBaseCost + totalOverwritePanelCost;
            // console.log("Redline Cost", redlinecost);
            $("#redline_costs").val(redlinecost);
            // console.log("Base Cost", baseCost);
        }

        function calculateSystemSizeAmount() {
            let panelQty = $("#panel_qty").val();
            let moduleQty = $("#module_qty").val();
            let overwritePanelCost = $("#overwrite_panel_price").val();
            let overwriteBaseCost = $("#overwrite_base_price").val();
            overwritePanelCost = parseFloat(overwritePanelCost);
            overwriteBaseCost = parseFloat(overwriteBaseCost);
            let totalOverwritePanelCost = overwritePanelCost * panelQty;
            $("#module_qty").val(panelQty * systemSize);
            let redlinecost = baseCost + (panelQty * moduleCost) + totalOverwritePanelCost + overwriteBaseCost;
            $("#redline_costs").val(redlinecost);
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
            if (unit_id == 5) {
                let moduleQty = $('#module_qty').val();
                let panelQty = $('#panel_qty').val();
                amount = amount * panelQty; //* panelQty;
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

        function deleteItem(id) {
            $("#row" + id).remove();
            calculateAddersAmount();
            calculateCommission();
        }

        function editItem(id, addersId, uomId, amount) { //subAdderId
            $("#adders").val(addersId).change();
            // $("#sub_type").val(subAdderId).change()
            $("#uom").val(uomId).change();
            $("#amount").val(amount).change();

        }
        //secondval
        function checkExistence(firstval, thirdval) {
            let result = false;
            $("#adderTable tbody tr").each(function(index) {
                let first = $(this).children().eq(0).val();
                // let second = $(this).children().eq(1).val();
                let third = $(this).children().eq(2).val();
                if (firstval == first && thirdval == third) { //&& secondval == second
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
        }

        function emptyControls() {
            $("#adders").val('').change();
            // $("#sub_type").val('').change();
            $("#uom").val('').change();
            $("#amount").val('');
        }




        $("#sales_partner_id").change(function() {
            $('#sales_partner_user_id').empty();
            $.ajax({
                method: "POST",
                url: "{{ route('get.salespartnets.users') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: $(this).val(),
                },
                dataType: 'json',
                success: function(response) {
                    $('#sales_partner_user_id').append(
                        "<option value=''>Select Sales Person User</option> ");
                    $.each(response.users, function(i, user) {
                        $('#sales_partner_user_id').append($('<option  value="' + user.id +
                            '">' + user.name + '</option>'));
                    });
                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        })

        $("#sales_partner_user_id").change(function() {
            $.ajax({
                method: "POST",
                url: "{{ route('sales.partner.overwrite.prices') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: $(this).val(),
                },
                dataType: 'json',
                success: function(response) {
                    // console.log(response.overwrites.overwrite_base_price);
                    // console.log(response.overwrites.overwrite_panel_price);
                    $("#overwrite_base_price").val(response.overwrites.overwrite_base_price)
                    $("#overwrite_panel_price").val(response.overwrites.overwrite_panel_price)
                    getRedlineCost();
                    calculateSystemSize();
                    calculateSystemSizeAmount();

                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        });
    </script>
@endsection
