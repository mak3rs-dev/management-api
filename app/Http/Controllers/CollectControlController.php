<?php

namespace App\Http\Controllers;

use App\Exports\CollectControlExport;
use App\Models\CollectControl;
use App\Models\CollectPieces;
use App\Models\Community;
use App\Models\InCommunity;
use App\Models\Piece;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use DB;

class CollectControlController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * @OA\GET(
     *     path="/communities/collect-control/",
     *     tags={"Collect Control"},
     *     description="Obtenemos todas las recogidas",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="alias_community", description="", type="string"),
     *         @OA\Property(property="uuid_community", description="", type="string"),
     *         @OA\Property(property="export", description="export", type="string"),
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
     * @param null $export
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function getCollectControl(Request $request, $alias = null, $export = null) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'uuid_community' => 'nullable|string',
            'alias_community' => 'nullable|string',
            'export' => 'nullable|string'
        ]);

        if ($request->uuid == null && $request->alias == null) {
            return response()->json(['errors' => 'No se ha recibido ningún parámetro'], 422);
        }

        if ($alias != null && $request->alias == null) {
            $request->alias = $alias;
        }

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check community
        $community = Community::when($request->uuid != null, function ($query) use ($request) {
           return $query->where('uuid', $request->uuid);
        })
        ->when($request->alias != null, function ($query) use ($request) {
            return $query->where('alias', $request->alias);
        })
        ->first();

        if ($community == null) {
            return response()->json(['errors' => 'La comunidad no existe'], 404);
        }

        $inCommunity = $community->InCommunitiesUser();

        if ($inCommunity == null) {
            return response()->json(['errors' => 'No perteneces a esta comunidad'], 404);
        }

        if ($export == 'export' || ($request->export != null && $request->export == 'export')) {
            return Excel::download(new CollectControlExport($inCommunity),'collect_control.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        $select = ['u.name as name_user', 'u.alias as  alias_user', 'p.name as piece_name',
                    'pc.units as cantidad', 'cc.address as address', 'cc.location as location',
                    'cc.province as province', 'cc.state as state', 'cc.country as country',
                    'cc.cp as cp'];

        $CollectControl = CollectControl::from('collect_control as cc')
            ->join('collect_pieces as pc', 'pc.collect_control_id', '=', 'cc.id')
            ->join('pieces as p', 'p.id', '=', 'pc.piece_id')
            ->join('status as st', 'st.id', '=', 'cc.status_id')
            ->join('users as u', 'u.id', '=', 'cc.user_id')
            ->select($select)
            ->when(!$inCommunity->hasRole('MAKER:ADMIN'), function ($query) {
                return $query->where('user_id', auth()->user()->id);
            })
            ->where('cc.community_id', $community->id)
            ->paginate(15);

        return response()->json($CollectControl, 200);
    }

    /**
     *  @OA\POST(
     *     path="/communities/collect/add",
     *     tags={"Collect Control"},
     *     description="Añadimos una pieza a una recogida",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="community", description="", type="string"),
     *         @OA\Property(property="user", description="", type="string"),
     *        @OA\Property(property="status", description="", type="string"),
     *       @OA\Property(property="pieces", description="", type="array", @OA\Items(type="string", format="binary")),
     *       @OA\Property(property="address", description="", type="string"),
     *       @OA\Property(property="location", description="", type="string"),
     *       @OA\Property(property="province", description="", type="string"),
     *       @OA\Property(property="state", description="", type="string"),
     *       @OA\Property(property="country", description="", type="string"),
     *       @OA\Property(property="cp", description="", type="string")
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
            'community' => 'required|string',
            'user' => 'required|string',
            'status' => 'required|string',
            'pieces' => 'required|array',
            'address' => 'nullable|string',
            'location' => 'nullable|string',
            'province' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'cp' => 'nullable|string|regex:/^[0-9]+$/'
        ], [
            'community.required' => 'La comunidad es requerida',
            'user.required' => 'El usuario es requerido',
            'status.required' => 'El estado es requerido',
            'pieces.required' => 'Las piezas son requeridas',
            'pieces.array' => 'Las piezas deben de estar en un array',
            'cp.regex' => 'El código postal no puede contener letras'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $community = Community::where('uuid', $request->community)->first();

        if ($community == null) {
            return response()->json(['error' => 'No se encuentra la comunidad'], 404);
        }

        // Check user join community
        $user = User::where('uuid', $request->user)->first();

        if ($user == null) {
            return response()->json(['error' => 'No se encuentra el usuario'], 404);
        }

        $inCommunity = $community->InCommunities->where('user_id', $user->id)->first();

        if ($inCommunity == null) {
            return response()->json(['error' => 'El usuario no pertence a la comunidad'], 422);
        }

        // Check permissions in community
        if (!$user->hasRole('USER:ADMIN') && !$inCommunity->hasRole('MAKER:ADMIN')) {
            return response()->json(['error' => 'No tienes permisos para gestionar recogidas'], 403);
        }

        // Obtains status
        $status = Status::where('code', $request->status)->first();

        // Create transactions
        DB::beginTransaction();

        $collectControl = new CollectControl();
        $collectControl->in_community_id = $inCommunity->id;
        $collectControl->community_id = $community->id;
        $collectControl->user_id = $user->id;
        $collectControl->status_id = $status != null ? $status : Status::where('code', 'COLLECT:REQUESTED')->first()->id;
        $collectControl->address = $request->address;
        $collectControl->location = $request->location;
        $collectControl->province = $request->province;
        $collectControl->state = $request->state;
        $collectControl->country = $request->country;
        $collectControl->cp = $request->cp;

        if (!$collectControl->save()) {
            DB::rollBack();
            return response()->json(['error' => 'No se ha podido crear la recogida'], 500);
        }

        foreach ($request->pieces as $piece) {
            $p = Piece::where('uuid', $piece['piece'])->first();

            if ($p == null) {
                DB::rollBack();
                return response()->json(['error' => 'No se ha podido crear la recogida, por que no se ha encontrado una pieza'], 500);
            }

            $collectPiece = new CollectPieces();
            $collectPiece->collect_control_id = $collectControl->id;
            $collectPiece->piece_id = $p->id;
            $collectPiece->units = abs(intval($piece['units']));

            if (!$collectPiece->save()) {
                DB::rollBack();
                return response()->json(['error' => 'No se ha podido añadir la pieza a la recogida'], 500);
            }
        }

        DB::commit();
        return response()->json(['message' => 'La recogida se ha creado correctamente'], 200);
    }
}
