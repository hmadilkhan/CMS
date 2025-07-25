@extends('layouts.master')

@section('title', 'Dynamic Report Builder')

@section('content')
<div class="body d-flex py-lg-3 py-md-2">
    <div class="container-xxl">
        <livewire:dynamic-report-builder />
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Add any additional JavaScript if needed
</script>
@endsection
