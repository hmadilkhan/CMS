<!doctype html>
<html class="no-js" lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf_token" content="{{ csrf_token() }}" />
    <title>Solen Energy Construction - CRM</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon"> <!-- Favicon-->
    <!-- plugin css file  -->
    <link rel="stylesheet" href="{{asset('assets/plugin/datatables/responsive.dataTables.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/plugin/datatables/dataTables.bootstrap5.min.css')}}">
    <!-- project css file  -->
    <link rel="stylesheet" href="{{asset('assets/css/my-task.style.min.css')}}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />
    
</head>

<body>
    <div id="mytask-layout" class="theme-indigo">
        <!-- sidebar -->
        @include("layouts.sidebar")

        <!-- main body area -->
        <div class="main px-lg-4 px-md-4">

            <!-- Body: Header -->
            @include("layouts.header")

            <!-- Body: Body -->
            <div class="body d-flex py-3">
                @yield("content")
            </div>
        </div>
    </div>




    <script src="assets/bundles/libscripts.bundle.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script><!-- jQuery base library needed -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script> 
    
    <script src="{{asset('assets/bundles/dataTables.bundle.js')}}"></script>

    <!-- Jquery Page Js -->
    <script src="{{asset('page/template.js')}}"></script>
    
    <script type="text/javascript">
        $(document).ready(function() {
            $('.select2').select2();
        });
        $('.datatable')
        .addClass( 'nowrap' )
        .dataTable( {
            responsive: true,
            columnDefs: [
                { targets: [-1, -3], className: 'dt-body-right' }
            ]
        });
    </script>
    @yield("scripts")
</body>

</html>