<?php

namespace App\Console\Commands\TelegramCommands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
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

        if ($this->data != null && $this->data->getChat() != null && strpos($this->data->getChat()->getType(), 'group') !== false) {
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
        }
    }
}