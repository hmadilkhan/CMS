<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\ProjectInvoiceDetail;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class InvoiceDetails extends Component
{
    use WithFileUploads;

    public $projectId;
    public $invoiceType = '';
    public $invoiceDate = '';
    public $amount = '';
    public $file;

    protected function rules(): array
    {
        return [
            'invoiceType' => 'required|in:labor,material',
            'invoiceDate' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'file' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv',
        ];
    }

    public function mount($projectId): void
    {
        $this->projectId = $projectId;
    }

    public function save(): void
    {
        $this->validate();

        $project = Project::findOrFail($this->projectId);
        $originalName = $this->file->getClientOriginalName();
        $safeOriginalName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $storedName = time() . '_' . $safeOriginalName;
        $filePath = $this->file->storeAs('invoices', $storedName, 'public');

        $invoice = ProjectInvoiceDetail::create([
            'project_id' => $project->id,
            'invoice_type' => $this->invoiceType,
            'invoice_date' => $this->invoiceDate,
            'amount' => $this->amount,
            'file_name' => basename($filePath),
            'original_file_name' => $originalName,
            'file_path' => $filePath,
            'uploaded_by' => optional(auth()->user())->id,
        ]);

        activity('project')
            ->performedOn($project)
            ->causedBy(auth()->user())
            ->setEvent('updated')
            ->withProperties(['project_invoice_detail_id' => $invoice->id])
            ->log(auth()->user()->name . ' added an invoice detail.');

        $this->resetForm();
        session()->flash('invoice_message', 'Invoice detail saved successfully.');
    }

    public function resetForm(): void
    {
        $this->reset(['invoiceType', 'invoiceDate', 'amount', 'file']);
        $this->resetValidation();
    }

    public function render()
    {
        $invoiceDetails = ProjectInvoiceDetail::where('project_id', $this->projectId)
            ->latest('invoice_date')
            ->latest('id')
            ->get();

        return view('livewire.project.invoice-details', compact('invoiceDetails'));
    }
}
