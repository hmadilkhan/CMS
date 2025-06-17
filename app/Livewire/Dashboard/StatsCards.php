<?php

namespace App\Livewire\Dashboard;

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

        return view('livewire.dashboard.stats-cards', compact('avgPermitFee', 'avgMaterialFee', 'avgLaborFee'));
    }
}
