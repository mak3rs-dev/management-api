<?php

namespace App\Console\Commands\Telegram;

use App\Models\User;
use Telegram\Bot\Commands\Command;

abstract class BaseCommand extends Command {

    protected function CheckAuth() {
        $this->replyWithMessage(['text' => 'MSG']);
        if ($username = $this->update->getChat()->getUsername()) {
            if ($userDb = User::where('alias', $username)->first()) {
                if ($telData = json_decode($userDb->telegram_data)) {
                    if (in_array('chatid', ((array)$telData))) {
                        if ($telData->chatid == $this->update->getChat()->getId()) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

}
