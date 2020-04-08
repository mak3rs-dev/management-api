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
     *         @OA\Property(property="uuid", description="Piece uuid", type="string"),
     *         @OA\Property(property="community", description="Community uuid", type="string"),
     *         @OA\Property(property="alias", description="Community alias", type="string"),
     *         @OA\Property(property="type_piece", description="Tipo de pieza {piece|material}", type="string"),
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description="List Pieces"),
     *     @OA\Response(response=422, description=""),
     *     @OA\Response(response=403, description=""),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pieces(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string',
            'uuid' => 'nullable|string',
            'community' => 'nullable|string',
            'alias' => 'nullable|string',
            'type_piece' => 'nullable|string'
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

        $inCommunity = $community != null ? $community->inCommunities() : null;

        if ($inCommunity == null) {
            return response()->json(['error' => 'Tu no perteneces a la comunidad'], 422);
        }

        // Status
        $status = Status::whereIn('code', ['COLLECT:DELIVERED', 'COLLECT:RECEIVED'])->pluck('id')->toArray();
        $sql = '(SELECT IFNULL(SUM(cp.units), 0) FROM collect_pieces as cp INNER JOIN collect_control as cc on cp.collect_control_id = cc.id WHERE cp.piece_id = p.id
                and cc.status_id in ('.implode(',', $status).')) as units_collected';

        $select = ['p.uuid', 'p.name', 'p.picture', 'p.description',
                    DB::raw('(SELECT IFNULL(SUM(units_manufactured), 0) FROM stock_control WHERE piece_id = p.id) as units_manufactured'),
                    DB::raw($sql)];

        $pieces = Piece::from('pieces as p')
            ->select($select)
            ->when($request->name != null, function ($query) use ($request) {
                return $query->where('p.name', 'like', "$request->name%");
            })
            ->when($community != null, function ($query) use ($community) {
                return $query->where('p.community_id', $community->id);
            })
            ->when($request->uuid != null, function ($query) use ($request) {
                return $query->where('p.uuid', $request->id);
            })
            ->when($request->type_piece == 'piece', function ($query) use ($request) {
                return $query->where('p.is_piece', 1)->where('p.is_material', 0);
            })
            ->when($request->type_piece == 'material', function ($query) use ($request) {
                return $query->where('p.is_piece', 0)->where('p.is_material', 1);
            })
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
        $pieces = $community->InCommunities()
                    ->join('stock_control as sc', 'sc.in_community_id', '=', 'ic')
            ->with([
                'StockControl' => function ($query) use ($status) {
                    return $query->selectRaw('piece_id, SUM(units_manufactured) as units_manufactured')->groupBy('piece_id');
                },
                'CollectControl.CollectPieces' => function ($query) use ($status) {
                    return $query->selectRaw('piece_id, SUM(units) as units')->groupBy('piece_id');
                }
            ])
            ->groupBy('stock_control.piece_id')
            ->paginate(15);
        // FIN COMMUNITY

        // USER
        $inCommunitiesUser = $community->InCommunitiesUser();
        $stockControlUser = [];
        $collectControlUser = [];
        $collectPiecesUser = [];

        if ($inCommunitiesUser != null && !$inCommunitiesUser->isDisabledUser() && !$inCommunitiesUser->isBlockUser()) {
            $stockControlUser = $inCommunitiesUser->StockControl()->selectRaw('piece_id, SUM(units_manufactured) as units_manufactured')
                ->with([
                    'Piece' => function ($query) use ($status) {
                        return $query->select('id', 'community_id', 'name', 'picture', 'description');
                    }
                ])
                ->groupBy('piece_id')
                ->get();

            $collectControlUser = $inCommunitiesUser->CollectControl->whereIn('status_id', $status)->pluck('id')->toArray();

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

        if ($community == null) {
            return response()->json(['error' => 'La comunidad seleccionada no existe!'], 404);
        }

        $inCommunity = $community->InCommunitiesUser();

        if ($inCommunity == null) {
            return response()->json(['error' => 'No perteneces a la comunidad, para poder crear una pieza'], 404);
        }

        // Check permission USER:ADMIN
        if (!auth()->user()->hasRole('USER:ADMIN') && !$inCommunity->hasRole('MAKER:ADMIN')) {
            return response()->json(['error' => 'No tienes permisos para crear una pieza'], 403);
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
