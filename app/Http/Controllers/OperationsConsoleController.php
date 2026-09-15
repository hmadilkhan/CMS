<?php

namespace App\Http\Controllers;

use App\Services\OperationsConsoleService;
use Illuminate\Http\Request;

/**
 * One window for the Operations screens - see config/operations_console.php.
 *
 * The console itself holds no operations logic: it lists the sections and opens
 * the one that was asked for. Every screen keeps its own page, its own routes
 * and its own permissions, and still works on its own URL.
 */
class OperationsConsoleController extends Controller
{
    public function __construct(private OperationsConsoleService $console) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $section = $this->console->section($user, $request->query('section'));
        $panel = $this->console->panelFor($section);

        return view('operations.console', [
            'groups' => $this->console->groupsFor($user),
            'section' => $section,
            'panelView' => $panel?->view(),
            'panelData' => $panel ? $panel->data($request) : [],
        ]);
    }
}
