<div>
    @if ($departmentId == $projectDepartmentId)
        @livewire('project.project-fields.edit-fields',['project' => $project, 'departmentId' => $departmentId])
    @else
        {{-- <livewire:project.project-fields.view-fields :project = "$project", :departmentId = "$departmentId" /> --}}
        @livewire('project.project-fields.view-fields',['project' => $project, 'departmentId' => $departmentId])
    @endif
</div>
