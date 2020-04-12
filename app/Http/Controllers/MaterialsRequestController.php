<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\MaterialRequest;
use App\Models\Piece;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;

class MaterialsRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware(['jwt.auth', 'privacy.policy']);
    }

    /**
     *  @OA\GET(
     *     path="/communities/materials/{alias}",
     *     tags={"Materials"},
     *     description="Listado de materiales pedidos por el usuario",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="piece", description="Piece uuid", type="string"),
     *         @OA\Property(property="user", description="User uuid", type="string"),
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
    public function get(Request $request, $alias = null) {
        // Validate request
        $validator = Validator::make([
            'community' => $alias,
            'piece' => $request->piece,
            'user' => $request->user
        ], [
            'community' => 'required|string',
            'piece' => 'nullable|string',
            'user' => 'nullable|string'
        ], [
            'community.required' => 'El identificador de la comunidad es requerido'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $community = Community::where('alias', $alias)->first();

        if ($community == null) {
            return response()->json(['error' => 'No se encuentra ninguna comunidad!!'], 404);
        }

        $user = null;
        $admin = false;

        // Check join Community
        $inCommunity = $community->InCommunitiesUser();

        if ($inCommunity == null) {
            return response()->json(['error' => 'El usuario no pertenece a esta comunidad!!'], 404);
        }

        if ($inCommunity->isDisabledUser() || $inCommunity->isBlockUser()) {
            return response()->json(['error' => 'Estás dado de baja o bloqueado en la comunidad'], 422);
        }

        // Check permissions in community
        if (auth()->user()->hasRole('USER:ADMIN') || $inCommunity->hasRole('MAKER:ADMIN')) {
            $admin = true;
        }

        if ($request->user != null) {
            if (!$admin) {
                return response()->json(['error' => 'No tienes permisos para poder gestionar materiales'], 403);
            }

            $user = User::where('uuid', $request->user)->first();

            if ($user == null) {
                return response()->json(['error' => 'El mak3r introducido no se encuentra'], 404);
            }

            // Check join
            $inCommunity = $community->InCommunities->where('user_id', $user->id)->first();

            if ($inCommunity == null) {
                return response()->json(['error' => 'El mak3r introducido no pertenece a la comunidad'], 422);
            }

            if ($inCommunity->isDisabledUser() || $inCommunity->isBlockUser()) {
                return response()->json(['error' => 'El mak3r en la comunidad esta dado de baja o bloqueado'], 422);
            }
        }

        $piece = null;

        if ($request->piece != null) {
            $piece = Piece::where('uuid', $request->piece)->where('is_material', 1)->first();
        }

        //$adminQueryUnits = !$admin && $request->user == null ? 'mr.units_request' : DB::raw('IFNULL(SUM(mr.units_request), 0) as units_request');

        $materialsRequest = MaterialRequest::from('material_requests as mr')
            ->select('p.uuid', 'p.name', 'p.picture', 'mr.units_request', DB::raw('IFNULL(SUM(cm.units_delivered), 0) as units_delivered'))
            ->join('pieces as p', 'p.id', '=', 'mr.piece_id')
            ->join('in_community as ic', 'mr.in_community_id', '=', 'ic.id')
            ->join('users as u', 'u.id', '=', 'ic.user_id')
            ->leftJoin('collect_materials as cm', 'cm.material_requests_id', '=', 'mr.id')
            ->when($user != null, function ($query) use ($user) {
                return $query->where('u.uuid', $user->uuid);
            })
            ->when($piece != null, function ($query) use ($piece) {
                return $query->where('p.uuid', $piece->uuid);
            })
            ->when($admin && $request->user == null && $piece == null, function ($query) use ($community)  {
                return $query->where('ic.community_id', $community->id);
            })
            ->when(!$admin, function ($query) use ($inCommunity)  {
                return $query->where('ic.id', $inCommunity->id);
            })
            ->groupBy('cm.material_requests_id')
            ->paginate(15);

        return response()->json($materialsRequest, 200);
    }

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
    public function addOrUpdate(Request $request) {
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
            $materialsRequest = new MaterialRequest();
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

                if ($materialsRequest->units_request <= $collectMaterial_sum) {
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
