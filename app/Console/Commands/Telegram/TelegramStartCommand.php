<?php

namespace App\Console\Commands\Telegram;

class TelegramStartCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Command to get you started';

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

        try {
            $this->replyWithMessage(['text' => 'Buenas! me llamo Mak3rsManagementBot y te doy la bienvenida!!']);

            if ($this->update->getChat()->getType()=="private") {
                if (!parent::CheckAuth($this->update)) {
                    if ($this->update->getChat()->getUsername()) {
                        $this->replyWithMessage(['text' => 'Para empezar a interactuar debes de iniciar sesión primero']);
                        $this->replyWithMessage(['text' => 'Utiliza /login [password]']);
                    } else {
                        $this->replyWithMessage(['text' => 'Para empezar a interactuar debes de crearte un alias en Ajustes->Perfil->Username, y después ejecutar el siguiente comando']);
                        $this->replyWithMessage(['text' => 'Utiliza /SetAlias [email] [password]']);
                    }
                }
            }
        } catch (Exception $e) {
            ob_start(); var_dump($e); $text= ob_get_clean();
            $this->replyWithMessage(['text' => $text]);
        }



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
