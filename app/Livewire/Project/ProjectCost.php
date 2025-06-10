<?php

namespace App\Livewire\Project;

use App\Models\InverterType;
use App\Models\InverterTypeRate;
use App\Models\LaborCost;
use App\Models\ModuleType;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProjectCost extends Component
{
    public $project;

    public $projectId;
    public $internalContractAmount = 0;
    # PRE FIELDS
    public $preEstimateMaterialCost = 0;
    public $preEstimateLaborCost = 0;
    public $preEstimatePermitCost = 0;
    # POST FIELDS
    public $postEstimateMaterialCost = 0;
    public $postEstimateLaborCost = 0;
    public $postEstimatePermitCost = 0;

    # PRE PROFIT FIELDS
    public $preEstimateProfit = 0;
    public $preEstimateProfitPercentage = 0;

    # POST PROFIT FIELDS
    public $postEstimateProfit = 0;
    public $postEstimateProfitPercentage = 0;

    public string $message = '';
    public string $messageType = 'success'; // or 'danger'
    public bool $showMessage = false;

    public $moduleType ;
    public $inverterType ;
    public $panelQty;
    public $laborCost;


    public function mount()
    {
        $this->projectId = $this->project->id;
        $this->internalContractAmount = $this->project->customer->finances->redline_costs + $this->project->customer->finances->adders;
        # PRE FIELDS
        $this->preEstimateMaterialCost = $this->project->pre_estimated_material_costs;
        $this->preEstimateLaborCost = $this->project->pre_estimated_labor_costs;
        $this->preEstimatePermitCost = $this->project->pre_estimated_permit_costs;
        # POST FIELDS
        $this->postEstimateMaterialCost = $this->project->actual_material_cost;
        $this->postEstimateLaborCost = $this->project->actual_labor_cost;
        $this->postEstimatePermitCost = $this->project->actual_permit_fee;

        $this->moduleType = ModuleType::where("id",$this->project->customer->module_type_id)->where("inverter_type_id",$this->project->customer->inverter_type_id)->first();
        $this->inverterType = InverterTypeRate::where("inverter_type_id",$this->project->customer->inverter_type_id)->first();
        $this->panelQty = $this->project->customer->panel_qty;
        $this->laborCost = LaborCost::first();
    }

    public function calculateProfit()
    {
        $this->preEstimateProfit = $this->internalContractAmount - ($this->preEstimateMaterialCost + $this->preEstimateLaborCost + $this->preEstimatePermitCost);
        $this->preEstimateProfitPercentage = ($this->preEstimateProfit /  $this->internalContractAmount * 100);

        $this->postEstimateProfit = $this->internalContractAmount - ($this->postEstimateMaterialCost + $this->postEstimateLaborCost + $this->postEstimatePermitCost);
        $this->postEstimateProfitPercentage = ($this->postEstimateProfit /  $this->internalContractAmount * 100);
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName, [
            'preEstimateMaterialCost' => 'nullable|numeric',
            'preEstimateLaborCost' => 'nullable|numeric',
            'preEstimatePermitCost' => 'nullable|numeric',
            'postEstimateMaterialCost' => 'nullable|numeric',
            'postEstimateLaborCost' => 'nullable|numeric',
            'postEstimatePermitCost' => 'nullable|numeric',
        ]);

        $this->saveToDatabase($propertyName);
    }

    protected $propertyMap = [
        'preEstimateMaterialCost' => 'pre_estimated_material_costs',
        'preEstimateLaborCost' => 'pre_estimated_labor_costs',
        'preEstimatePermitCost' => 'pre_estimated_permit_costs',
        'postEstimateMaterialCost' => 'actual_material_cost',
        'postEstimateLaborCost' => 'actual_labor_cost',
        'postEstimatePermitCost' => 'actual_permit_fee',
    ];

    public function saveToDatabase()
    {
        try {
            $project  = Project::find($this->projectId);

            $project->update([
                // 'pre_estimated_material_costs' => $this->preEstimateMaterialCost,
                // 'pre_estimated_labor_costs' => $this->preEstimateLaborCost,
                'pre_estimated_permit_costs' => $this->preEstimatePermitCost,
                'actual_material_cost' => $this->postEstimateMaterialCost,
                'actual_labor_cost' => $this->postEstimateLaborCost,
                'actual_permit_fee' => $this->postEstimatePermitCost,
            ]);
            $this->message = 'Your data has been saved!';
            $this->messageType = 'success';
            $this->showMessage = true;
            $this->calculateProfit();
        } catch (\Throwable $th) {

            $this->message = 'Record not updated!';
            $this->messageType = 'error';
        }
    }

    public function calcaulatePreEstimatedFields()
    {
        $this->preEstimateMaterialCost = $this->inverterType->internal_base_cost ?? 0 + ($this->panelQty ?? 0 * $this->moduleType->internal_module_cost ?? 0);
        $this->preEstimateLaborCost = $this->inverterType->internal_labor_cost ?? 0 + ($this->panelQty ?? 0 * $this->laborCost->cost ?? 0);
    }

    public function render()
    {
        $this->calculateProfit();
        $this->calcaulatePreEstimatedFields();
        return view('livewire.project.project-cost');
    }
}
