@extends('layouts.master')
@section('title', 'Create Intake Form')
@section('content')
<style>
    .premium-gradient {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    }
    .premium-card {
        border-radius: 20px;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
        border: none;
        overflow: hidden;
    }
    .premium-header {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        color: white;
        padding: 25px;
        border-radius: 20px 20px 0 0;
    }
    .premium-input {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 15px;
        transition: all 0.3s ease;
    }
    .premium-input:focus {
        border-color: #4a5568;
        box-shadow: 0 0 0 0.2rem rgba(45, 55, 72, 0.25);
    }
    .premium-label {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 8px;
    }
    .premium-section {
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        padding: 20px;
        border-radius: 15px;
        margin: 20px 0;
        border-left: 5px solid #2d3748;
    }
    .premium-btn {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        border: none;
        color: white;
        border-radius: 10px;
        padding: 12px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(45, 55, 72, 0.4);
    }
    .premium-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(45, 55, 72, 0.6);
    }
    .premium-table {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }
    .premium-table thead tr th {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        color: white;
    }
    .premium-table tbody tr:hover {
        background-color: #f7fafc;
        transform: scale(1.01);
        transition: all 0.3s ease;
    }
    .select2-container--default .select2-selection--single {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        height: 45px;
        padding: 5px;
    }
</style>

