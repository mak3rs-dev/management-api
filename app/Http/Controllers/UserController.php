<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\InCommunity;
use App\Models\Piece;
use App\Models\Role;
use App\Models\StockControl;
use App\Models\User;
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
     *         @OA\Property(property="community", description="Community", type="string"),
     *         @OA\Property(property="alias", description="Community", type="string"),
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
            'community' => 'nullable|string',
            'alias' => 'nullable|string'
        ]);

        if ($request->community == null && $request->alias == null) {
            return response()->json(['errors' => 'No se ha recibido ningún parámetro'], 422);
        }

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check Community exists
        $community = Community::when($request->community != null, function ($query) use ($request) {
            return $query->where('uuid', $request->community);
        })
        ->when($request->alias != null, function ($query) use ($request) {
            return $query->where('alias', $request->alias);
        })
        ->first();

        if ($community == null) {
            return response()->json(['errors' => 'No se encuentra ninguna comunidad!!'], 404);
        }

        // Check user join in community
        $inCommunity = InCommunity::where('community_id', $community->id)->where('user_id', auth()->user()->id)->count();

        if ($inCommunity > 0) {
            return response()->json(['errors' => 'Ya perteneces a está comundidad'], 500);
        }

        $inCommunity = null;
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
                if ($stockControl->units_manufactured < $request->units) {
                    return response()->json(['errors' => 'No puedes descontar stock que no tienes'], 500);
                }

                $stockControl->units_manufactured -= abs($request->units);
            }
        }

        if (!$stockControl->save()) {
            return response()->json(['errors' => 'No se ha podido añadir la pieza a la comunidad'], 500);
        }

        return response()->json(['message' => 'La pieza se ha añadido correctamente a la comunidad &#128521;'], 200);
    }

    /**
     * @OA\GET(
     *     path="/users/communities",
     *     tags={"User"},
     *     description="Listado de comunidades a las que pertenece el usuario",
     *     @OA\RequestBody( required=false,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="uuid", description="User", type="string"),
     *         @OA\Property(property="alias", description="", type="string"),
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=404, description=""),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function communities(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'uuid' => 'nullable|string',
            'alias' => 'nullable|string',
        ]);

        $user = null;

        if ($request->uuid == null && $request->alias == null) {
            $user = auth()->user();

        } else {
            $user = User::when($request->uuid != null, function ($query) use ($request) {
                return $query->where('uuid', $request->uuid);
            })
            ->when($request->alias != null, function ($query) use ($request) {
                return $query->where('alias', $request->alias);
           })->first();
        }

        if ($user == null) {
            return response()->json(['errors' => 'No se ha encontrado ningún usuario'], 404);
        }

        // Check user join comminities
        $inCommunity = InCommunity::select('community_id')->where('user_id', $user->id)->get()->toArray();

        if (count($inCommunity) == 0) {
            return response()->json(['errors' => 'No perteneces ha ninguna comunidad!!'], 404);
        }

        $community = Community::select('uuid', 'name', 'alias', 'created_at', 'updated_at')
            ->whereIn('id', $inCommunity)->paginate(15);

        return response()->json($community, 200);
    }
}
