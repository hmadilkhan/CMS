<!doctype html>
<html class="no-js" lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf_token" content="{{ csrf_token() }}" />
    <title> @yield('title') - Solen Energy Construction</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('website/images/favicon_big.png') }}">
    <link rel="shortcut icon" href="{{ asset('website/images/favicon_big.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('website/images/favicon_big.png') }}"> <!-- Favicon-->
    <!-- plugin css file  -->
    <link rel="stylesheet" href="{{ asset('assets/plugin/datatables/responsive.dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugin/datatables/dataTables.bootstrap5.min.css') }}">
    <!-- project css file  -->
    <link rel="stylesheet" href="{{ asset('assets/css/my-task.style.min.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />

    <style>
        #mytask-layout.theme-indigo {
            --primary-color: var(--solen-primary);
            --secondary-color: var(--solen-gold);
            --primary-gradient: var(--solen-gradient);
            --body-color: var(--solen-background);
            --card-color: var(--solen-card);
            --border-color: var(--solen-border);
            --color-100: #ffffff;
            --color-200: #ffffff;
            --color-300: #ffffff;
            --color-400: #cdbda8;
            --color-500: #a49686;
            --color-600: #7c6f60;
            --color-700: #5c4632;
            --color-800: #3f2e20;
            --color-900: #342416;
        }

        body {
            background: var(--solen-background);
            color: var(--solen-foreground);
        }

        #mytask-layout {
            background: #ffffff !important;
            min-height: 100vh;
        }

        .sidebar {
            background: #000000 !important;
            box-shadow: 0 24px 60px -28px rgba(0, 0, 0, 0.68);
        }

        .sidebar.sidebar-mini .menu-list .sub-menu,
        .sidebar.sidebar-mini .menu-list .m-link:hover span {
            background: #000000 !important;
        }

        .sidebar .brand-icon .logo-icon {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 50%;
            box-shadow: 0 0 42px rgba(255, 246, 229, 0.46);
        }

        .sidebar .menu-list .m-link,
        .sidebar .menu-list .ms-link,
        .sidebar .form-switch label,
        .sidebar .sidebar-title {
            color: rgba(255, 255, 255, 0.88) !important;
        }

        .sidebar .brand-icon .logo-text {
            color: #ffffff !important;
        }

        .sidebar .menu-list .m-link:hover,
        .sidebar .menu-list .m-link.active,
        .sidebar .menu-list .ms-link:hover,
        .sidebar .menu-list .ms-link.active {
            background: transparent !important;
            color: #f19828 !important;
        }

        .sidebar .menu-list .sub-menu::before,
        .sidebar .menu-list li[aria-expanded="true"] .sub-menu:before {
            background-color: rgba(241, 152, 40, 0.7);
        }

        .main,
        .main .body,
        .body,
        .bg-light {
            background: #ffffff !important;
        }

        .header .navbar,
        .card,
        .dropdown-menu,
        .modal-content {
            background-color: var(--solen-card) !important;
            border-color: var(--solen-border) !important;
            box-shadow: 0 18px 48px -34px rgba(52, 36, 22, 0.45);
        }

        .card .card-header,
        .modal-header,
        .premium-header,
        .premium-modal-header,
        .bg-dark,
        .bg-gradient-primary {
            background: var(--solen-gradient) !important;
            border-color: var(--solen-primary-border) !important;
            color: #ffffff !important;
        }

        .table,
        .form-control,
        .form-select,
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            border-color: var(--solen-border) !important;
            color: var(--solen-foreground);
        }

        .table-light,
        .table-hover > tbody > tr:hover > * {
            background-color: var(--solen-secondary) !important;
            color: var(--solen-foreground) !important;
        }

        .btn-primary,
        .btn-dark,
        .btn-success,
        .page-item.active .page-link {
            background: var(--solen-gradient) !important;
            border-color: transparent !important;
            color: #ffffff !important;
            box-shadow: 0 12px 30px -18px rgba(151, 76, 18, 0.55);
        }

        a,
        .page-link,
        .text-primary {
            color: var(--solen-primary-dark) !important;
        }

        .btn-primary a,
        .btn-dark a,
        .btn-success a,
        .sidebar a,
        .card-header a,
        .premium-header a,
        .premium-modal-header a {
            color: inherit !important;
        }
    </style>

    @include('layouts.partials.solen-theme')

    @livewireStyles
</head>

<body>
    <div id="mytask-layout" class="theme-indigo">
        <!-- sidebar -->
        @include('layouts.sidebar')

        <!-- main body area -->
        <div class="main px-lg-4 px-md-4">

            <!-- Body: Header -->
            @include('layouts.header')

            <!-- Body: Body -->
            <div class="body d-flex py-3">
                @yield('content')
            </div>
            <style id="crm-white-background-override">
                :root,
                #mytask-layout.theme-indigo {
                    --body-color: #ffffff;
                    --bs-body-bg: #ffffff;
                    --bs-light: #ffffff;
                    --bs-light-rgb: 255, 255, 255;
                    --color-100: #ffffff;
                    --color-200: #ffffff;
                    --color-300: #ffffff;
                    --solen-background: #ffffff;
                    --solen-cream: #ffffff;
                    --solen-cream-strong: #ffffff;
                    --solen-secondary: #ffffff;
                }

                html,
                body,
                #mytask-layout,
                #mytask-layout.theme-indigo,
                #mytask-layout .main,
                #mytask-layout .body,
                #mytask-layout .tab-content,
                #mytask-layout .tab-pane,
                #mytask-layout .container,
                #mytask-layout .container-fluid,
                #mytask-layout .row.bg-light,
                #mytask-layout .bg-light,
                #mytask-layout .table-light,
                #mytask-layout .list-group-item,
                #mytask-layout .dropdown-menu,
                #mytask-layout .modal-body {
                    background-color: #ffffff !important;
                    background-image: none !important;
                }
            </style>
        </div>
    </div>

    <script src="{{ asset('assets/bundles/libscripts.bundle.js') }}"></script>
    <script src="{{ asset('assets/bundles/apexcharts.bundle.js') }}"></script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script><!-- jQuery base library needed -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
    <script src="{{ asset('assets/bundles/dataTables.bundle.js') }}"></script>
    <!-- Jquery Page Js -->
    <script src="{{ asset('page/template.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('.select2').select2();
        });
        $('.datatable')
            .addClass('nowrap')
            .dataTable({
                responsive: true,
                ordering: false,
                columnDefs: [{
                    targets: [-1, -3],
                    className: 'dt-body-right'
                }]
            });
        $(".sidebar").hover(function() {
            $(".sidebar").removeClass("sidebar-mini")
        }, function() {
            $(".sidebar").addClass("sidebar-mini")
        })
        $(".sidebar").addClass("sidebar-mini")
    </script>
    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Livewire !== 'undefined') {
                console.log('Livewire loaded successfully');
            } else {
                console.error('Livewire failed to load');
            }
        });
    </script>
    @yield('scripts')
</body>

</html>
