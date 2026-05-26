<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf_token" content="{{ csrf_token() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AI Chat') - {{ config('app.name', 'CRM') }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('website/images/favicon_big.png') }}">
    <link rel="shortcut icon" href="{{ asset('website/images/favicon_big.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            margin: 0;
            background: #f8fafc;
        }
    </style>
</head>

<body class="min-h-screen overflow-hidden text-slate-900 antialiased">
    @yield('content')

    @yield('scripts')
</body>

</html>
