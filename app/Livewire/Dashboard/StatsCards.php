<?php

namespace App\Livewire\Dashboard;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class StatsCards extends Component
{
    public $startDate;
    public $endDate;

    public function mount($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function render()
    {
        $startJanDate = Carbon::createFromDate(now()->year, 1, 1)->startOfDay();
        $startDate = Carbon::today()->subMonths(12);
        $endDate = Carbon::today();

        $avgPermitFee = DB::table('projects')
            ->selectRaw('AVG(actual_permit_fee) as avg_permit_fee')
            ->whereNotNull('actual_permit_fee')
            ->first();

        $avgMaterialFee = DB::table('projects')
            ->selectRaw('AVG(actual_material_cost) as avg_material_cost')
            ->whereNotNull('actual_material_cost')
            ->first();
        $avgLaborFee = DB::table('projects')
            ->selectRaw('AVG(actual_labor_cost) as avg_labor_cost')
            ->whereNotNull('actual_labor_cost')
            ->first();

        $avgContractAmount = Project::whereBetween('projects.created_at', [$startJanDate, $endDate])
            ->whereHas('customer.finances')
            ->join('customer_finances', 'projects.customer_id', '=', 'customer_finances.customer_id')
            ->avg('customer_finances.contract_amount');

        return view('livewire.dashboard.stats-cards', compact('avgPermitFee', 'avgMaterialFee', 'avgLaborFee','avgContractAmount'));
    }
}
