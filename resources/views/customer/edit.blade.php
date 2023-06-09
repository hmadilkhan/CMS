@extends("layouts.master")
@section('title', 'Create Customer')
@section('content')
<div class="card card-info">
    <div class="card-body">
        <div class="row clearfix">
            <div class="col-md-12">
                <div class="card border-0 mb-4 no-bg">
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Edit Customer</h3>
                        <a href="{{route('customers.index')}}" class="btn btn-dark me-1 mt-1 w-sm-100" id="openemployee"><i class="icofont-arrow-left me-2 fs-6"></i>Back to List</a>
                    </div>
                </div>
            </div>
        </div><!-- Row End -->
        <form id="form" method="post" action="{{route('customers.update',$customer->id)}}" enctype="multipart/form-data">
            @method("PUT")
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-sm-6 mb-3">
                    <label for="exampleFormControlInput877" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" value="{{$customer->first_name}}">
                    @error("first_name")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="exampleFormControlInput877" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" value="{{$customer->last_name}}">
                    @error("last_name")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-3 mb-3">
                    <label for="exampleFormControlInput877" class="form-label">Street</label>
                    <input type="text" class="form-control" id="street" name="street" placeholder="Street" value="{{$customer->street}}">
                    @error("street")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-3 mb-3">
                    <label for="exampleFormControlInput877" class="form-label">City</label>
                    <input type="text" class="form-control" id="city" name="city" placeholder="City" value="{{$customer->city}}">
                    @error("city")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-3 mb-3">
                    <label for="exampleFormControlInput877" class="form-label">State</label>
                    <input type="text" class="form-control" id="state" name="state" placeholder="State" value="{{$customer->state}}">
                    @error("state")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-3 mb-3">
                    <label for="exampleFormControlInput877" class="form-label">Zip Code</label>
                    <input type="text" class="form-control" id="zipcode" name="zipcode" placeholder="Zip Code" value="{{$customer->zipcode}}">
                    @error("zipcode")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-4">
                    <label for="code" class="form-label">System Size</label>
                    <input type="text" class="form-control" id="system_size" name="system_size" placeholder="System Size" value="{{$customer->system_size}}">
                    @error("system_size")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-4">
                    <label for="sold_date" class="form-label">Sold Date</label>
                    <input type="date" class="form-control" id="sold_date" name="sold_date" placeholder="Sold Date" value="{{$customer->sold_date}}">
                    @error("sold_date")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Sales Partner</label>
                    <select class="form-select select2" aria-label="Default select Sales Partner" id="sales_partner_id" name="sales_partner_id" >
                        <option value="">Select Sales Partner</option>
                        @foreach ($partners as $partner)
                        <option @selected($customer->sales_partner_id == $partner->id) value="{{ $partner->id }}">
                            {{ $partner->name }}
                        </option>
                        @endforeach
                    </select>
                    @error("sales_partner_id")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="addnote" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3">{{$customer->address}}</textarea>
                    @error("address")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="addnote" class="form-label">Scope of Work</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3">{{$customer->notes}}</textarea>
                    @error("notes")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
            </div>

            <div class="row clearfix">
                <div class="col-md-12">
                    <div class="card border-0 mb-4 no-bg">
                        <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                            <h6 class=" fw-bold flex-fill mb-0 mt-sm-0">Customer Financing</h6>
                        </div>
                    </div>
                </div>
            </div><!-- Row End -->
            <div class="row g-3 mb-3">
                <div class="col-sm-6 mb-3">
                    <label for="finance_option_id" class="form-label">Finance Option</label>
                    <label class="form-label">Finance Option</label>
                    <select class="form-select select2" aria-label="Default select Finance Option" id="finance_option_id" name="finance_option_id">
                        <option value="">Select Finance Option</option>
                        @foreach ($financeoptions as $financeOption)
                        <option @selected($customer->finances->finance_option_id == $financeOption->id) value="{{ $financeOption->id }}">
                            {{ $financeOption->name }}
                        </option>
                        @endforeach
                    </select>
                    @error("finance_option_id")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="contract_amount" class="form-label">Contract Amount</label>
                    <input type="text" class="form-control" id="contract_amount" name="contract_amount" placeholder="Contract Amount" value="{{$customer->finances->contract_amount}}">
                    @error("contract_amount")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-3 mb-3">
                    <label for="redline_costs" class="form-label">Redline Costs</label>
                    <input type="text" class="form-control" id="redline_costs" name="redline_costs" placeholder="Redline Costs" value="{{$customer->finances->redline_costs}}">
                    @error("redline_costs")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-3 mb-3">
                    <label for="adders" class="form-label">Adders</label>
                    <input type="text" class="form-control" id="adders" name="adders" placeholder="Adders" value="{{$customer->finances->adders}}">
                    @error("adders")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-3 mb-3">
                    <label for="commission" class="form-label">Commission</label>
                    <input type="text" class="form-control" id="commission" name="commission" placeholder="Commission" value="{{$customer->finances->commission}}">
                    @error("commission")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-3 mb-3">
                    <label for="dealer_fee" class="form-label">Dealer Fee</label>
                    <input type="text" class="form-control" id="dealer_fee" name="dealer_fee" placeholder="Dealer Fee" value="{{$customer->finances->dealer_fee}}">
                    @error("dealer_fee")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
            </div>



            <button type="submit" class="btn btn-primary"><i class="icofont-pencil me-2 fs-6"></i>Update</button>
        </form>

    </div>
</div>
@endsection