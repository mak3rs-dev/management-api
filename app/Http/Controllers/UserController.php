<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\InCommunity;
use App\Models\Piece;
use App\Models\Role;
use App\Models\StockControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * @OA\POST(
     *     path="/communities/join",
     *     tags={"Community"},
     *     description="Cuando un usuario se quiere añadir a una comunidad",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="uuid", description="Community", type="string"),
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
    public function joinCommunity(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|string',
        ], [
            'uuid.required' => 'El identificador de la comunidad es requerido'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check Community exists
        $community = Community::where('uuid', $request->uuid)->first();

        if ($community == null) {
            return response()->json(['errors' => 'No se encuentra ninguna comunidad!!'], 404);
        }

        $inCommunity = new InCommunity();
        $inCommunity->community_id = $community->id;
        $inCommunity->user_id = auth()->user()->id;
        $inCommunity->role_id = Role::where('name', 'MAKER:USER')->first()->id;

        if (!$inCommunity->save()) {
            return response()->json(['errors' => 'El usuario no se ha podido unir a la comunidad'], 500);
        }

        return response()->json(['message' => 'El usuario se ha añadido a la comunidad correctamente'], 200);
    }

    /**
     * @OA\POST(
     *     path="/communities/piece/add-or-update",
     *     tags={"Community"},
     *     description="Cuando un usuario añade o actualiza una pieza a una comunidad",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="uuid_community", description="Community", type="string"),
     *         @OA\Property(property="uuid_piece", description="Piece", type="string"),
     *        @OA\Property(property="units", description="", type="integer"),
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
    public function addorUpdatePieceStock(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'uuid_community' => 'required|string',
            'uuid_piece' => 'required|string',
            'units' => 'required|integer'
        ], [
            'uuid_community.required' => 'El identificador de la comunidad es requerido',
            'uuid_piece.required' => 'El identificador de la pieza es requerido',
            'units.required' => 'Las unidades de la pieza son requeridas',
            'units.integer' => 'El valor de la unidades de pieza tiene que ser númerico'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check Community exists
        $community = Community::where('uuid', $request->uuid_community)->first();

        if ($community == null) {
            return response()->json(['errors' => 'No se encuentra ninguna comunidad!!'], 404);
        }

        // Check join Community
        $inCommunity = InCommunity::where('community_id', $community->id)->first();

        if ($inCommunity == null) {
            return response()->json(['errors' => 'El usuario no pertenece a esta comunidad!!'], 404);
        }

        // Check pieces
        $piece = Piece::where('uuid', $request->uuid_piece)->first();

        if ($piece == null) {
            return response()->json(['errors' => 'No se encuentra ninguna pieza!!'], 404);
        }

        // Check stock exists
        $stockControl = StockControl::where('in_community_id', $inCommunity->id)->where('piece_id', $piece->id)->first();

        if ($stockControl == null)  {
            // Create Stock
            if ($request->units < 0) {
                return response()->json(['errors' => 'No puedes añadir piezas que no tienes &#128530;'], 500);
            }

            $stockControl = null;
            $stockControl = new StockControl();
            $stockControl->in_community_id = $inCommunity->id;
            $stockControl->piece_id = $piece->id;
            $stockControl->units_manufactured = $request->units;

        } else {
            // Update Stock
            // We check units > 0
            if ($request->units > 0) {
                $stockControl->units_manufactured += $request->units;

            } else {
                $stockControl->units_manufactured -= abs($request->units);
            }
        }

        if (!$stockControl->save()) {
            return response()->json(['errors' => 'No se ha podido añadir la pieza a la comunidad'], 500);
        }

        return response()->json(['message' => 'La pieza se ha añadido correctamente a la comunidad &#128521;'], 200);
    }
}
