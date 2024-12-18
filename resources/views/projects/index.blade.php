@extends('layouts.master')
@section('title', 'Projects')
@section('content')
    <div class="container-xxxl">
        <div class="row align-items-center">
            <div class="border-0 mb-4">
                <div
                    class="card-header p-0 no-bg bg-transparent d-flex align-items-center px-0 justify-content-between border-bottom flex-wrap">
                    <h3 class="fw-bold py-3 mb-0">Projects</h3>
                    <div class="d-flex py-2 project-tab flex-wrap w-sm-100">
                        <ul class="nav nav-tabs tab-body-header rounded ms-3 prtab-set w-sm-100" role="tablist"
                            style="cursor: pointer;">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" onclick="projectList('all')"
                                    role="tab">All</a></li>
                            @foreach ($departments as $department)
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab"
                                        onclick="projectList('{{ $department->id }}')"
                                        role="tab">{{ $department->name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div> <!-- Row end  -->
        <div class="row align-items-center">
            <div class="col-lg-12 col-md-12 flex-column">
                <div class="tab-content mt-4" id="projectlist">

                </div>
            </div>
        </div>
        <!-- Create Employee-->
        @include('projects.create-model')
    </div>
    @include('projects.delete-modal')

    @include('projects.scripts')
@endsection
