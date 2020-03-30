<?php

namespace App\Http\Controllers;

use App\Models\CollectControl;
use App\Models\CollectPieces;
use App\Models\Community;
use App\Models\InCommunity;
use App\Models\Piece;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CollectControlController extends Controller
{
    /**
     *  @OA\POST(
     *     path="/communities/piece/add-collect",
     *     tags={"Community"},
     *     description="Añadimos una pieza a una recogida",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="uuid_piece", description="", type="string"),
     *         @OA\Property(property="uuid_community", description="", type="string"),
     *        @OA\Property(property="alias_community", description="", type="string"),
     *       @OA\Property(property="units", description="", type="integer"),
     *       @OA\Property(property="address", description="", type="string"),
     *       @OA\Property(property="location", description="", type="string"),
     *       @OA\Property(property="province", description="", type="string"),
     *       @OA\Property(property="state", description="", type="string"),
     *       @OA\Property(property="country", description="", type="string"),
     *       @OA\Property(property="cp", description="", type="string"),
     *
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
    public function addPieceCollection(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'uuid_piece' => 'required|string',
            'uuid_community' => 'nullable|string',
            'alias_community' => 'nullable|string',
            'units' => 'required|integer',
            'address' => 'nullable|string',
            'location' => 'nullable|string',
            'province' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'cp' => 'nullable|string|regex:^[0-9]+$'
        ], [
            'uuid_piece.required' => 'El nombre es requerido',
            'units.required' => 'La cantidad de la pieza es requerida',
            'cp.regex' => 'El código postal no puede contener letras'
        ]);

        if ($request->uuid_user == null && $request->alias_user == null) {
            return response()->json(['errors' => 'Los parámetros de la comunidad no son correctos!!'], 422);
        }

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $community = Community::when($request->uuid_community != null, function ($query) use ($request) {
            return $query->where('uuid', $request->uuid_community);
        })
            ->when($request->alias_community != null, function ($query) use ($request) {
                return $query->where('alias', $request->alias);
            })
            ->first();

        if ($community == null) {
            return response()->json(['errors' => 'No se encuentra la comunidad'], 404);
        }

        $piece = $community->Pieces()->where('uuid_piece', $request->uuid_piece)->first();

        if ($piece == null) {
            return response()->json(['errors' => 'La pieza no se encuentra en la comunidad'], 422);
        }

        $inCommunity = $community->InCommunities()->where('user_id', auth()->user->id)->first();

        if ($inCommunity == null) {
            return response()->json(['errors' => 'No perteneces a esta comunidad'], 422);
        }

        $collectControl = new CollectControl();
        $collectControl->in_community_id = $inCommunity->id;
        $collectControl->community_id = $community->id;
        $collectControl->user_id = auth()->user()->id;
        $collectControl->status_id = Status::where('code', 'REQUESTED')->first()->id;
        $collectControl->address = $request->address;
        $collectControl->location = $request->location;
        $collectControl->province = $request->province;
        $collectControl->state = $request->state;
        $collectControl->country = $request->country;
        $collectControl->cp = $request->cp;

        if (!$collectControl->save()) {
            return response()->json(['errors' => 'No se ha podido crear la recogida'], 500);
        }

        $collectPiece = new CollectPieces();
        $collectPiece->collect_control_id = $collectControl->id;
        $collectPiece->piece_id = $piece->id;
        $collectPiece->units = abs($request->units);

        if (!$collectPiece->save()) {
            return response()->json(['errors' => 'No se ha podido añadir la pieza a la recogida'], 500);
        }

        return response()->json(['message' => 'La recogida se ha creado correctamente'], 200);
    }
}
