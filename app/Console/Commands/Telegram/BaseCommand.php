<?php

namespace App\Console\Commands\Telegram;

use App\Models\User;
use Telegram\Bot\Commands\Command;

class BaseCommand extends Command {

    protected function CheckAuth() {
        $req = $this->getUpdate();

        if (in_array('username', $req["message"]["chat"]) && $req["message"]["chat"]["username"]) {
            $username = $req["message"]["chat"]["type"]["username"];

            if ($userDb = User::where('alias', $username)->first()) {
                if ($telData = json_decode($userDb->telegram_data)) {
                    if (in_array('chatid', ((array)$telData))) {
                        if ($telData->chatid == $req["message"]["chat"]["type"]["id"]) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    public function handle($arguments) {
    }

}
