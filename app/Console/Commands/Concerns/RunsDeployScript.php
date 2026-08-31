<?php

namespace App\Console\Commands\Concerns;

trait RunsDeployScript
{
    /**
     * Run one of the deployment shell scripts and report how it went.
     *
     * The script's own output is written to the command output so that
     * Artisan::output() carries it back to whoever triggered the run — the
     * admin deploy page shows it and stores it on the deploy log. Without
     * that, a script that warned or failed looked exactly like a clean run.
     */
    protected function runDeployScript(string $script, string $startMessage): int
    {
        $scriptPath = base_path($script);

        if (! file_exists($scriptPath)) {
            $this->error("❌ Script not found: {$scriptPath}");

            return 1;
        }

        $this->info($startMessage);

        $command = 'bash '.escapeshellarg($scriptPath).' 2>&1';

        if (function_exists('exec')) {
            $lines = [];
            $exitCode = 1;
            exec($command, $lines, $exitCode);
            $output = implode(PHP_EOL, $lines);
        } else {
            // Some shared hosts disable exec() but leave shell_exec() in place.
            // The script still runs, its exit status is just not observable.
            $output = (string) shell_exec($command);
            $exitCode = 0;
        }

        $this->line($output);

        if ($exitCode !== 0) {
            $this->error("❌ {$script} exited with status {$exitCode}.");

            return $exitCode;
        }

        return 0;
    }
}
