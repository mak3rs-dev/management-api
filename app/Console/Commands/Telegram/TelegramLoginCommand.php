<?php

namespace App\Console\Commands\Telegram;

use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Hash;

class TelegramLoginCommand extends BaseCommand {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando para iniciar sesión';

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
                    if (count($args) == 1) {
                        $password = $args[0];

                        if ($user = DB::table('users')->where('alias', '@'.$username)->first()) {
                            if (Hash::check($password, $user->password)) {
                                $telData = json_decode($user->telegram_data);
                                if (!$telData) $telData = new \stdClass();
                                $telData->chatid = $this->update->getChat()->getId();

                                $res = DB::table('users')->where('id',$user->id)->update([
                                    'telegram_data' => json_encode($telData),
                                    'updated_at' => Carbon::now()
                                ]);
                                if ($res) {
                                    $this->replyWithMessage(['text' => "Has iniciado sesión correctamente"]);
                                } else {
                                    $this->replyWithMessage(['text' => "Se ha producido un error"]);
                                }

                                $this->getTelegram()->deleteMessage([
                                    'chat_id' => $this->update->getChat()->getId(),
                                    'message_id' => $this->update->getMessage()->getMessageId()
                                ]);

                            } else {
                                $this->replyWithMessage(['text' => "No hemos encontrado ninguna coincidencia con tu alias y contraseña, inténtalo de nuevo o actualiza tu alias mediante:\n/SetAlias [email] [password]"]);
                            }

                        } else {
                            $this->replyWithMessage(['text' => "No hemos encontrado ninguna coincidencia con tu alias y contraseña, inténtalo de nuevo o actualiza tu alias mediante:\n/SetAlias [email] [contraseña]"]);
                        }

                    } else {
                        $this->replyWithMessage(['text' => 'Utiliza /login [password]']);
                    }

                } else {
                    $this->replyWithMessage(['text' => 'Para empezar a interactuar debes de crearte un alias en Ajustes->Perfil->Username, y después ejecutar el siguiente comando']);
                    $this->replyWithMessage(['text' => 'Utiliza /SetAlias [email] [contraseña]']);
                }

            } else {
                $this->replyWithMessage(['text' => 'Ya ha iniciado sesión']);
            }
        }
    }
}
