<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeployLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

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
                Artisan::call('deploy:run');
            } elseif ($action === 'rollback') {
                Artisan::call('deploy:rollback');
            } else {
                throw new \Exception('Invalid action');
            }
        } catch (\Throwable $e) {
            $output = $e->getMessage();
            $status = 'failed';
        }

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
