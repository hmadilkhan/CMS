<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectDateUpdatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendProjectDateUpdatedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    /**
     * @param  array<int, array{field: string, label: string, old: ?string, new: ?string}>  $changes
     */
    public function __construct(
        public User $user,
        public Project $project,
        public array $changes,
        public string $updatedBy
    ) {
    }

    public function handle(): void
    {
        if (empty($this->user->email) || empty($this->changes)) {
            return;
        }

        $this->user->notify(
            new ProjectDateUpdatedNotification($this->project, $this->changes, $this->updatedBy)
        );
    }
}
