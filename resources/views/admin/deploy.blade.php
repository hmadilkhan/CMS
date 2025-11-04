<form method="post" action="{{ route('admin.deploy') }}">
    @csrf
    <button name="action" value="deploy" class="btn btn-success">🚀 Deploy Update</button>
    <button name="action" value="rollback" class="btn btn-danger">⏪ Rollback</button>
</form>

@if(isset($output))
<pre class="bg-dark text-white p-3 mt-3 rounded">{{ $output }}</pre>
@endif
<h3 class="mt-5">🧾 Deployment History</h3>
<table class="table table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>Action</th>
            <th>Run By</th>
            <th>Status</th>
            <th>Time</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($deployLogs as $log)
            <tr>
                <td>{{ $log->id }}</td>
                <td>{{ ucfirst($log->action) }}</td>
                <td>{{ $log->run_by }}</td>
                <td>
                    <span class="badge {{ $log->status === 'success' ? 'bg-success' : 'bg-danger' }}">
                        {{ $log->status }}
                    </span>
                </td>
                <td>{{ $log->created_at }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
