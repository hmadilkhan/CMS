<div class="card mt-4">
    <style>
        .account-transactions-table th,
        .account-transactions-table td {
            vertical-align: middle;
        }

        .account-transactions-table .transaction-actions {
            width: 150px;
            min-width: 150px;
        }

        .transaction-action-group {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            white-space: nowrap;
        }

        .transaction-action-btn {
            min-width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 8px !important;
            line-height: 1;
        }

        .transaction-delete-confirm {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.55rem;
            white-space: nowrap;
            font-size: 0.78rem;
        }
    </style>
    <div class="card-header">
        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0" data-bs-toggle="collapse" aria-expanded="false"
            aria-controls="adderTable">Account Transactions</h3>
    </div>
    <div class="card-body">
        @if (session()->has('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif
        @can('Account Transactions Edit')
            <form wire:submit.prevent="{{ $isEditMode ? 'update' : 'save' }}" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Payee</label>
                        <select class="form-select form-control" aria-label="Default select Payee"
                            wire:model.defer="payee">
                            <option value="">Select Payee</option>
                            <option value="sales_partner">Sales Partner</option>
                            <option value="sub_contractor">Sub-Contractor</option>
                            <option value="supplier">Supplier</option>
                            <option value="customer">Customer</option>
                            <option value="others">Others</option>
                        </select>
                        @error('payee')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Milestone</label>
                        <input type="text" class="form-control" wire:model.defer="milestone">
                        @error('milestone')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" class="form-control" wire:model.defer="amount"
                            placeholder="Positive or negative">
                        <small class="text-muted">Use positive or negative values for debit/credit.</small>
                        @error('amount')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Deduction Amount</label>
                        <input type="number" step="0.01" class="form-control" wire:model.defer="deduction_amount">
                        @error('deduction_amount')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Transaction Date</label>
                        <input type="date" class="form-control" wire:model.defer="transaction_date">
                        @error('transaction_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Details</label>
                        <input type="text" class="form-control" wire:model.defer="transaction_details">
                        @error('transaction_details')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            {{ $isEditMode ? 'Update' : 'Add' }}
                        </button>
                        @if ($isEditMode)
                            <button type="button" class="btn btn-secondary w-100 mt-1"
                                wire:click="resetForm">Cancel</button>
                        @endif
                    </div>
                </div>
            </form>
        @endcan
        <div class="table-responsive">
            @php
                $totalAmount = $transactions->sum('amount');
                $totalDeductionAmount = $transactions->sum('deduction_amount');
                $totalRemittedAmount = $transactions->sum(fn($transaction) => $transaction->remitted_amount);
            @endphp
            <table class="table table-bordered table-hover align-middle account-transactions-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Payee</th>
                        <th>Milestone</th>
                        <th>Amount</th>
                        <th>Deduction Amount</th>
                        <th>Remitted Amount</th>
                        <th>Date</th>
                        <th>Details</th>
                        @can('Account Transactions Edit')
                            <th class="transaction-actions text-center">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $i => $transaction)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $transaction->payee_label  }}</td>
                            <td>{{ $transaction->milestone }}</td>
                            <td>${{ number_format($transaction->amount, 2) }}</td>
                            <td>${{ number_format($transaction->deduction_amount, 2) }}</td>
                            <td>${{ number_format($transaction->remitted_amount , 2) }}</td>
                            <td>{{ date('d M Y',strtotime($transaction->transaction_date)) }}</td>
                            <td>{{ $transaction->transaction_details }}</td>
                            @can('Account Transactions Edit')
                                <td class="transaction-actions text-center">
                                    <div class="transaction-action-group">
                                        <button class="btn btn-sm btn-info transaction-action-btn" title="Edit"
                                            wire:click="edit({{ $transaction->id }})">
                                            <i class="icofont-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger transaction-action-btn" title="Delete"
                                            wire:click="confirmDelete({{ $transaction->id }})">
                                            <i class="icofont-trash"></i>
                                        </button>
                                    </div>
                                    @if ($confirmingDeleteId === $transaction->id)
                                        <div class="transaction-delete-confirm">
                                            <span>Are you sure?</span>
                                            <button class="btn btn-sm btn-danger"
                                                wire:click="delete({{ $transaction->id }})">Yes</button>
                                            <button class="btn btn-sm btn-secondary"
                                                wire:click="$set('confirmingDeleteId', null)">No</button>
                                        </div>
                                    @endif
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="@can('Account Transactions Edit') 9 @else 8 @endcan" class="text-center">No transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3" class="text-end">Totals</th>
                        <th>${{ number_format($totalAmount, 2) }}</th>
                        <th>${{ number_format($totalDeductionAmount, 2) }}</th>
                        <th>${{ number_format($totalRemittedAmount, 2) }}</th>
                        <th colspan="@can('Account Transactions Edit') 3 @else 2 @endcan"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
