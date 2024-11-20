<?php

namespace App\Livewire\Project\ProjectFields;

use Livewire\Component;

class ViewFields extends Component
{
    public $project;
    public $departmentId;

    public function render()
    {
        return view('livewire.project.project-fields.view-fields');
    }
}
