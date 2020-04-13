<?php

namespace App\Http\Controllers;

use App\Models\CollectControl;
use App\Models\Community;
use App\Models\InCommunity;
use App\Models\Piece;
use App\Models\StockControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockControlController extends Controller
{
    /**
     * StockControlController constructor.
     */
    public function __construct()
    {
        $this->middleware(['jwt.auth', 'privacy.policy']);
    }

    /**
     * @OA\POST(
     *     path="/communities/piece/add-or-update",
     *     tags={"Stock"},
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
    public function addOrUpdatePieceStock(Request $request) {
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
            return response()->json(['error' => 'No se encuentra ninguna comunidad!!'], 404);
        }

        // Check join Community
        $inCommunity = $community->InCommunitiesUser();

        if ($inCommunity == null) {
            return response()->json(['error' => 'El usuario no pertenece a esta comunidad!!'], 404);
        }

        if ($inCommunity->isDisabledUser() || $inCommunity->isBlockUser()) {
            return response()->json(['error' => 'Estás dado de baja o bloqueado en la comunidad'], 422);
        }

        // Check pieces
        $piece = $community->Pieces->where('uuid', $request->uuid_piece)->where('is_piece', 1)->first();

        if ($piece == null) {
            return response()->json(['error' => 'No se encuentra ninguna pieza!!'], 404);
        }

        // Check stock exists
        $stockControl = $inCommunity->StockControl->where('piece_id', $piece->id)->first();

        if ($stockControl == null)  {
            // Create Stock
            if ($request->units < 0) {
                return response()->json(['error' => 'No puedes añadir piezas que no tienes'], 500);
            }

            $stockControl = null;
            $stockControl = new StockControl();
            $stockControl->in_community_id = $inCommunity->id;
            $stockControl->piece_id = $piece->id;
            $stockControl->units_manufactured = $request->units;

        } else {
            // Update Stock
            if ($request->units > 0) {
                $stockControl->units_manufactured += $request->units;

            } else {
                // Calculate sum collect pieces
                $sumUnits = CollectControl::from('collect_control as cc')
                    ->join('collect_pieces as cp', 'cp.collect_control_id', '=', 'cc.id')
                    ->join('status as st', 'st.id', '=', 'cc.status_id')
                    ->where('cc.in_community_id', $inCommunity->id)
                    ->where('cp.piece_id', $piece->id)
                    ->whereIn('st.code', ['COLLECT:DELIVERED', 'COLLECT:RECEIVED'])
                    ->sum('cp.units');

                if ($sumUnits == null || (is_array($sumUnits) && count($sumUnits) == 0) ) {
                    $sumUnits = 0;
                }

                if (($request->units + $stockControl->units_manufactured) < $sumUnits) {
                    return response()->json(['error' => 'No te puedes descontar stock que no tienes'], 500);
                }

                $stockControl->units_manufactured -= abs($request->units);
            }
        }

        if (!$stockControl->save()) {
            return response()->json(['error' => 'No se ha podido añadir la pieza a la comunidad'], 500);
        }

        return response()->json(['message' => 'La pieza se ha añadido correctamente a la comunidad'], 200);
    }
}
