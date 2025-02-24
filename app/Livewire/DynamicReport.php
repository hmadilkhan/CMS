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

    #[Computed()]
    public function updatedSelectedTable($tableName)
    {
        $column = [
            "name" => $this->getTableColumnName($tableName),
            "value" => $tableName." as ".$tableName,
            "col" => $tableName,
        ];
        array_push($this->selectedColumns, $column);
    }

    #[On("selectedFields")]
    public function selectedFields($value,$text)
    {
        $column = [
            "name" => $this->getTableColumnName($value),
            "value" => $value." as ".$value,
            "col" => $value,
            "text" => $text,
        ];
        array_push($this->selectedColumns, $column);
    }

    #[On("saveFilter")]
    public function saveFilter($text, $column, $operator, $value)
    {
        $filter = [
            "text" => $text,
            "column" => $column,
            "operator" => $operator,
            "value" => $value,
        ];
       
        array_push($this->selectedFilters, $filter);
    }

    #[On('submitData')]
    public function submitData()
    {
        // DB::enableQueryLog();
        $columns = collect($this->selectedColumns)->pluck('value');
        // dd(...$columns);
        $query = Project::query();
        $query->with("task", "customer", "department", "logs", "logs.call", "subdepartment", "assignedPerson", "assignedPerson.employee", "departmentnotes", "departmentnotes.user", "salesPartnerUser")
            ->join('customers', 'projects.customer_id', '=', 'customers.id')
            ->join('departments', 'projects.department_id', '=', 'departments.id')
            ->join('sub_departments', 'projects.sub_department_id', '=', 'sub_departments.id')
            ->join('sales_partners', 'sales_partners.id', '=', 'customers.sales_partner_id')
            ->join('module_types', 'module_types.id', '=', 'customers.module_type_id')
            ->join('inverter_types', 'inverter_types.id', '=', 'customers.inverter_type_id');

            // dump($this->selectedFilters);
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

    public function deleteColumn($index)
    {
        unset($this->selectedColumns[$index]); 
        $this->selectedColumns = array_values($this->selectedColumns); // Reindex the array
    }
    public function deleteFilter($index)
    {
        unset($this->selectedFilters[$index]); 
        $this->selectedFilters = array_values($this->selectedFilters); // Reindex the array
    }


    public function render()
    {
        return view('livewire.dynamic-report');
    }
}
