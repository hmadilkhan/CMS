<?php

namespace App\Livewire\DynamicReport;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DynamicReportForm extends Component
{
    public $tables = [];
    public $columns = [];
    public $selectedTable;
    public $selectedColumns = [];
    public $filters = [];

    protected $listeners = ['generateReport' => 'generateReport'];

    public function mount()
    {
        // Load all table names at mount
        $this->tables = DB::select('SHOW TABLES');
    }

    #[Computed()]
    public function updatedSelectedTable($tableName)
    {
        // Load columns based on selected table
        dd($tableName);
        $this->columns = DB::select("SHOW COLUMNS FROM {$tableName}");
    }

    public function generateReport()
    {
        // Emit the selected table, columns, and filters to the ReportResults component
        $this->emit('loadReport', [
            'table' => $this->selectedTable,
            'columns' => $this->selectedColumns,
            'filters' => $this->filters,
        ]);
    }

    public function render()
    {
        return view('livewire.dynamic-report.dynamic-report-form');
    }
}
