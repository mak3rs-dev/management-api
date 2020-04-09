<?php

namespace App\Console\Commands;

use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Console\Command;

class BotWebHook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
        $updates = Telegram::getUpdates();
        $this->info(var_dump($updates));
    }
}
