<?php

namespace App\Livewire;

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
        return view('livewire.admin-dashboard');
    }
}
