<?php

namespace App\Console\Commands\TelegramCommands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User;

class TelegramRawUpdate extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mak3rs:telegramRawUpdate {--data=}';

    /**
     * The update event
     *
     * @var Update
     */
    private $data;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->data = new Update(json_decode($this->option('data'), true));

        if ($this->data == null || $this->data->getChat() == null) {
            return;
        }

        if (strpos($this->data->getChat()->getType(), 'group') !== false) {
            if ($members = $this->data->getMessage()->get('new_chat_members')) {
                foreach ($members as $member) {
                    $member = new User($member);
                    Artisan::call('mak3rs:telegramCheckUser', [
                        '--msgId' => null,
                        '--groupId' => $this->data->getMessage()->getChat()->getId(),
                        '--userId' => $member->getId()
                    ]);
                }

            } else {
                Artisan::call('mak3rs:telegramCheckUser', [
                    '--msgId' => $this->data->getMessage()->getMessageId(),
                    '--groupId' => $this->data->getMessage()->getChat()->getId(),
                    '--userId' => $this->data->getMessage()->getFrom()->getId()
                ]);
            }

        } else {
            if ($this->data->getMessage() == null || $this->data->getMessage()->getText() == null) {
                return;
            }

            $message = "";
            switch (Str::lower($this->data->getMessage()->getText())) {
                case "hola":
                    $commands = Telegram::getCommands();

                    // Build the list
                    $response = '';
                    foreach ($commands as $name => $command) {
                        $response .= sprintf('/%s - %s' . PHP_EOL, $name, $command->getDescription());
                    }

                    $message = "Hola!! Soy un bot, no puedo atender tus mensajes a menos que me digas una función en específico, a continuación te describo las funciones, recuerda que también debes de indicar la /:\n\n$response";
                    break;

                default:
                    $message = "No entiendo lo que me quieres decir 🥺";
            }

            Telegram::sendMessage([
                'chat_id' => $this->data->getMessage()->getChat()->getId(),
                'text' => $message
            ]);

        }
    }
}
