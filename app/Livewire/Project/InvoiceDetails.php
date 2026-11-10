<?php

namespace App\Livewire\Project;

use App\Livewire\Concerns\AuthorizesProjectAccess;
use App\Models\Project;
use App\Models\ProjectInvoiceDetail;
use App\Services\SubContractorPayableService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class InvoiceDetails extends Component
{
    use AuthorizesProjectAccess;

    use WithFileUploads;

    #[Locked]
    public $projectId;
    public $invoiceType = '';
    public $invoiceDate = '';
    public $amount = '';
    public $notes = '';
    public $file;

    protected function rules(): array
    {
        return [
            'invoiceType' => 'required|in:labor,material',
            'invoiceDate' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:5000',
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
            'notes' => $this->notes,
            'file_name' => basename($filePath),
            'original_file_name' => $originalName,
            'file_path' => $filePath,
            'uploaded_by' => optional(auth()->user())->id,
        ]);

        $this->syncProjectInvoiceCost($project, $this->invoiceType);
        $this->dispatch('project-invoice-costs-updated', projectId: $project->id);

        activity('project')
            ->performedOn($project)
            ->causedBy(auth()->user())
            ->setEvent('updated')
            ->withProperties(['project_invoice_detail_id' => $invoice->id])
            ->log(auth()->user()->name . ' added an invoice detail.');

        // The Labor invoice is only needed to reach PTO, so it can arrive after
        // the Inspection Approval Date that raises the Labor Payable. Raise it
        // now then; an invoice never gets a second one.
        if ($this->invoiceType === 'labor') {
            $project->refresh();

            if (! empty($project->inspection_approval_date)) {
                app(SubContractorPayableService::class)->createForInspectionApproval($project);
            }
        }

        $this->resetForm();
        session()->flash('invoice_message', 'Invoice detail saved successfully.');
    }

    public function deleteInvoice(int $invoiceId): void
    {
        $invoice = ProjectInvoiceDetail::where('project_id', $this->projectId)->findOrFail($invoiceId);
        $project = Project::findOrFail($this->projectId);
        $invoiceType = $invoice->invoice_type;

        if ($invoice->file_path) {
            Storage::disk('public')->delete($invoice->file_path);
        }

        $invoice->delete();
        $this->syncProjectInvoiceCost($project, $invoiceType);
        $this->dispatch('project-invoice-costs-updated', projectId: $project->id);

        activity('project')
            ->performedOn($project)
            ->causedBy(auth()->user())
            ->setEvent('updated')
            ->withProperties(['project_invoice_detail_id' => $invoice->id])
            ->log(auth()->user()->name . ' deleted an invoice detail.');

        session()->flash('invoice_message', 'Invoice detail deleted successfully.');
    }

    protected function syncProjectInvoiceCost(Project $project, string $invoiceType): void
    {
        ProjectInvoiceDetail::syncProjectCost($project, $invoiceType);
    }

    public function resetForm(): void
    {
        $this->reset(['invoiceType', 'invoiceDate', 'amount', 'notes', 'file']);
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
