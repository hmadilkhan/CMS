<?php

namespace App\Services;

use App\Services\Operations\OperationsPanel;
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

    /**
     * The panel that draws a section natively, or null for a section the
     * console still opens in a frame. A section names its panel in the config;
     * nothing else in the console has to know which of the two it is.
     */
    public function panelFor(?array $section): ?OperationsPanel
    {
        if (empty($section['panel'])) {
            return null;
        }

        return app($section['panel']);
    }

    /**
     * Where a save made on an Operations screen comes back to: the console
     * section it was made in, or the screen's own page. `$params` are the
     * screen's own (a tab to land on, a record to edit); they ride along
     * either way.
     *
     * The key arrives in the form, so it is only honoured when it names a
     * section this viewer actually has - the field can never be turned into a
     * redirect somewhere else.
     */
    public function returnUrl($user, $sectionKey, string $fallbackRoute, array $params = []): string
    {
        if (is_string($sectionKey) && $sectionKey !== '') {
            $section = $this->section($user, $sectionKey);

            if ($section && $section['key'] === $sectionKey) {
                return route('operations.console', ['section' => $sectionKey] + $params);
            }
        }

        return route($fallbackRoute, $params);
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
