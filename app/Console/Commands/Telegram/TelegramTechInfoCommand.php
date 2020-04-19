<?php

namespace App\Console\Commands\Telegram;

use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramTechInfoCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'techinfo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando para administradores';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle($arguments)
    {
        // This will send a message using `sendMessage` method behind the scenes to
        // the user/chat id who triggered this command.
        // `replyWith<Message|Photo|Audio|Video|Voice|Document|Sticker|Location|ChatAction>()` all the available methods are dynamically
        // handled when you replace `send<Method>` with `replyWith` and use the same parameters - except chat_id does NOT need to be included in the array.

        if (parent::isChatType("group")) {
            if (parent::isGroupAdmin()) {
                try {
                    ob_start(); var_dump($this->update); $text = ob_get_clean();
                    $this->getTelegram()->sendMessage([
                        'chat_id' => $this->update->getMessage()->getFrom()->getId(),
                        'text' => $text
                    ]);

                    $me = $this->getTelegram()->getMe();
                    $this->replyWithMessage(['text' => 'Te he enviado por privado la información del grupo'."\n@".$me->getUsername()]);

                } catch (TelegramSDKException $e) {
                    $this->replyWithMessage(['text' => 'Lo siento, no he conseguido darte lo que pedías..']);
                }

            } else {
                $this->replyWithMessage(['text' => '¡Acceso denegado! Debes ser administrador ¬¬']);
            }

        } else {
            $this->replyWithMessage(['text' => 'El uso de este comando está restringido a grupos']);
        }
    }
}
