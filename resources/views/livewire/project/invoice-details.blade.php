<div class="card mt-1">
    <div class="card-body">
        <div class="card-header py-3 px-0 d-sm-flex align-items-center border-bottom">
            <h3 class="fw-bold flex-fill mb-0 mt-sm-0 px-3" data-bs-toggle="collapse"
                data-bs-target="#invoiceDetailsTable" aria-expanded="false" aria-controls="invoiceDetailsTable">
                Invoice Details
            </h3>
        </div>

        @if (session()->has('invoice_message'))
            <div class="alert alert-success mt-3">{{ session('invoice_message') }}</div>
        @endif

        <form wire:submit.prevent="save" class="mt-3 mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Invoice Type</label>
                    <select class="form-select form-control" wire:model.defer="invoiceType">
                        <option value="">Select Invoice Type</option>
                        <option value="labor">Labor</option>
                        <option value="material">Material</option>
                    </select>
                    @error('invoiceType')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" wire:model.defer="invoiceDate">
                    @error('invoiceDate')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" min="0" class="form-control" wire:model.defer="amount"
                        placeholder="0.00">
                    @error('amount')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">File Upload</label>
                    <input type="file" class="form-control" wire:model="file"
                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.csv">
                    @error('file')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div wire:loading wire:target="file" class="text-primary small mt-1">
                        Processing file...
                    </div>
                </div>

                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled"
                        wire:target="file,save">
                        Save
                    </button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table id="invoiceDetailsTable" class="table table-bordered table-striped text-white">
                <thead>
                    <tr>
                        <th class="text-white">No.</th>
                        <th class="text-white">Invoice Type</th>
                        <th class="text-white">Date</th>
                        <th class="text-white">Amount</th>
                        <th class="text-white">File</th>
                        <th class="text-white">Uploaded By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoiceDetails as $key => $invoice)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $invoice->invoice_type_label }}</td>
                            <td>{{ optional($invoice->invoice_date)->format('d M Y') }}</td>
                            <td>$ {{ number_format($invoice->amount, 2) }}</td>
                            <td>
                                <a href="{{ asset('storage/' . $invoice->file_path) }}" target="_blank"
                                    class="text-white text-decoration-underline">
                                    {{ $invoice->original_file_name }}
                                </a>
                            </td>
                            <td>{{ optional($invoice->uploader)->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No invoice details found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
