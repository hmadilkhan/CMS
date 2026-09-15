@extends("layouts.master")
@section('title', 'Tools')
@section('content')
{{-- The screen keeps its own page; its body lives in the panel, which the
     Operations console draws too - see docs/operations-console.md. --}}
@include('tools.panel')
@endsection
