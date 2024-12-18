<?php

namespace App\Livewire;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Str;

class DynamicReport extends Component
{
    #[Title("Dynamic Report Builder")]
    protected $listeners = ['submitData'];

    public $tables = [];
    public $columns = [];
    public $selectedTable;
    public $selectedColumns = [];
    public $selectedFilters = [];
    public $filters = [];
    public $data = [];

    public function mount()
    {
        // Load all table names at mount
        // $this->tables = DB::select('SHOW TABLES');
    }

    #[Computed()]
    public function updatedSelectedTable($tableName)
    {
        $column = [
            "name" => $this->getTableColumnName($tableName),
            "value" => $tableName." as ".$tableName,
            "col" => $tableName,
        ];
        array_push($this->selectedColumns, $column);
        // dump($this->selectedColumns);
    }

    #[On("saveFilter")]
    public function saveFilter($column, $operator, $value)
    {

        $filter = [
            "column" => $column,
            "operator" => $operator,
            "value" => $value,
        ];

        array_push($this->selectedFilters, $filter);
    }

    #[On('submitData')]
    public function submitData()
    {
        DB::enableQueryLog();
        $columns = collect($this->selectedColumns)->pluck('value');
        // dd(...$columns);
        $query = Project::query();
        $query->with("task", "customer", "department", "logs", "logs.call", "subdepartment", "assignedPerson", "assignedPerson.employee", "departmentnotes", "departmentnotes.user", "salesPartnerUser")
            ->join('customers', 'projects.customer_id', '=', 'customers.id')
            ->join('departments', 'projects.department_id', '=', 'departments.id')
            ->join('sub_departments', 'projects.sub_department_id', '=', 'sub_departments.id');
        if(count($this->selectedFilters)){
            foreach ($this->selectedFilters as $key => $filter) {
                $query->where($filter['column'],$filter['operator'],$filter['value']);
            }
        }
        $query->select(...$columns);
        $this->data = $query->get();
        // dump( $this->data);
        // $this->data = Project::with("task", "customer", "department", "logs", "logs.call", "subdepartment", "assignedPerson", "assignedPerson.employee", "departmentnotes", "departmentnotes.user", "salesPartnerUser")
        //     ->join('customers', 'projects.customer_id', '=', 'customers.id')
        //     ->join('departments', 'projects.department_id', '=', 'departments.id')
        //     ->join('sub_departments', 'projects.sub_department_id', '=', 'sub_departments.id')
        //     ->select(...$columns)
        //     ->get();
        // dd($this->selectedColumns);
        // dd(DB::getQueryLog());
    }

    public function getTableColumnName($string)
    {
        // Step 1: Extract the part after the dot
        $afterDot = Str::after($string, '.');

        // Step 2: Replace underscores with spaces
        $withSpaces = str_replace('_', ' ', $afterDot);

        // Step 3: Capitalize each word
        $formattedString = Str::title($withSpaces);

        // Outputs: "Project Name"
        return $formattedString;
    }


    public function render()
    {
        return view('livewire.dynamic-report');
    }
}
