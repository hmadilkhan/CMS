<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProjectAcceptance;
use App\Models\Project;

class BackfillProjectAcceptanceFinancials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'acceptance:backfill-financials {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill financial data for existing project acceptance records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }
        
        // Find all project acceptances that don't have financial data saved
        $acceptances = ProjectAcceptance::whereNull('inverter_base_price')
            ->orWhereNull('module_qty_price')
            ->get();
        
        if ($acceptances->isEmpty()) {
            $this->info('No project acceptances need backfilling.');
            return 0;
        }
        
        $this->info("Found {$acceptances->count()} project acceptance(s) to backfill.");
        
        $bar = $this->output->createProgressBar($acceptances->count());
        $bar->start();
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        foreach ($acceptances as $acceptance) {
            try {
                // Load the project with all necessary relationships
                $project = Project::with([
                    'customer',
                    'customer.finances',
                    'customer.inverter',
                    'customer.adders',
                    'customer.adders.type'
                ])->find($acceptance->project_id);
                
                if (!$project || !$project->customer) {
                    $errors[] = "Acceptance ID {$acceptance->id}: Project or customer not found";
                    $errorCount++;
                    $bar->advance();
                    continue;
                }
                
                // Check if required relationships exist
                if (!$project->customer->finances) {
                    $errors[] = "Acceptance ID {$acceptance->id}: Customer finances not found";
                    $errorCount++;
                    $bar->advance();
                    continue;
                }
                
                if (!$project->customer->inverter) {
                    $errors[] = "Acceptance ID {$acceptance->id}: Customer inverter not found";
                    $errorCount++;
                    $bar->advance();
                    continue;
                }
                
                // Calculate financial values (same logic as in controller)
                $basePrice = $project->customer->finances->inverter_base_cost + ($project->overwrite_base_price ?? 0);
                $moduleQtyPrice = $project->customer->finances->module_type_cost + ($project->overwrite_panel_price ?? 0);
                $modulesAmount = $project->customer->panel_qty * $moduleQtyPrice;
                
                // Extract adder names
                $addersList = [];
                if ($project->customer->adders) {
                    foreach ($project->customer->adders as $adder) {
                        if ($adder->type) {
                            $addersList[] = $adder->type->name;
                        }
                    }
                }
                
                // Prepare update data
                $updateData = [
                    'inverter_base_price' => $basePrice,
                    'dealer_fee_amount' => $project->customer->finances->dealer_fee_amount,
                    'module_qty_price' => $moduleQtyPrice,
                    'modules_amount' => $modulesAmount,
                    'panel_qty' => $project->customer->panel_qty,
                    'contract_amount' => $project->customer->finances->contract_amount,
                    'redline_costs' => $project->customer->finances->redline_costs,
                    'adders_amount' => $project->customer->finances->adders,
                    'commission_amount' => $project->customer->finances->commission,
                    'inverter_name' => $project->customer->inverter->name,
                    'adders_list' => $addersList,
                ];
                
                if (!$dryRun) {
                    $acceptance->update($updateData);
                }
                
                $successCount++;
                
            } catch (\Exception $e) {
                $errors[] = "Acceptance ID {$acceptance->id}: {$e->getMessage()}";
                $errorCount++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Display results
        $this->info("Backfill completed!");
        $this->info("Successfully processed: {$successCount}");
        
        if ($errorCount > 0) {
            $this->warn("Errors encountered: {$errorCount}");
            $this->newLine();
            $this->error("Error details:");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }
        
        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN mode was enabled - no changes were made to the database.');
            $this->info('Run without --dry-run to apply changes.');
        }
        
        return 0;
    }
}
