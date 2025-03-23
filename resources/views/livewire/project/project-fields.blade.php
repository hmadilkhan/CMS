<div>
    @php
        $showEditFields =
            ($ghost == 'ghost' && $departmentId == 7) || ($ghost != 'ghost' && $departmentId == $projectDepartmentId);
    @endphp
    @if ($showEditFields)
        @livewire('project.project-fields.edit-fields', ['project' => $project, 'departmentId' => $departmentId, 'ghost' => $ghost])
    @else
        @livewire('project.project-fields.view-fields', ['project' => $project, 'departmentId' => $departmentId])
    @endif
    {{-- @if ($departmentId == $projectDepartmentId)
        @livewire('project.project-fields.edit-fields', ['project' => $project, 'departmentId' => $departmentId, 'ghost' => $ghost])
    @else
        @livewire('project.project-fields.view-fields', ['project' => $project, 'departmentId' => $departmentId])
    @endif --}}
</div>
