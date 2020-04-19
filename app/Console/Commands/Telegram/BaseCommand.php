<?php

namespace App\Console\Commands\Telegram;

use App\Models\User;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Exceptions\TelegramSDKException;

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

    /**
     * @param string $type
     * @param bool $exactmatch
     * @return bool
     */
    protected function isChatType($type = 'private', $exactmatch = false) {
        return ($exactmatch)
            ? $this->getUpdate()->getChat()->getType() === $type
            : strpos($this->getUpdate()->getChat()->getType(), $type) !== false
        ;
    }

    /**
     * @return bool
     */
    protected function isGroupAdmin() {
        if (self::isChatType("group")) {
            try {
                $chatmember = $this->getTelegram()->getChatMember([
                    'chat_id' => $this->update->getChat()->getId(),
                    'user_id' => $this->update->getMessage()->getFrom()->getId()
                ]);

                if (in_array($chatmember->get('status'), ['creator', 'administrator'])) {
                    return true;
                }

            } catch (TelegramSDKException $e) {}
        }

        return false;
    }

    /**
     * Parse arguments
     *
     * @param $arguments
     * @return false|string[]
     */
    protected function parseArgs($arguments) {
        return array_filter(explode(' ', $arguments), function ($val) {
            return ($val) ? true : false;
        });
    }
}
