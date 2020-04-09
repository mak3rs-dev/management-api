<?php

namespace App\Console\Commands;

use App\Imports\CsvImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Storage;

class ImportCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mak3r:importCsv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command import csv init';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Starting Import...');

        try {
            Excel::import(new CsvImport(), Storage::path('importMak3rs.csv'), null, \Maatwebsite\Excel\Excel::CSV);

        } catch (\Exception $e) {
            $this->error('The import has failed --> '.$e->getMessage());
        }

        $this->info('The import is complete');
    }
}
