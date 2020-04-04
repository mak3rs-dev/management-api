<?php

namespace App\Http\Controllers;

use App\Models\CollectControl;
use App\Models\CollectPieces;
use App\Models\Community;
use App\Models\InCommunity;
use App\Models\Piece;
use App\Models\Status;
use App\Models\StockControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use DB;

class PiecesController extends Controller
{
    /**
     * Create a new CommunityController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * @OA\GET(
     *     path="/pieces/all",
     *     tags={"Pieces"},
     *     description="Obtenemos todas las piezas",
     *     @OA\RequestBody( required=false,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="name", description="", type="string"),
     *         @OA\Property(property="community", description="Community uuid", type="string"),
     *         @OA\Property(property="alias", description="Community alias", type="string"),
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description="List Pieces"),
     *     @OA\Response(response=422, description=""),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pieces(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string',
            'community' => 'nullable|string',
            'alias' => 'nullable|string'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $community = null;

        if ($request->community != null) {
            $community = Community::where('uuid', $request->community)->first();
        }

        if ($request->alias != null) {
            $community = Community::where('alias', $request->alias)->first();
        }

        // Status
        $status = Status::whereIn('code', ['COLLECT:DELIVERED', 'COLLECT:RECEIVED'])->pluck('id')->toArray();

        $select = ['p.uuid as piece_uuid', 'p.name as piece_name', 'p.picture as piece_picture', 'p.description as piece_description',
                    DB::raw('IFNULL(SUM(sc.units_manufactured), 0) as units_manufactured'), DB::raw('IFNULL(SUM(cp.units), 0) as units_collected'),
                    DB::raw('(IFNULL(SUM(sc.units_manufactured), 0) - IFNULL(SUM(cp.units), 0)) as stock')];

        $pieces = Piece::from('pieces as p')
            ->join('in_community as ic', 'ic.community_id', '=', 'p.community_id')
            ->leftJoin('collect_control as cc', 'cc.in_community_id', '=', 'ic.id')
            ->leftJoin('collect_pieces as cp', 'cp.collect_control_id', '=', 'cc.id')
            ->leftJoin('stock_control as sc', 'sc.in_community_id', '=', 'ic.id')
            ->select($select)
            ->when($request->name != null, function ($query) use ($request) {
                return $query->where('p.name', 'like', "$request->name%");
            })
            ->when($community != null, function ($query) use ($community) {
                return $query->where('p.community_id', $community->id);
            })
            ->groupBy('p.piece_id', 'cp.piece_id', 'sc.piece_id')
            ->paginate(15);

        return response()->json($pieces);
    }

    /**
     * @OA\GET(
     *     path="/communities/pieces/{alias}",
     *     tags={"Pieces"},
     *     description="Obtenemos todas las piezas de la comunidad",
     *     @OA\Response(response=200, description="List Pieces"),
     *     @OA\Response(response=422, description=""),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function piecesOfCommunity(Request $request, $alias = null) {
        // Validate request
        $validator = Validator::make([
            'alias' => $alias
        ], [
            'alias' => 'required|string'
        ], [
            'alias.required' => 'El alias es requerido'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $community = Community::where('alias', $alias)->first();

        if ($community == null) {
            return response()->json(['error' => 'No se encuentra la comunidad'], 404);
        }

        // Status
        $status = Status::whereIn('code', ['COLLECT:DELIVERED', 'COLLECT:RECEIVED'])->pluck('id')->toArray();

        // COMMUNITY
        $pieces = $community->Pieces()
            ->with([
                'StockControl' => function ($query) use ($status) {
                    return $query->selectRaw('piece_id, SUM(units_manufactured) as units_manufactured')->groupBy('piece_id');
                },
                'CollectControl.CollectPieces' => function ($query) use ($status) {
                    return $query->selectRaw('piece_id, SUM(units_manufactured) as units_manufactured')->groupBy('piece_id');
                }
            ])
            ->paginate(15);
        // FIN COMMUNITY

        // USER
        $inCommunitiesUser = $community->InCommunitiesUser();
        $stockControlUser = [];
        $collectControlUser = [];
        $collectPiecesUser = [];

        if ($inCommunitiesUser != null) {
            $stockControlUser = StockControl::selectRaw('piece_id, SUM(units_manufactured) as units_manufactured')
                ->where('in_community_id', $inCommunitiesUser->id)
                ->with([
                    'Piece' => function ($query) use ($status) {
                        return $query->select('id', 'community_id', 'name', 'picture', 'description');
                    }
                ])
                ->groupBy('piece_id')
                ->get();

            $collectControlUser = CollectControl::where('in_community_id', $inCommunitiesUser->id)->whereIn('status_id', $status)->pluck('id')->toArray();

            $collectPiecesUser = CollectPieces::selectRaw('piece_id, SUM(units) as units')
                ->whereIn('collect_control_id', $collectControlUser)
                ->with([
                    'Piece' => function ($query) use ($status) {
                        return $query->select('id', 'community_id', 'name', 'picture', 'description');
                    }
                ])
                ->groupBy('piece_id')
                ->get();
        }
        // FIN USER

        $result = [
            'pieces' => $pieces,
            'user' => [
                'stock_control' => $stockControlUser,
                'collect_pieces' => $collectPiecesUser
            ]
        ];

        return response()->json($result);
    }

    /**
     * @OA\POST(
     *     path="/pieces/create",
     *     tags={"Pieces"},
     *     description="Creamos una pieza",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="name", description="", type="string"),
     *         @OA\Property(property="description", description="", type="string"),
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description="Piece"),
     *     @OA\Response(response=422, description=""),
     *     @OA\Response(response=500, description=""),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'community' => 'required|string',
            'name' => 'required|string',
            'description' => 'nullable|string'
        ], [
            'community.required' => 'La comunidad es requerida',
            'name.required' => 'El nombre es requerido'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $community = Community::where('alias', $request->community)->orWhere('uuid', $request->community)->first();

        $inCommunity = $community->InCommunitiesUser();

        if ($inCommunity == null) {
            return response()->json(['error' => 'No perteneces a la comunidad, para poder crear una pieza'], 404);
        }

        // Check permission USER:ADMIN
        if (!auth()->user()->hasRole('USER:ADMIN') && !$inCommunity->hasRole('MAKER:ADMIN')) {
            return response()->json(['error' => 'No tienes permisos para crear una pieza'], 403);
        }

        if ($community == null) {
            return response()->json(['error' => 'La comunidad seleccionada no existe!'], 404);
        }

        $piece = new Piece();
        $piece->uuid = Str::uuid();
        $piece->community_id = $community->id;
        $piece->name = $request->name;
        $piece->description = $request->description;

        if (!$piece->save()) {
            return response()->json(['error' => 'La pieza no se ha podido crear correctamente'], 500);
        }

        return response()->json([
            'piece' => $piece,
            'message' => 'La pieza se ha creado correctamente'
        ], 200);
    }
}
