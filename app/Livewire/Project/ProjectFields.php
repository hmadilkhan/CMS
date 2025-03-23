<?php

namespace App\Livewire\Project;

use Livewire\Component;

class ProjectFields extends Component
{
    public $project;
    public $taskId;
    public $departmentId;
    public $projectDepartmentId;
    public $ghost;

    public function render()
    {
        return view('livewire.project.project-fields');
    }
}