<div class="premium-card">
    <div class="premium-header">
        <div class="d-sm-flex align-items-center justify-content-between">
            <h3 class="fw-bold mb-0">
                <i class="icofont-ui-note me-2"></i>New Intake Form
            </h3>
            <a href="{{ route('intake-form.index') }}" class="btn btn-light mt-2 mt-sm-0">
                <i class="icofont-arrow-left me-2"></i>Back to List
            </a>
        </div>
    </div>

    <div class="card-body p-4">
        <form id="form" method="post" action="{{ route('intake-form.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="overwrite_base_price" name="overwrite_base_price" />
            <input type="hidden" id="overwrite_panel_price" name="overwrite_panel_price" />

            <div class="premium-section">
                <h5 class="fw-bold mb-4" style="color: #2d3748;">
                    <i class="icofont-user me-2"></i>Customer Information
                </h5>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label premium-label">First Name</label>
                        <input type="text" class="form-control premium-input" id="first_name" name="first_name" placeholder="Enter first name">
                        @error('first_name')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label premium-label">Last Name</label>
                        <input type="text" class="form-control premium-input" id="last_name" name="last_name" placeholder="Enter last name">
                        @error('last_name')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label premium-label">Street</label>
                        <input type="text" class="form-control premium-input" id="street" name="street" placeholder="Enter street">
                        @error('street')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label premium-label">City</label>
                        <input type="text" class="form-control premium-input" id="city" name="city" placeholder="Enter city">
                        @error('city')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label premium-label">State</label>
                        <input type="text" class="form-control premium-input" id="state" name="state" placeholder="Enter state">
                        @error('state')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label premium-label">Zip Code</label>
                        <input type="text" class="form-control premium-input" id="zipcode" name="zipcode" placeholder="Enter zip code">
                        @error('zipcode')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label premium-label">Phone</label>
                        <input type="text" class="form-control premium-input" id="phone" name="phone" placeholder="Enter phone">
                        @error('phone')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label premium-label">Email</label>
                        <input type="text" class="form-control premium-input" id="email" name="email" placeholder="Enter email">
                        @error('email')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="premium-section">
                <h5 class="fw-bold mb-4" style="color: #2d3748;">
                    <i class="icofont-briefcase me-2"></i>Sales & Partnership Details
                </h5>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label premium-label">Sold Date</label>
                        <input type="date" class="form-control premium-input" id="sold_date" name="sold_date">
                        @error('sold_date')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label premium-label">Sales Partner</label>
                        <select class="form-select select2 premium-input" id="sales_partner_id" name="sales_partner_id">
                            <option value="">Select Sales Partner</option>
                            @foreach ($partners as $partner)
                                <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                            @endforeach
                        </select>
                        @error('sales_partner_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label premium-label">Sales Partner User</label>
                        <select class="form-select select2 premium-input" id="sales_partner_user_id" name="sales_partner_user_id">
                            <option value="">Select Sales Partner User</option>
                        </select>
                        @error('sales_partner_user_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label premium-label">Sub-Contractors</label>
                        <select class="form-select select2 premium-input" id="sub_contractor_id" name="sub_contractor_id">
                            <option value="">Select Sub-Contractors</option>
                            @foreach ($contractors as $contractor)
                                <option value="{{ $contractor->id }}">{{ $contractor->name }}</option>
                            @endforeach
                        </select>
                        @error('sub_contractor_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label premium-label">Sub-Contractor User</label>
                        <select class="form-select select2 premium-input" id="sub_contractor_user_id" name="sub_contractor_user_id">
                            <option value="">Select Sub-Contractor User</option>
                        </select>
                        @error('sub_contractor_user_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="premium-section">
                <h5 class="fw-bold mb-4" style="color: #2d3748;">
                    <i class="icofont-solar-panel me-2"></i>System Configuration
                </h5>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label premium-label">Inverter Type</label>
                        <select class="form-select select2 premium-input" id="inverter_type_id" name="inverter_type_id" onchange="getRedlineCost()">
                            <option value="">Select Inverter Type</option>
                            @foreach ($inverter_types as $inverter)
                                <option value="{{ $inverter->id }}">{{ $inverter->name }}</option>
                            @endforeach
                        </select>
                        @error('inverter_type_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label premium-label">Module Type</label>
                        <select class="form-select select2 premium-input" id="module_type_id" name="module_type_id" onchange="calculateSystemSize()">
                            <option value="">Select Module Type</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module->id }}">{{ $module->inverter->name . ' ' . $module->name }}</option>
                            @endforeach
                        </select>
                        @error('module_type_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label premium-label">Panel Qty</label>
                        <input type="text" class="form-control premium-input" id="panel_qty" name="panel_qty" placeholder="Enter panel quantity" onblur="calculateSystemSizeAmount()">
                        @error('panel_qty')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label premium-label">System Size</label>
                        <input type="text" class="form-control premium-input" id="module_qty" name="module_qty" placeholder="System size">
                        @error('module_qty')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label premium-label">Inverter Qty</label>
                        <input type="text" class="form-control premium-input" id="inverter_qty" name="inverter_qty" placeholder="Inverter quantity">
                        @error('inverter_qty')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label premium-label">Is ADU?</label>
                        <select class="form-select select2 premium-input" id="adu" name="adu">
                            <option value="">Select ADU</option>
                            <option value="1">Yes</option>
                            <option selected value="0">No</option>
                        </select>
                        @error('adu')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div id="loadIdDiv" class="col-sm-6">
                        <label class="form-label premium-label">Loan Id</label>
                        <input type="text" class="form-control premium-input" id="loanId" name="loanId" placeholder="Enter loan ID">
                        @error('loanId')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div id="soldProductionValueDiv" class="col-sm-6">
                        <label class="form-label premium-label">Sold Production Value</label>
                        <input type="text" class="form-control premium-input" id="sold_production_value" name="sold_production_value" placeholder="Enter sold production value">
                        @error('sold_production_value')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="premium-section">
                <h5 class="fw-bold mb-4" style="color: #2d3748;">
                    <i class="icofont-plus-square me-2"></i>Adders
                </h5>
                <div class="row g-3 mb-3">
                    <div class="col-sm-3">
                        <label class="form-label premium-label">Adders</label>
                        <select class="form-select select2 premium-input" id="adders" name="adders">
                            <option value="">Select Adders</option>
                            @foreach ($adders as $adder)
                                <option value="{{ $adder->id }}">{{ $adder->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label premium-label">UOM</label>
                        <select class="form-select select2 premium-input" id="uom">
                            <option value="">Select UOM</option>
                            @foreach ($uoms as $uom)
                                <option value="{{ $uom->id }}">{{ $uom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label premium-label">Amount</label>
                        <input type="text" class="form-control premium-input" id="amount" name="amount" placeholder="Enter amount">
                    </div>
                    <div class="col-sm-3 d-flex align-items-end">
                        <button type="button" id="btnAdder" class="btn premium-btn w-100">
                            <i class="icofont-plus me-2"></i>Add
                        </button>
                    </div>
                </div>
                <table id="adderTable" class="table premium-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Adder</th>
                            <th>Unit</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="premium-section">
                <h5 class="fw-bold mb-4" style="color: #2d3748;">
                    <i class="icofont-dollar me-2"></i>Customer Financing
                </h5>
                <div class="row g-3">
                    <div class="col-sm-3">
                        <label class="form-label premium-label">Finance Option</label>
                        <select class="form-select select2 premium-input" id="finance_option_id" name="finance_option_id">
                            <option value="">Select Finance Option</option>
                            @foreach ($financeoptions as $financeOption)
                                <option value="{{ $financeOption->id }}">{{ $financeOption->name }}</option>
                            @endforeach
                        </select>
                        @error('finance_option_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 loandiv">
                        <label class="form-label premium-label">Loan Term</label>
                        <select class="form-select select2 premium-input" id="loan_term_id" name="loan_term_id">
                            <option value="">Select Loan Term</option>
                        </select>
                        @error('loan_term_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 loandiv">
                        <label class="form-label premium-label">Loan Apr</label>
                        <select class="form-select select2 premium-input" id="loan_apr_id" name="loan_apr_id">
                            <option value="">Select Loan Apr</option>
                        </select>
                        @error('loan_apr_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label premium-label">Contract Amount</label>
                        <input type="text" class="form-control premium-input" id="contract_amount" name="contract_amount" placeholder="0" onblur="dealerFee()" value="0">
                        @error('contract_amount')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label premium-label">Redline Costs</label>
                        <input type="text" class="form-control premium-input" id="redline_costs" name="redline_costs" placeholder="0" value="0">
                        @error('redline_costs')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label premium-label">Adders</label>
                        <input type="text" class="form-control premium-input" id="adders_amount" name="adders_amount" placeholder="0" value="0">
                        @error('adders')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label premium-label">Commission</label>
                        <input type="text" class="form-control premium-input" id="commission" name="commission" placeholder="0" value="0">
                        @error('commission')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label premium-label">Dealer Fee</label>
                        <input readonly type="text" class="form-control premium-input" id="dealer_fee" name="dealer_fee" placeholder="0" value="0">
                        @error('dealer_fee')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label premium-label">Dealer Fee Amount</label>
                        <input type="text" class="form-control premium-input" id="dealer_fee_amount" name="dealer_fee_amount" placeholder="0" value="0">
                        @error('dealer_fee_amount')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn premium-btn btn-lg px-5">
                    <i class="icofont-save me-2"></i>Create Intake Form
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('customer/create.js') }}"></script>
@endsection
