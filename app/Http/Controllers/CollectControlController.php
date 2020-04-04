<?php

namespace App\Http\Controllers;

use App\Exports\CollectControlExport;
use App\Models\CollectControl;
use App\Models\CollectPieces;
use App\Models\Community;
use App\Models\InCommunity;
use App\Models\Piece;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class CollectControlController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * @OA\GET(
     *     path="/communities/collect-control/{alias?}/{export?}",
     *     tags={"Community"},
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
            'cp' => 'nullable|string|regex:/^[0-9]+$/'
        ], [
            'uuid_piece.required' => 'El nombre es requerido',
            'units.required' => 'La cantidad de la pieza es requerida',
            'cp.regex' => 'El código postal no puede contener letras'
        ]);

        if ($request->uuid_user == null && $request->alias_user == null) {
            return response()->json(['error' => 'Los parámetros de la comunidad no son correctos!!'], 422);
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
            return response()->json(['error' => 'No se encuentra la comunidad'], 404);
        }

        $piece = $community->Pieces->where('uuid_piece', $request->uuid_piece)->first();

        if ($piece == null) {
            return response()->json(['error' => 'La pieza no se encuentra en la comunidad'], 422);
        }

        $inCommunity = $community->InCommunitiesUser();

        if ($inCommunity == null) {
            return response()->json(['error' => 'No perteneces a esta comunidad'], 422);
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
            return response()->json(['error' => 'No se ha podido crear la recogida'], 500);
        }

        $collectPiece = new CollectPieces();
        $collectPiece->collect_control_id = $collectControl->id;
        $collectPiece->piece_id = $piece->id;
        $collectPiece->units = abs($request->units);

        if (!$collectPiece->save()) {
            return response()->json(['error' => 'No se ha podido añadir la pieza a la recogida'], 500);
        }

        return response()->json(['message' => 'La recogida se ha creado correctamente'], 200);
    }
}
