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
                        @if(count($departments) > 1)
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
                        @endif
                    </div>
                </div>
                <div class="row align-items-center justify-content-end py-3 px-0 flex-wrap">
                    <div class="col-auto">
                        <div class="search-box-wrapper">
                            <i class="icofont-search search-icon"></i>
                            <input type="text" class="form-control search-input" id="search" placeholder="Type to search / Enter to refresh" />
                        </div>
                    </div>
                </div>
                <style>
                    .search-box-wrapper {
                        position: relative;
                        width: 500px;
                    }
                    .search-icon {
                        position: absolute;
                        left: 18px;
                        top: 50%;
                        transform: translateY(-50%);
                        color: #6c757d;
                        font-size: 1.2rem;
                        z-index: 10;
                    }
                    .search-input {
                        padding: 12px 20px 12px 50px;
                        height: 50px;
                        border-radius: 8px;
                        border: 2px solid #e9ecef;
                        transition: all 0.3s ease;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                    }
                    .search-input:focus {
                        border-color: #2c3e50;
                        box-shadow: 0 4px 12px rgba(44,62,80,0.15);
                        outline: none;
                    }
                    .search-input::placeholder {
                        color: #adb5bd;
                        font-size: 0.9rem;
                    }
                </style>
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
