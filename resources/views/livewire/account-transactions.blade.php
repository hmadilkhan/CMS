<div class="card mt-4">
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
                        <select class="form-select select2" aria-label="Default select Payee"
                            wire:model.defer="payee">
                            <option value="">Select Payee</option>
                            <option value="sales_partner">Sales Partner</option>
                            <option value="sub_contractor">Sub-Contractor</option>
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
                        <input type="number" step="0.01" class="form-control" wire:model.defer="amount">
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
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Milestone</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Details</th>
                        @can('Account Transactions Edit')
                            <th>Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $i => $transaction)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $transaction->milestone }}</td>
                            <td>${{ number_format($transaction->amount, 2) }}</td>
                            <td>{{ $transaction->transaction_date }}</td>
                            <td>{{ $transaction->transaction_details }}</td>
                            @can('Account Transactions Edit')
                                <td>
                                    <button class="btn btn-sm btn-info"
                                        wire:click="edit({{ $transaction->id }})">Edit</button>
                                    <button class="btn btn-sm btn-danger"
                                        wire:click="confirmDelete({{ $transaction->id }})">Delete</button>
                                    @if ($confirmingDeleteId === $transaction->id)
                                        <div class="mt-2">
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
                            <td colspan="6" class="text-center">No transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
