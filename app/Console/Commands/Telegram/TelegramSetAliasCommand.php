<?php

namespace App\Console\Commands\Telegram;

use Illuminate\Support\Facades\DB;
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
                    if (count($args) == 2) {
                        $email = $args[0];
                        $password = $args[1];

                        if ($user = DB::table('users')->where('email', $email)->first()) {
                            if (Hash::check($password, $user->password)) {

                                $res = DB::table('users')->where('id', $user->id)->update(['alias' => "@".$username]);
                                if ($res >= 0) {
                                    $this->replyWithMessage(['text' => "Alias establecido correctamente. Ahora puede iniciar sesión mediante \n\n/login [contraseña]"]);
                                } else {
                                    $this->replyWithMessage(['text' => "Se ha producido un error"]);
                                }

                                $this->getTelegram()->deleteMessage([
                                    'chat_id' => $this->update->getChat()->getId(),
                                    'message_id' => $this->update->getMessage()->getMessageId()
                                ]);

                            } else {
                                $this->replyWithMessage(['text' => "No hemos encontrado ninguna coincidencia con tu alias y contraseña, inténtalo de nuevo o actualiza tu alias mediante:\n/SetAlias [email] [contraseña]"]);
                            }

                        } else {
                            $this->replyWithMessage(['text' => "No hemos encontrado ninguna coincidencia con tu alias y contraseña, inténtalo de nuevo o actualiza tu alias mediante:\n/SetAlias [email] [contraseña]"]);
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
    }
}
