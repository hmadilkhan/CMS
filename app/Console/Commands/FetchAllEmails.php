<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailFetchService;

class FetchAllEmails extends Command
{
    protected $signature = 'emails:fetch-all';
    protected $description = 'Fetch all department emails for all customers and projects';

    public function handle()
    {
        $service = new EmailFetchService();
        $service->fetchAll();
        $this->info('All emails fetched successfully.');
    }
} 