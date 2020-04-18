<?php

namespace App\Console\Commands\Telegram;

use Illuminate\Support\Facades\Hash;

class TelegramSetAliasCommand extends BaseCommand {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'setalias';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Establecer el alias de la cuenta (necesario para utilizar el bot)';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle($arguments) {
        // This will send a message using `sendMessage` method behind the scenes to
        // the user/chat id who triggered this command.
        // `replyWith<Message|Photo|Audio|Video|Voice|Document|Sticker|Location|ChatAction>()` all the available methods are dynamically
        // handled when you replace `send<Method>` with `replyWith` and use the same parameters - except chat_id does NOT need to be included in the array.

        if (parent::isChatType('private')) {
            $args = parent::parseArgs($arguments);
            if (!parent::CheckAuth()) {
                if ($username = $this->update->getChat()->getUsername()) {
                    if (count($args)==2) {
                        $email = $args[0];
                        $password = $args[1];

                        if ($user = DB::table('users')->where('email', '@'.$email)->first()) {
                            if (Hash::check($password, $user->password)) {

                                $res = DB::table('users')->where('id',$user->id)->update(['alias' => "@".$username]);
                                if ($res) {
                                    $this->replyWithMessage(['text' => "Alias establecido correctamente"]);
                                } else {
                                    $this->replyWithMessage(['text' => "Se ha producido un error"]);
                                }
                            } else {
                                $this->replyWithMessage(['text' => "No hemos encontrado ningúna coincidencia con tu alias y contraseña, inténtalo de nuevo o actualiza tu alias mediante:\n/SetAlias [email] [password]"]);
                            }
                        } else {
                            $this->replyWithMessage(['text' => "No hemos encontrado ningúna coincidencia con tu alias y contraseña, inténtalo de nuevo o actualiza tu alias mediante:\n/SetAlias [email] [password]"]);
                        }
                    } else {
                        $this->replyWithMessage(['text' => 'Utiliza /SetAlias [email] [password]']);
                    }
                } else {
                    $this->replyWithMessage(['text' => 'Para empezar a interactuar debes de crearte un alias en Ajustes->Perfil->Username, y después ejecutar el siguiente comando']);
                    $this->replyWithMessage(['text' => 'Utiliza /SetAlias [email] [password]']);
                }
            } else {
                $this->replyWithMessage(['text' => 'Ya ha iniciado sesión, lo cual significa que el alias ya era correcto.']);
            }
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
