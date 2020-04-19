<?php

namespace App\Console\Commands\TelegramCommands;

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
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function handle()
    {
        $url = env('APP_URL') . '/' . env('TELEGRAM_BOT_TOKEN') . '/webhook/telegram';
        $this->info("URL => $url");
        $response = Telegram::setWebhook(['url' => $url]);
        $this->info(($response == true ? 'Se ha actualizado el WebHook correctamente' : 'No se ha podido actualizar el WebHook correctamente'));
    }
}
