<?php

namespace App\Http\Controllers;

use App\Services\ProjectService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }
    public function dashboard(Request $request) : View 
    {
        return view('dashboard',[
            "projects" => $this->projectService->projectQuery($request),
        ]);
    }
}
