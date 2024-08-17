@extends('layouts.master')
@section('title', 'Reports')
@section('content')
    <div class="body d-flex py-lg-3 py-md-2">
        <div class="container-xxl">
            <div class="row clearfix">
                <div class="col-md-12">
                    <div class="card border-0 mb-4 no-bg">
                        <div
                            class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Profitablity Report</h3>
                        </div>
                    </div>
                </div>
            </div><!-- Row End -->
            <div class="card mt-1">
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-3 mt-4">
                            <label class="form-label">Sales Partner</label>
                            <select class="form-select select2" aria-label="Default select Sales Partner"
                                id="sales_partner_id" name="sales_partner_id" style="width:100%">
                                <option value="">Select Sales Partner</option>
                                @foreach ($partners as $partner)
                                    <option value="{{ $partner->id }}">
                                        {{ $partner->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sales_partner_id')
                                <div id="salespartner_message" class="text-danger message mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-2 mb-3">
                            <label for="from_date" class="form-label">From </label>
                            <input type="date" class="form-control" id="from_date" name="from_date"
                                placeholder="Enter From Date">
                            @error('from_date')
                                <div id="from_message" class="text-danger message mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-2 mb-3">
                            <label for="to_date" class="form-label">To </label>
                            <input type="date" class="form-control" id="to_date" name="to_date"
                                placeholder="Enter From Date">
                            @error('to_date')
                                <div id="to_message" class="text-danger message mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-4">
                            <button class="btn btn-primary mt-4 float-right" type="button" onclick="generateReport()"><i
                                    class="icofont-save"></i> Submit</button>
                            <button class="btn btn-success mt-4 float-right text-white" style="background-color: green"
                                type="button" onclick="excelExport()"><i class="icofont-file-excel"></i> Excel
                                Export</button>
                            <button class="btn btn-danger mt-4 float-right text-white" type="button"
                                onclick="pdfExport()"><i class="icofont-file-pdf"></i> PDF Export</button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="reporttable"></div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        function generateReport() {
            if ($("#from_date").val() == "") {
                $("#from_message").html("Please select from date")
            } else if ($("#to_date").val() == "") {
                $("#to_message").html("Please select to date")
            } else {
                $("#salespartner_message").html("");
                $("#from_message").html("");
                $("#to_message").html("");

                $.ajax({
                    url: "{{ route('reports.profit') }}",
                    method: "POST",
                    data: {
                        "_token": "{{ csrf_token() }}",
                        sales_partner_id: $("#sales_partner_id").val(),
                        from: $("#from_date").val(),
                        to: $("#to_date").val(),
                    },
                    success: function(response) {
                        console.log(response);
                        $("#reporttable").empty();
                        $("#reporttable").html(response);

                    }
                })
            }
        }

        function excelExport() {
            window.open("{{ url('profitable-report-excel-export') }}" + "/" + $("#from_date").val() + "/" + $("#to_date")
                .val());
        }

        function pdfExport() {
            window.open("{{ url('profitable-report-pdf-export') }}" + "/" + $("#from_date").val() + "/" + $("#to_date")
                .val());
        }
    </script>
@endsection
