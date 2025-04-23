<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class NewProjectsCard extends Component
{
    public function render()
    {
        $newProjects = Project::selectRaw('sales_partners.name as sales_partner_name, COUNT(projects.id) as project_count')
            ->join('users', 'projects.sales_partner_user_id', '=', 'users.id')
            ->join('sales_partners', 'users.sales_partner_id', '=', 'sales_partners.id')
            ->whereMonth('projects.created_at', now()->month)
            ->whereYear('projects.created_at', now()->year)
            ->whereNotNull('projects.sales_partner_user_id')
            ->groupBy('sales_partners.id', 'sales_partners.name')
            ->orderBy('project_count', 'desc')
            ->get();

        $totalProjects = $newProjects->sum('project_count');

        return view('livewire.dashboard.new-projects-card', [
            'newProjects' => $newProjects,
            'totalProjects' => $totalProjects
        ]);
    }
} 