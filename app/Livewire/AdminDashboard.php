<?php

namespace App\Livewire;

use App\Models\ProjectFollowUp;
use Livewire\Attributes\Title;
use Livewire\Component;
use Carbon\Carbon;

class AdminDashboard extends Component
{
    #[Title("Dashboard")]

    public $startDate;
    public $endDate;

    public function mount()
    {
        // Set default dates to current month if not set
        $this->startDate = $this->startDate ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = $this->endDate ?? Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function updateDates()
    {
        // Validate dates
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate'
        ]);

        // Emit event to refresh all components
        $this->dispatch('datesUpdated', [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate
        ]);

        $this->dispatch('refreshPtoChart');
        $this->dispatch('refreshChart'); // triggers JS to redraw
    }

    
    public function render()
    {
        if (auth()->user()->id === 2) {
            $employeeId = 42;
        }else{
            $employeeId = auth()->user()->employee->id ?? null;
        }
        $followUps = collect();

        if (!empty(auth()->user()->employee)) {
            $followUps = ProjectFollowUp::with(['project.customer', 'employee'])
                ->where('employee_id', $employeeId)
                ->where('status', '!=', 'Resolved')
                ->orderBy('follow_up_date', 'asc')
                ->get();
        }

        return view('livewire.admin-dashboard', [
            'followUps' => $followUps,
        ]);
    }
}
