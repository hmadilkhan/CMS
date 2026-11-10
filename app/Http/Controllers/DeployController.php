<?php

namespace App\Http\Controllers;

use App\Models\DeployLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DeployController extends Controller
{
    public function deploy()
    {
        $output = '';
        $deployLogs = DeployLog::latest()->take(20)->get();

        return view('admin.deploy', compact('deployLogs', 'output') + [
            'pendingMigrations' => $this->pendingMigrations(),
        ]);
    }

    /**
     * The migrations this database has not run yet, so the page can say what
     * the button would do before anyone presses it.
     *
     * Returns null when the answer cannot be worked out - no database, no
     * migrations table yet. That is not the same as "nothing pending", and the
     * page says so rather than showing an empty list.
     */
    private function pendingMigrations(): ?array
    {
        try {
            $migrator = app('migrator');

            if (! $migrator->repositoryExists()) {
                return null;
            }

            $paths = array_merge($migrator->paths(), [database_path('migrations')]);
            $files = $migrator->getMigrationFiles($paths);
            $ran = $migrator->getRepository()->getRan();

            return array_values(array_diff(array_keys($files), $ran));
        } catch (\Throwable $e) {
            return null;
        }
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
            } elseif ($action === 'migrate') {
                // deploy.sh deliberately does not migrate: pulling code is safe
                // to repeat, changing the schema is not. So it is its own
                // button, pressed on purpose, and --force because production
                // has no one at a prompt to answer the confirmation.
                //
                // There is NO rollback counterpart. `migrate:rollback` drops
                // columns and the data in them, and a wrong press cannot be
                // undone from a web page - that one stays on the command line.
                $exitCode = Artisan::call('migrate', ['--force' => true]);
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

        return view('admin.deploy', compact('output', 'deployLogs') + [
            'pendingMigrations' => $this->pendingMigrations(),
        ]);
    }
}
