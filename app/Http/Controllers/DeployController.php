<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeployLog;
use Illuminate\Support\Facades\Auth;

class DeployController extends Controller
{
    public function deployAction(Request $request)
    {
        $user = Auth::user();
        $action = $request->input('action');
        $output = '';
        $status = 'success';

        try {
            if ($action === 'deploy') {
                $output = shell_exec('php artisan deploy:run 2>&1');
            } elseif ($action === 'rollback') {
                $output = shell_exec('php artisan deploy:rollback 2>&1');
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

        return view('admin.deploy', compact('output'));
    }
}
