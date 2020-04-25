<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;

class SendMessageTelegramController extends Controller
{
    public function __construct()
    {
        $this->middleware(['jwt.auth', 'privacy.policy']);
    }

    /**
     *  @OA\POST(
     *     path="/telegram/sendmessage",
     *     tags={"Telegram Bot"},
     *     description="Enviar mensaje a un usuario mediante el bot",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="community", description="uuid", type="string"),
     *         @OA\Property(property="users", description="uuid", type="string"),
     *         @OA\Property(property="message", description="text", type="string"),
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=422, description=""),
     *     @OA\Response(response=404, description=""),
     *     @OA\Response(response=500, description=""),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function SendMessage(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'community' => 'required|string',
            'users' => 'required|array|min:1',
            'message' => 'required|string'
        ], [
            'community.required' => 'La comunidad es requerida',
            'user.required' => 'El usuario es requerido',
            'user.min' => 'Tiene que haber al menos un usuario',
            'message.required' => 'El mensaje es requerido'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $community = Community::where('alias', $request->community)->first();
        if ($community == null) {
            return response()->json(['error' => 'No se encuentra la comunidad'], 404);
        }

        // Check role MAKE:ADMIN or USER:ADMIN
        $inCommunity = $community->InCommunitiesUser();
        if (!auth()->user()->hasRole('USER:ADMIN') && ($inCommunity == null || !$inCommunity->hasRole('MAKER:ADMIN'))) {
            return response()->json(['error' => 'No tienes permisos para enviar mensajes'], 403);
        }

        $users = User::whereIn('uuid', $request->users)->get();

        $message = $request->message . "\n\nMensaje enviado por " . (Str::startsWith(auth()->user()->alias, "@") ? auth()->user()->alias : "@".auth()->user()->alias);
        $errors = [];
        foreach ($users as $user) {
            if (!auth()->user()->hasRole('USER:ADMIN')) {
                $inCommunityUser = $community->InCommunities->where('user_id', $user->id)->first();
                if ($inCommunityUser == null) {
                    $errors[] = "El Mak3r $user->alias no pertenece a tu comunidad";
                }
            }

            // Parse telegram_data
            $telData = json_decode($user->telegram_data);

            if ($telData != null && isset($telData->chatid)) {
                // SendMessage
                try {
                    Telegram::sendMessage([
                        'chat_id' => $telData->chatid,
                        'text' => $message
                    ]);

                } catch (TelegramSDKException $e) {
                    $errors[] = "Al usuario $user->alias no se le ha podido enviar el mensaje";
                }

            } else {
               $errors[] = "El usuario $user->alias no tiene un chat_id";
            }
        }

        if ($errors > 0) {
            return response()->json(['errors' => $errors], 500);
        }

        return response()->json(['message' => 'El mensaje se ha enviado correctamente'], 200);
    }
}
