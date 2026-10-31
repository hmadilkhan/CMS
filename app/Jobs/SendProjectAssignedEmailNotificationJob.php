<?php

namespace App\Jobs;

use App\Jobs\Concerns\ThrottlesMail;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ProjectAssignedEmailNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendProjectAssignedEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ThrottlesMail;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public User $user,
        public Project $project,
        public Task $task,
        public string $assignedBy
    ) {
    }

    public function handle(): void
    {
        if (empty($this->user->email)) {
            return;
        }

        $this->user->notify(
            new ProjectAssignedEmailNotification($this->project, $this->task, $this->assignedBy)
        );
    }
}
