@extends('layouts.master')
@section('title', 'Forecast Report')
@section('content')
    @include('operations.partials.index-styles')
    <div class="body d-flex py-lg-3 py-md-2">
        <div class="container-xxl">
            <div class="operation-page-header">
                <div>
                    <h1 class="operation-page-title"><i class="icofont-adjust me-2"></i>Override Report</h1>
                    <p class="operation-page-subtitle">Review base and panel override costs by sales partner and date range.</p>
                </div>
            </div>
            <div class="operation-card mt-1">
                <div class="card-body">
                    <div class="operation-form row g-3 align-items-end mb-3">
                        <div class="col-sm-3">
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
                        <div class="col-sm-3">
                            <label for="from_date" class="form-label">From </label>
                            <input type="date" class="form-control" id="from_date" name="from_date"
                                placeholder="Enter From Date">
                            @error('from_date')
                                <div id="from_message" class="text-danger message mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-3">
                            <label for="to_date" class="form-label">To </label>
                            <input type="date" class="form-control" id="to_date" name="to_date"
                                placeholder="Enter From Date">
                            @error('to_date')
                                <div id="to_message" class="text-danger message mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-3">
                            <div class="operation-actions">
                            <button class="btn btn-primary" type="button" onclick="generateReport()"><i
                                    class="icofont-save"></i> Submit</button>
                            <button class="btn btn-success text-white"
                                type="button" onclick="excelExport()"><i class="icofont-file-excel"></i> Excel
                                Export</button>
                            <button class="btn btn-danger text-white" type="button"
                                onclick="pdfExport()"><i class="icofont-file-pdf"></i> PDF Export</button>
                            </div>
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
                $("#from_message").html("");
                $("#to_message").html("");

                $.ajax({
                    url: "{{ route('override.report') }}",
                    method: "POST",
                    data: {
                        "_token": "{{ csrf_token() }}",
                        sales_partner_id: $("#sales_partner_id").val(),
                        from: $("#from_date").val(),
                        to: $("#to_date").val(),
                    },
                    success: function(response) {
                        $("#reporttable").empty();
                        $("#reporttable").html(response);

                    }
                })
            }
        }

        function excelExport() {
            window.open("{{ url('override-report-excel-export') }}" + "/" + $("#sales_partner_id").val() + "/" + $("#from_date").val() + "/" + $("#to_date")
                .val());
        }

        function pdfExport() {
            window.open("{{ url('override-report-pdf-export') }}" + "/" + $("#sales_partner_id").val() + "/" + $("#from_date").val() + "/" + $("#to_date")
                .val());
        }
    </script>
@endsection
