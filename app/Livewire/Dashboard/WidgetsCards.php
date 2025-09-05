<?php

namespace App\Livewire\Dashboard;

use App\Models\Project;
use Carbon\Carbon;
use Livewire\Component;

class WidgetsCards extends Component
{
    public function render()
    {
        $startJanDate = Carbon::createFromDate(now()->year, 1, 1)->startOfDay();
        $startDate = Carbon::today()->subMonths(12);
        $endDate = Carbon::today();

        // Rolling 12 Month Revenue
        $totalContractAmount = Project::whereBetween('created_at', [$startJanDate, $endDate])
            ->whereHas('customer.finances') // optional safety
            ->with('customer.finances')
            ->get()
            ->sum(function ($project) {
                return $project->customer->finances->contract_amount ?? 0;
            });
        // YTD Revenue
        $totalYtdrevenue = Project::whereBetween('created_at', [$startJanDate, $endDate])
            ->whereHas('customer.finances') // optional safety
            ->with('customer.finances')
            ->get()
            ->sum(function ($project) {
                return $project->customer->finances->contract_amount ?? 0;
            });
        // YTD Total Commission
        $totalCommission = Project::whereBetween('created_at', [$startJanDate, $endDate])
            ->whereHas('customer.finances') // optional safety
            ->with('customer.finances')
            ->get()
            ->sum(function ($project) {
                return $project->customer->finances->commission ?? 0;
            });

        return view('livewire.dashboard.widgets-cards', compact('totalContractAmount', 'totalYtdrevenue', 'totalCommission'));
    }
}
