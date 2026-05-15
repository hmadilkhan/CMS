@extends('layouts.master')
@section('title', 'Projects')
@section('content')
    <style>
        .search-box-wrapper {
            position: relative;
            width: min(100%, 420px);
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
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(255, 255, 255, 0.94);
            transition: all 0.3s ease;
            box-shadow: 0 14px 28px -22px rgba(52, 36, 22, 0.7);
        }

        .search-input:focus {
            border-color: rgba(255, 255, 255, 0.86);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.2);
            outline: none;
        }

        .search-input::placeholder {
            color: #adb5bd;
            font-size: 0.9rem;
        }

        .premium-lock-card {
            position: relative;
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .premium-lock-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #ffd700, #ffed4e, #ffd700);
            border-radius: 16px;
            z-index: -1;
            animation: borderGlow 3s ease-in-out infinite;
        }

        @keyframes borderGlow {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        .lock-icon-wrapper {
            position: absolute;
            top: 50%;
            right: 24px;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(255, 215, 0, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: translateY(-50%) scale(1); }
            50% { transform: translateY(-50%) scale(1.1); }
        }

        .lock-icon-wrapper i {
            color: #000;
            font-size: 24px;
        }

        .premium-badge {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
        }

        .project-filter-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 18px;
        }

        .premium-lock-card h3 {
            color: #fff;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .premium-lock-card .nav-tabs {
            border: none;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 4px;
        }

        .premium-lock-card .nav-link {
            color: rgba(255, 255, 255, 0.7);
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .premium-lock-card .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        .premium-lock-card .nav-link.active {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            font-weight: 600;
        }

        .department-tabs-card {
            background: transparent !important;
            box-shadow: none !important;
            padding: 0;
            overflow: visible;
        }

        .department-tabs-card::before {
            display: none;
        }

        .department-tabs-card .nav-tabs {
            /* background: var(--solen-cream) !important; */
            border: 0;
            gap: 0.5rem;
            padding: 0.65rem;
        }

        .department-tabs-card .nav-link {
            background: var(--solen-cream-strong);
            color: var(--solen-warm-text) !important;
            border: 0 !important;
            border-radius: 999px;
            font-weight: 700;
        }

        .department-tabs-card .nav-link:hover {
            background: var(--solen-primary-soft);
            color: var(--solen-warm-hover) !important;
        }

        .department-tabs-card .nav-link.active {
            background: var(--solen-gradient) !important;
            color: #ffffff !important;
        }

        @media (max-width: 767px) {
            .search-box-wrapper {
                width: 100%;
            }

            .project-filter-row {
                justify-content: stretch;
            }
        }
    </style>
    <div class="container-xxxl">
        <div class="row align-items-center">
            <div class="border-0 mb-4">
                <div class="project-filter-row">
                    <div class="search-box-wrapper">
                        <i class="icofont-search search-icon"></i>
                        <input type="text" class="form-control search-input" id="search"
                            placeholder="Search projects" />
                    </div>
                </div>
                <div class="premium-lock-card department-tabs-card">
                    <div class="d-flex project-tab flex-wrap justify-content-center">
                        @if (count($departments) > 1)
                            <ul class="nav nav-tabs rounded prtab-set" role="tablist" style="cursor: pointer;">
                                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab"
                                        onclick="projectList('all')" role="tab">All</a></li>
                                @foreach ($departments as $department)
                                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab"
                                            onclick="projectList('{{ $department->id }}')"
                                            role="tab">{{ $department->name }}</a></li>
                                @endforeach
                            </ul>
                        @endif
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
