<?php

namespace App\Console\Commands;

use App\Models\NewTicket;
use App\Models\WpContactLead;
use App\Models\WpContactLeadDetail;
use Illuminate\Console\Command;

class GetWordpressLeadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:leads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $totalForm = [];
        $form = [];
        $leads = WpContactLead::where("is_uploaded", 0)->get();
        if (!empty($leads)) {
            foreach ($leads as $key => $lead) {
                $details = WpContactLeadDetail::where("lead_id", $lead->id)->get();
                foreach ($details as $key => $value) {
                    $form[$value->name] = $value->value;
                }
                $item = [
                    "name" => $form['your-name'],
                    "email" => $form['your-email'],
                    "address" => $form['your-address'],
                    "phone" => $form['your-phone'],
                    "message" => $form['your-message'],
                ];
                array_push($totalForm, $item);
                WpContactLead::where("id", $lead->id)->update(["is_uploaded" => 1]);
            }
        }
        if (!empty($totalForm)) {
            foreach ($totalForm as $key => $form) {
                NewTicket::create([
                    "name" => $form['name'],
                    "email" => $form['email'],
                    "address" => $form['address'],
                    "phone" => $form['phone'],
                    "message" => $form['message'],
                ]);
            }
        }
        $this->info("Total " . count($totalForm) . " has been transferred to CMS");
    }
}
