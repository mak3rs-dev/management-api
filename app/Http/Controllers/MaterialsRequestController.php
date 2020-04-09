<?php

namespace App\Http\Controllers;

use App\Models\Community;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaterialsRequestController extends Controller
{
    /**
     * @OA\POST(
     *     path="/communities/materials/add-or-update",
     *     tags={"Materials"},
     *     description="Cuando un usuario realiza o actualiza una pedido a una comunidad",
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
    public function add(Request $request) {
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
        $piece = $community->Pieces->where('uuid', $request->uuid_piece)->where('is_material', 1)->first();

        if ($piece == null) {
            return response()->json(['error' => 'No se encuentra ninguna pieza!!'], 404);
        }

        // Check materials exists
        $materialsRequest = $inCommunity->MaterialsRequest->where('piece_id', $piece->id)->first();

        if ($materialsRequest == null)  {
            // Create Stock
            if ($request->units < 0) {
                return response()->json(['error' => 'No puedes pedir con un valor menor que 0'], 500);
            }

            $materialsRequest = null;
            $materialsRequest = new MaterialsRequest();
            $materialsRequest->in_community_id = $inCommunity->id;
            $materialsRequest->piece_id = $piece->id;
            $materialsRequest->units_request = $request->units;

        } else {
            // Update Stock
            if ($request->units > 0) {
                $materialsRequest->units_request += $request->units;

            } else {
                if (($request->units + $materialsRequest->units_request) < 0) {
                    return response()->json(['error' => 'No puedes pedir con un valor menor que 0'], 500);
                }

                $collectMaterial_sum = $materialsRequest->CollectMaterials()->sum('units_delivered');

                if ($materialsRequest->units_request < $collectMaterial_sum) {
                    return response()->json(['error' => 'No puedes modificar el pedido del material por que ya te han entregado '.$collectMaterial_sum.' material(es)'], 500);
                }

                $materialsRequest->units_request -= abs($request->units);
            }
        }

        if (!$materialsRequest->save()) {
            return response()->json(['error' => 'No se ha podido hacer un pedido de materiales'], 500);
        }

        return response()->json(['message' => 'El pedido de material se ha creado correctamente'], 200);
    }
}
