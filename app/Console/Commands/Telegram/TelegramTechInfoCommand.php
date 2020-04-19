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
    protected $description = 'Admin use only';

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
                    ob_start(); var_dump($this->update); $text= ob_get_clean();
                    $this->getTelegram()->sendMessage([
                        'chat_id' => $this->update->getMessage()->getFrom()->getId(),
                        'text' => $text
                    ]);
                    $me = $this->getTelegram()::getMe();
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

        // ob_start(); var_dump($e); $text= ob_get_clean();
        // $this->replyWithMessage(['text' => $text]);

        /// This will update the chat status to typing...
        //$this->replyWithChatAction(['action' => Actions::TYPING]);

        /*// This will prepare a list of available commands and send the user.
        // First, Get an array of all registered commands
        // They'll be in 'command-name' => 'Command Handler Class' format.
        $commands = $this->getTelegram()->getCommands();

        // Build the list
        $response = '';
        foreach ($commands as $name => $command) {
            $response .= sprintf('/%s - %s' . PHP_EOL, $name, $command->getDescription());
        }

        // Reply with the commands list
        $this->replyWithMessage(['text' => $response]);

        // Trigger another command dynamically from within this command
        // When you want to chain multiple commands within one or process the request further.
        // The method supports second parameter arguments which you can optionally pass, By default
        // it'll pass the same arguments that are received for this command originally.
        $this->triggerCommand('subscribe');*/
    }
}
