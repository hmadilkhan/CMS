<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeployLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DeployController extends Controller
{
    public function deploy()
    {
        $output = '';
        $deployLogs = DeployLog::latest()->take(20)->get();
        return view('admin.deploy',compact('deployLogs', 'output'));
    }

    public function deployAction(Request $request)
    {
        $user = Auth::user();
        $action = $request->input('action');
        $output = '';
        $status = 'success';
        // $WEBROOT="/home/u160855881/domains/solenenergyco.com/public_html/CRM/portal";
        
        try {
            if ($action === 'deploy') {
                $exitCode = Artisan::call('deploy:run');
            } elseif ($action === 'rollback') {
                $exitCode = Artisan::call('deploy:rollback');
            } else {
                throw new \Exception('Invalid action');
            }

            // The script's own log — warnings included — only reaches the page
            // and the deploy log through here. A non-zero exit is a real
            // failure even though no exception was thrown.
            $output = trim(Artisan::output());
            $status = $exitCode === 0 ? 'success' : 'failed';
        } catch (\Throwable $e) {
            $output = $e->getMessage();
            $status = 'failed';
        }

        // artisan runs with --ansi inside composer's post-autoload-dump, and the
        // page renders the log as escaped text — so the colour codes would show
        // up as literal "[90m." noise. Strip the terminal escapes.
        $output = preg_replace('/\e\[[0-9;?]*[a-zA-Z]/', '', $output);

        // deploy_logs.output is a TEXT column; keep a long script log from
        // overflowing it.
        $output = Str::limit($output, 60000, "\n… (output truncated)");

        DeployLog::create([
            'action' => $action,
            'run_by' => $user ? $user->name : 'system',
            'output' => $output,
            'status' => $status,
        ]);

        $deployLogs = DeployLog::latest()->take(20)->get();

        return view('admin.deploy', compact('output', 'deployLogs'));
    }
}
