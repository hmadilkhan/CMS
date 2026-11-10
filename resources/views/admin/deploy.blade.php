<form method="post" action="{{ route('admin.deploy') }}">
    @csrf
    <button name="action" value="deploy" class="btn btn-success">🚀 Deploy Update</button>
    <button name="action" value="rollback" class="btn btn-danger">⏪ Rollback</button>

    {{-- Pulling code is safe to repeat; changing the schema is not. So the
         migrations are their own button, pressed on purpose, and the page says
         what it would run before anyone presses it. --}}
    @if (!isset($pendingMigrations))
        <button name="action" value="migrate" class="btn btn-warning"
            onclick="return confirm('Run all pending database migrations on this database?')">
            🗄️ Run Migrations
        </button>
    @elseif (count($pendingMigrations) === 0)
        <button class="btn btn-outline-secondary" disabled>🗄️ No Pending Migrations</button>
    @else
        <button name="action" value="migrate" class="btn btn-warning"
            onclick="return confirm('Run {{ count($pendingMigrations) }} pending migration(s) on this database? This changes the live schema and cannot be undone from here.')">
            🗄️ Run {{ count($pendingMigrations) }} Pending Migration{{ count($pendingMigrations) === 1 ? '' : 's' }}
        </button>
    @endif
</form>

@if (!isset($pendingMigrations))
    <div class="alert alert-secondary mt-3 mb-0">
        Could not read the migration status from the database, so the pending list is unknown.
        Running the migrations is still safe — it only applies what has not run yet.
    </div>
@elseif (count($pendingMigrations) > 0)
    <div class="alert alert-warning mt-3 mb-0">
        <strong>Waiting to run on this database:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($pendingMigrations as $migration)
                <li><code>{{ $migration }}</code></li>
            @endforeach
        </ul>
    </div>
@endif

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
