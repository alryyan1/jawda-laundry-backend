<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupRadhiData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'radhi:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed product types, service actions, and offerings from Radhi database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Radhi data setup...');

        $this->info('Running ProductTypeRadhiSeeder...');
        $this->call('db:seed', ['--class' => 'ProductTypeRadhiSeeder']);

        $this->info('Running ServiceRadhiSeeder...');
        $this->call('db:seed', ['--class' => 'ServiceRadhiSeeder']);

        $this->info('Radhi data setup completed successfully.');
    }
}
