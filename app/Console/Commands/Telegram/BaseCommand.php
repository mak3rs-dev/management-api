<?php

namespace App\Console\Commands\Telegram;

use App\Models\User;
use Telegram\Bot\Commands\Command;

abstract class BaseCommand extends Command {

    /**
     * Check user auth by alias and chat_id
     *
     * @return false|User
     */
    protected function CheckAuth() {
        if ($username = $this->update->getChat()->getUsername()) {
            if ($userDb = User::where('alias', '@'.$username)->first()) {
                if ($telData = json_decode($userDb->telegram_data)) {
                    if (isset($telData->chatid)) {
                        if ($telData->chatid == $this->update->getChat()->getId()) {
                            return $userDb;
                        }
                    }
                }
            }
        }

        return false;
    }

    protected function isChatType($type='private', $exactmatch=false) {
        return ($exactmatch)
            ? $this->getUpdate()->getChat()->getType() === $type
            : strpos($this->getUpdate()->getChat()->getType(), $type)!==false
        ;
    }

    protected function isGroupAdmin() {
        if (parent::isChatType("group")) {
            $chatmember = $this->getTelegram()->getChatMember([
                'chat_id' => $this->update->getChat()->getId(),
                'user_id' => $this->update->getMessage()->getFrom()->getId()
            ]);
            if (in_array($chatmember->getStatus(), ['creator', 'administrator'])) {
                return true;
            }
        }
        return false;
    }

    protected function parseArgs($arguments) {
        return array_filter(explode(' ', $arguments), function ($val) {
            return ($val) ? true:false;
        });
    }

}
