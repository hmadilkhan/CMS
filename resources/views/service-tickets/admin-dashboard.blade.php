@extends('layouts.master')
@section('title', 'Service Admin Dashboard')
@section('content')
@include('operations.partials.index-styles')
<div class="container-xxxl">
    <div class="operation-page-header">
        <div>
            <h1 class="operation-page-title"><i class="icofont-ticket me-2"></i>All Service Tickets</h1>
            <p class="operation-page-subtitle">Review service tickets across all projects.</p>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="operation-card">
                <div class="card-header">
                    <h3 class="card-title">Tickets</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover operation-table">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Subject</th>
                                    <th>Created By</th>
                                    <th>Assigned To</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $ticket)
                                    <tr>
                                        <td>
                                            <a href="{{ route('projects.show', $ticket->project_id) }}">
                                                {{ $ticket->project->project_name }}
                                            </a>
                                        </td>
                                        <td>{{ $ticket->subject }}</td>
                                        <td>{{ $ticket->creator->name ?? 'N/A' }}</td>
                                        <td>{{ $ticket->assignedUser->name ?? 'Unassigned' }}</td>
                                        <td>
                                            <span class="badge 
                                                @if($ticket->priority == 'High') bg-danger
                                                @elseif($ticket->priority == 'Medium') bg-warning
                                                @else bg-info
                                                @endif">
                                                {{ $ticket->priority }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $ticket->status == 'Resolved' ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $ticket->status }}
                                            </span>
                                        </td>
                                        <td>{{$ticket->notes}}</td>
                                        <td>{{ $ticket->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No tickets found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
