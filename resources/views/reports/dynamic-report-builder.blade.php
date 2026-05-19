@extends('layouts.master')

@section('title', 'Dynamic Report Builder')

@section('content')
@include('operations.partials.index-styles')
<style>
    .report-builder-page .card {
        background: #ffffff !important;
        border: 0 !important;
        border-top: 1px solid rgba(238, 143, 69, 0.28) !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    .report-builder-page .card-header {
        background: transparent !important;
        border: 0 !important;
        border-bottom: 1px solid rgba(238, 143, 69, 0.16) !important;
        padding: 0.85rem 0 !important;
    }

    .report-builder-page .card-title,
    .report-builder-page .card-header h3,
    .report-builder-page .card-header h5,
    .report-builder-page .card-header h6 {
        color: #1f2937 !important;
        font-weight: 700;
    }

    .report-builder-page .card-body {
        padding: 1rem 0 1.35rem !important;
    }

    .report-builder-page .form-control,
    .report-builder-page .form-select {
        border: 1px solid rgba(238, 143, 69, 0.28) !important;
        border-radius: 999px !important;
        min-height: 42px;
    }

    .report-builder-page textarea.form-control {
        border-radius: 16px !important;
    }

    .report-builder-page .form-control:focus,
    .report-builder-page .form-select:focus {
        border-color: var(--solen-primary, #ee8f45) !important;
        box-shadow: 0 0 0 0.2rem rgba(238, 143, 69, 0.16) !important;
    }

    .report-builder-page .btn,
    .report-builder-page .badge {
        border-radius: 999px !important;
    }

    .report-builder-page table thead,
    .report-builder-page table thead th,
    .report-builder-page .table-dark th {
        background: rgba(255, 193, 143, 0.13) !important;
        border-bottom: 1px solid rgba(238, 143, 69, 0.42) !important;
        color: #7c2d12 !important;
    }

    .report-builder-page table tbody td {
        border-bottom: 1px solid #eef2f7 !important;
    }
</style>
<div class="body d-flex py-lg-3 py-md-2">
    <div class="container-xxl report-builder-page">
        <livewire:dynamic-report-builder />
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Add any additional JavaScript if needed
</script>
@endsection
