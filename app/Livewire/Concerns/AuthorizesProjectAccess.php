<?php

namespace App\Livewire\Concerns;

use App\Services\ProjectService;

/**
 * Re-checks the project a component is pointed at, on every request.
 *
 * $projectId is a public Livewire property, which means the browser owns it: a
 * user served the project page for one project can set it to another and the
 * component will happily render that project's notes, files or invoices. The
 * controller gate on /projects/{id} does not see any of that — the update goes
 * to livewire/update, not to the project route.
 *
 * boot() runs on the first render and on every subsequent round trip, so the id
 * is checked again each time it comes back from the client.
 */
trait AuthorizesProjectAccess
{
    public function bootAuthorizesProjectAccess(): void
    {
        $projectId = (int) ($this->projectId ?? 0);

        if ($projectId === 0) {
            return;
        }

        abort_unless(
            auth()->check() && app(ProjectService::class)->canAccessProject(auth()->user(), $projectId),
            403
        );
    }
}
