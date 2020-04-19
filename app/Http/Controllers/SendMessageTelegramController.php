<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
     *         @OA\Property(property="user", description="uuid", type="string"),
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
            'user' => 'required|string',
            'message' => 'required|string'
        ], [
            'community.required' => 'La comunidad es requerida',
            'user.required' => 'El usuario es requerido',
            'message.required' => 'El mensaje es requerido'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $community = Community::where('uuid', $request->community)->first();
        if ($community == null) {
            return response()->json(['error' => 'No se encuentra la comunidad'], 404);
        }

        // Check role MAKE:ADMIN or USER:ADMIN
        $inCommunity = $community->InCommunitiesUser();
        if (!auth()->user()->hasRole('USER:ADMIN') && ($inCommunity == null || !$inCommunity->hasRole('MAKER:ADMIN'))) {
            return response()->json(['error' => 'No tienes permisos para enviar mensajes'], 403);
        }

        $user = User::where('uuid', $request->user)->first();
        if ($user == null) {
            return response()->json(['error' => 'No se encuentra el usuario'], 404);
        }

        if (!auth()->user()->hasRole('USER:ADMIN')) {
            $inCommunityUser = $community->InCommunities->where('user_id', $user->id)->first();
            if ($inCommunityUser == null) {
                return response()->json(['error' => 'El Mak3r no pertenece a tu comunidad'], 403);
            }
        }

        // Parse telegram_data
        $telData = json_decode($user->telegram_data);

        if ($telData != null && isset($telData->chatid)) {
            // SendMessage
            try {
                Telegram::sendMessage([
                    'chat_id' => $telData->chatid,
                    'text' => $request->message
                ]);

                return response()->json(['message' => 'El mensaje se ha enviado correctamente'], 200);

            } catch (TelegramSDKException $e) {
                return response()->json(['error' => 'El mensaje no se ha podido enviar correctamente'], 500);
            }

        } else {
            return response()->json(['error' => 'El usuario no tiene asociado un chat_id'], 500);
        }
    }
}
