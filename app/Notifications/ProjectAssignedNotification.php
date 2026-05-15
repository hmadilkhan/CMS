<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Project $project,
        public Task $task,
        public string $assignedBy
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $projectName = str_replace('-', ' ', $this->project->project_name);

        return [
            'project_id' => $this->project->id,
            'project_name' => $projectName,
            'task_id' => $this->task->id,
            'assigned_by' => $this->assignedBy,
            'mentioned_by' => $this->assignedBy,
            'note' => 'You have been assigned a new project: ' . $projectName,
            'message' => 'You have been assigned a new project: ' . $projectName,
            'url' => route('projects.show', $this->project->id),
        ];
    }
}
