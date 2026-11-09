{{--
    The Payees screen's body - see operations/departments/panel.blade.php for
    what `$console` means here.
--}}
@php
    $console = $console ?? false;
    $screenUrl = fn ($id = null) => $console
        ? route('operations.console', array_filter(['section' => 'payees', 'id' => $id]))
        : route('payees.index', array_filter(['id' => $id]));
@endphp
@if(session('success'))
<div class="alert alert-primary" role="alert">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="alert alert-danger" role="alert">
    {{ session('error') }}
</div>
@endif
@include('operations.partials.index-styles')
<style>
    .payee-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid transparent;
    }
    .payee-badge.system { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
    .payee-badge.custom { background: rgba(255, 193, 143, 0.18); color: #9a3412; border-color: rgba(238, 143, 69, 0.28); }
    .payee-badge.on { background: #ecfdf3; color: #166534; border-color: #bbf7d0; }
    .payee-badge.off { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
    .payee-key { color: #64748b; font-size: 12px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    .payee-actions { display: inline-flex; gap: 6px; align-items: center; }
    .payee-actions form { display: inline; margin: 0; }
    .payee-actions button.action-link { padding: 0; }
    .payee-locked { color: #94a3b8; font-size: 12px; }
</style>
<div class="operation-page-header">
    <div>
        <h1 class="operation-page-title">Payees</h1>
        <p class="operation-page-subtitle">Who account transactions and finance milestones are paid to. Built-in payees are locked; the ones you add can be renamed, switched off, or deleted while nothing uses them.</p>
    </div>
    <div class="operation-summary">
        <span>Total Payees</span>
        <strong>{{ $payees->count() }}</strong>
    </div>
</div>
<div class="card operation-card">
    <div class="card-header">
        <h4 class="card-title">{{ $payee ? 'Rename Payee' : 'Add Payee' }}</h4>
    </div>
    <div class="card-body">
        <form class="operation-form" method="POST" action="{{ $payee ? route('payees.update', $payee->id) : route('payees.store') }}">
            @csrf
            @if ($payee)
            @method('PUT')
            @endif
            @if ($console)
            <input type="hidden" name="ops_section" value="payees" />
            @endif
            <div class="row g-3 align-items-start">
                <div class="col-xl-5 col-lg-6 col-md-8 col-12">
                    <label for="payee_label">Payee name</label>
                    <input type="text" required maxlength="100" autocomplete="off" class="form-control @error('label') is-invalid @enderror" id="payee_label" name="label" placeholder="e.g. Solar Fund LLC" value="{{ old('label', $payee->label ?? '') }}">
                    @error('label')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    @if ($payee)
                    <div class="form-text">Every transaction and milestone that uses this payee will show the new name.</div>
                    @endif
                </div>
                <div class="col-12">
                    <div class="operation-actions">
                        <button type="submit" class="btn btn-primary"><i class="icofont-save"></i> {{ $payee ? 'Save' : 'Add Payee' }}</button>
                        <a href="{{ $screenUrl() }}" class="btn btn-outline-secondary"><i class="icofont-ban"></i> Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card operation-card mt-3">
    <div class="card-header">
        <h4 class="card-title">Payees</h4>
    </div>
    <div class="card-body">
        <table class="table table-hover operation-table">
            <thead>
                <tr>
                    <th>Payee</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Transactions</th>
                    <th>Milestones</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payees as $row)
                <tr>
                    <td>
                        <strong>{{ $row->label }}</strong>
                        <div class="payee-key">{{ $row->key }}</div>
                    </td>
                    <td>
                        <span class="payee-badge {{ $row->is_system ? 'system' : 'custom' }}">{{ $row->is_system ? 'Built-in' : 'Added' }}</span>
                    </td>
                    <td>
                        <span class="payee-badge {{ $row->is_active ? 'on' : 'off' }}">{{ $row->is_active ? 'Active' : 'Switched off' }}</span>
                    </td>
                    <td class="cost-value">{{ $row->transactions_count }}</td>
                    <td class="cost-value">{{ $row->milestones_count }}</td>
                    <td class="text-center">
                        @if ($row->is_system)
                        <span class="payee-locked"><i class="icofont-lock"></i> Locked</span>
                        @else
                        <div class="payee-actions">
                            <a class="action-link" title="Rename" href="{{ $screenUrl($row->id) }}">
                                <i class="icofont-pencil text-warning"></i></a>
                            <form method="POST" action="{{ route('payees.toggle', $row->id) }}">
                                @csrf
                                @if ($console)
                                <input type="hidden" name="ops_section" value="payees" />
                                @endif
                                <button type="submit" class="action-link" title="{{ $row->is_active ? 'Switch off' : 'Switch on' }}">
                                    <i class="{{ $row->is_active ? 'icofont-eye-blocked text-secondary' : 'icofont-eye text-success' }}"></i></button>
                            </form>
                            @if ($row->transactions_count + $row->milestones_count === 0)
                            <form method="POST" action="{{ route('payees.destroy', $row->id) }}" onsubmit="return confirm('Delete this payee permanently?');">
                                @csrf
                                @method('DELETE')
                                @if ($console)
                                <input type="hidden" name="ops_section" value="payees" />
                                @endif
                                <button type="submit" class="action-link" title="Delete">
                                    <i class="icofont-trash text-danger"></i></button>
                            </form>
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($payees->isEmpty())
        <div class="empty-state">No payees yet.</div>
        @endif
        <p class="text-muted small mt-3 mb-0">A switched-off payee is no longer offered when adding a transaction or a milestone, but the rows that already use it keep showing its name. A payee can be deleted only while no transaction or milestone uses it.</p>
    </div>
</div>
