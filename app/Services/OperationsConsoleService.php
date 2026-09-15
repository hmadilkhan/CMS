<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

/**
 * The Operations console's section list, read from config/operations_console.php.
 *
 * One place answers "what is in the console, in what order, and may this user
 * open it" - the page, the deep link and the tests all ask here, so they cannot
 * disagree about what exists.
 */
class OperationsConsoleService
{
    /**
     * The console as the viewer may see it: groups with their sections, each
     * carrying the URL it opens. A section whose route is missing (renamed,
     * removed) or whose permission the viewer lacks is dropped rather than
     * rendered as a dead link.
     *
     * @return array<int, array{name: string, sections: array<int, array>}>
     */
    public function groupsFor($user): array
    {
        $groups = [];

        foreach ((array) config('operations_console.groups', []) as $group) {
            $sections = [];

            foreach ($group['sections'] ?? [] as $section) {
                if (! $this->isVisible($section, $user)) {
                    continue;
                }

                $sections[] = $section + ['url' => route($section['route'])];
            }

            if ($sections !== []) {
                $groups[] = ['name' => $group['name'], 'sections' => $sections];
            }
        }

        return $groups;
    }

    /** Every visible section, flat - the order the console lists them in. */
    public function sectionsFor($user): array
    {
        return collect($this->groupsFor($user))->flatMap(fn ($group) => $group['sections'])->all();
    }

    /**
     * The section a request asked for by key, or the first one the viewer may
     * open. An unknown key falls back rather than opening an empty console.
     */
    public function section($user, ?string $key): ?array
    {
        $sections = $this->sectionsFor($user);

        foreach ($sections as $section) {
            if ($section['key'] === $key) {
                return $section;
            }
        }

        return $sections[0] ?? null;
    }

    private function isVisible(array $section, $user): bool
    {
        if (! Route::has($section['route'])) {
            return false;
        }

        $permission = $section['permission'] ?? config('operations_console.permission');

        return ! $permission || ($user && $user->can($permission));
    }
}
