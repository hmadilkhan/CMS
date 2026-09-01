<?php

namespace App\Livewire\Concerns;

use App\Services\ProjectService;

/**
 * Re-checks the project a component is pointed at, on every request.
 *
 * $projectId used to be an ordinary public Livewire property, which means the
 * browser owned it: a user served the project page for one project could set it
 * to another and the component would happily render that project's notes, files
 * or invoices. The controller gate on /projects/{id} does not see any of that -
 * the update goes to livewire/update, not to the project route. The property is
 * now #[Locked] (the client can no longer change it at all) and boot() re-runs
 * the CRM's own project-access rule on every round trip.
 *
 * One exception: the public customer tracking page (/track-your-project/{ref})
 * mounts these components for a visitor who is not logged in, passing
 * viewSource = 'website'. That page is reached with an encrypted project
 * reference and renders read-only, so it is allowed through without a user.
 * viewSource is #[Locked] too, so only a page that mounted the component
 * server-side in website mode can carry that value - a logged-in user cannot
 * flip their own component into it.
 */
trait AuthorizesProjectAccess
{
    public function bootAuthorizesProjectAccess(): void
    {
        $projectId = (int) ($this->projectId ?? 0);

        if ($projectId === 0 || $this->onPublicWebsiteView()) {
            return;
        }

        abort_unless(
            auth()->check() && app(ProjectService::class)->canAccessProject(auth()->user(), $projectId),
            403
        );
    }

    /**
     * Website mode is read-only - the tracking page hides every note/file
     * control - so refuse the writes as well, not just hide their buttons.
     */
    protected function abortIfPublicWebsiteView(): void
    {
        abort_if($this->onPublicWebsiteView(), 403);
    }

    /** True while this instance was mounted by the public tracking page. */
    protected function onPublicWebsiteView(): bool
    {
        return property_exists($this, 'viewSource') && $this->viewSource === 'website';
    }
}
