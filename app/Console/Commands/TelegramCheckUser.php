<?php

namespace App\Console\Commands;

use App\Models\Community;
use App\Models\User;
use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramCheckUser extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mak3rs:telegramCheckUser {--msgId=} {--groupId=} {--userId=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Telegram User';

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
        $msgId = $this->option('msgId');
        $groupId = $this->option('groupId');
        $userId = $this->option('userId');

        $community = Community::whereRaw("telegram_data REGEXP '.*\"chatid\":.*$groupId.*'")->first();

        if ($community) {
            $telData = json_decode($community->telegram_data);

            if (isset($telData->autokicknonuser) && $telData->autokicknonuser) {
                if (!isset($telData->pendingCheckUsers)) $telData->pendingCheckUsers = [];
                if (!in_array($userId, $telData->pendingCheckUsers)) {
                    $user = User::whereRaw("telegram_data REGEXP '.*\"chatid\":.*$userId.*'")->first();
                    $inCommunity = ($user)?$user->InCommunities->where('community_id', $community->id)->first():null;

                    if (!$inCommunity) {
                        $telData->pendingCheckUsers[] = $userId;
                        $community->telegram_data = json_encode($telData);

                        if ($community->save()) {
                            $me = Telegram::getMe();

                            Telegram::sendMessage(array_merge([
                                'chat_id' => $groupId,
                                'text' => 'Para permanecer en el grupo, debe de hablarme por privado e iniciar sesión para confirmar su cuenta'."\n@".$me->getUsername()
                            ], ($msgId?['reply_to_message_id'=>$msgId]:[])));
                        }
                    }
                }
            }
        }


    }
}
