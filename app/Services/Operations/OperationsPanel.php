<?php

namespace App\Services\Operations;

use Illuminate\Http\Request;

/**
 * An Operations screen the console draws itself, rather than loading its page
 * in a frame.
 *
 * A panel is the one place that says what a screen shows: its own page and the
 * console both ask the same object for the view and the data, so the two can
 * never drift apart. The screen keeps its own route, its own controller and its
 * own permission - a panel only moves where it is drawn.
 */
interface OperationsPanel
{
    /** The partial that draws the screen's body, with no page chrome around it. */
    public function view(): string;

    /**
     * What that partial needs. It is read from the request the same way on both
     * sides, so `?id=` selects a record for editing in the console exactly as
     * the screen's own `/{id?}` does.
     *
     * @return array<string, mixed>
     */
    public function data(Request $request): array;
}
