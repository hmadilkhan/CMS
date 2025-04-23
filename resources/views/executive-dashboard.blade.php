@extends("layouts.master")
@section("content")
<div class="container-xxl">
    <div class="row g-4">
        <div class="col-md-4">
            <livewire:new-projects-card />
        </div>
        <div class="col-md-4">
            <livewire:department-time-chart :key="1" />
        </div>
        <div class="col-md-4">
            <livewire:installation-chart :key="2" />
        </div>
    </div>

</div>
@endsection
