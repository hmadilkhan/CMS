<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NewProjectsCard extends Component
{
    public $startDate;
    public $endDate;

    public function mount($startDate = null, $endDate = null)
    {
        // Set default dates to current month if not provided
        $this->startDate = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    protected $listeners = ['datesUpdated' => 'updateDates'];

    public function updateDates($dates)
    {
        $this->startDate = Carbon::parse($dates['startDate'])->format('Y-m-d');
        $this->endDate = Carbon::parse($dates['endDate'])->format('Y-m-d');
    }

    public function render()
    {
        $newProjects = Project::selectRaw('sales_partners.name as sales_partner_name, COUNT(projects.id) as project_count, COALESCE(SUM(customer_finances.contract_amount), 0) as total_contract_amount')
            ->join('users', 'projects.sales_partner_user_id', '=', 'users.id')
            ->join('sales_partners', 'users.sales_partner_id', '=', 'sales_partners.id')
            ->join('customers', 'projects.customer_id', '=', 'customers.id')
            ->join('customer_finances', 'customers.id', '=', 'customer_finances.customer_id')
            ->whereDate('projects.created_at', '>=', $this->startDate)
            ->whereDate('projects.created_at', '<=', $this->endDate)
            ->whereNotNull('projects.sales_partner_user_id')
            ->groupBy('sales_partners.id', 'sales_partners.name')
            ->orderBy('project_count', 'desc')
            ->get();
			
			$totalProjects = $newProjects->sum('project_count');
            $totalContractAmount = $newProjects->sum('total_contract_amount');
	


        return view('livewire.dashboard.new-projects-card', [
            'newProjects' => $newProjects,
            'totalProjects' => $totalProjects,
            'totalContractAmount' => $totalContractAmount,
        ]);
    }
} 