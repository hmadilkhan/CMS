@extends('layouts.master')
@section('title', 'Reports')
@section('content')
    @include('operations.partials.index-styles')
    <div class="body d-flex py-lg-3 py-md-2">
        <div class="container-xxl">
            <div class="operation-page-header">
                <div>
                    <h1 class="operation-page-title"><i class="icofont-money-bag me-2"></i>Transaction Report</h1>
                    <p class="operation-page-subtitle">Review project transaction remittance and deduction details.</p>
                </div>
            </div>
            <div class="operation-card mt-1">
                <div class="card-body">
                    <div class="operation-form row g-3 align-items-end mb-3">
                        <div class="col-sm-2">
                            <label for="from_date" class="form-label">From </label>
                            <input type="date" class="form-control" id="from_date" name="from_date"
                                placeholder="Enter From Date">
                            @error('from_date')
                                <div id="from_message" class="text-danger message mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-2">
                            <label for="to_date" class="form-label">To </label>
                            <input type="date" class="form-control" id="to_date" name="to_date"
                                placeholder="Enter From Date">
                            @error('to_date')
                                <div id="to_message" class="text-danger message mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-5">
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
                $("#salespartner_message").html("");
                $("#from_message").html("");
                $("#to_message").html("");
                getReport($("#from_date").val(), $("#to_date").val());
            }
        }
        getReport('', '');

        function getReport(startDate, endDate) {
            $.ajax({
                url: "{{ route('transaction.report') }}",
                method: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    start_date: startDate,
                    end_date: endDate,
                },
                success: function(response) {
                    $("#reporttable").empty();
                    $("#reporttable").html(response);

                }
            })
        }

        function excelExport() {
            window.open("{{ url('transaction-report-excel-export') }}" + "/" + $("#from_date").val() + "/" + $("#to_date")
                .val());
        }

        function pdfExport() {
            window.open("{{ url('transaction-report-pdf-export') }}" + "/" + $("#from_date").val() + "/" + $("#to_date")
                .val());
        }
    </script>
@endsection
