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
use Illuminate\Support\Str;
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
     *     path="/communities/collect/{communty}",
     *     tags={"Collect Control"},
     *     description="Obtenemos todas las recogidas",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="user", description="", type="string"),
     *         @OA\Property(property="status", description="export", type="string"),
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
    public function getCollectControl(Request $request, $alias = null) {
        // Validate request
        $validator = Validator::make([
            'community' => $alias,
            'user' => $request->user,
            'status' => $request->status
        ], [
            'community' => 'required|string',
            'user' => 'nullable|string',
        ], [
            'community.required' => 'La comunidad es requerida'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check community
        $community = Community::where('alias', $alias)->first();

        if ($community == null) {
            return response()->json(['error' => 'La comunidad no se encuentra'], 404);
        }

        $user = null;
        $inCommunity = null;

        if ($request->user != null) {
            // Check join
            $inCommunity = $community->InCommunitiesUser();

            if ($inCommunity == null) {
                return response()->json(['error' => 'El mak3r introducido no pertenece a la comunidad'], 422);
            }

            // Check permissions in community
            if (!auth()->user()->hasRole('USER:ADMIN') && !$inCommunity->hasRole('MAKER:ADMIN')) {
                return response()->json(['error' => 'No tienes permisos para gestionar recogidas de otros usuarios'], 403);
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

        } else {
            $inCommunity = $community->InCommunitiesUser();

            if ($inCommunity == null) {
                return response()->json(['error' => 'No perteneces a la comunidad'], 422);
            }

            if ($inCommunity->isDisabledUser() || $inCommunity->isBlockUser()) {
                return response()->json(['error' => 'Estás dado de baja o bloqueado'], 422);
            }
        }

        $select = ['u.name as user_name', 'ic.mak3r_num as mak3r_num', DB::raw('SUM(cp.units) as units_collected'),
                    'cc.address as collect_address', 'cc.location as collect_location', 'cc.province as collect_province',
                    'cc.state as collect_state', 'cc.country as collect_country', 'cc.cp as collect_cp',
                    'cc.address_description as collect_address_description', 'cc.created_at as created_at', 'st.name as status'];

        $collecControl = CollectControl::select($select)
                        ->from('collect_control as cc')
                        ->join('in_community as ic', 'cc.in_community_id', '=', 'ic.id')
                        ->join('collect_pieces as cp', 'cp.collect_control_id', '=', 'cc.id')
                        ->join('status as st', 'st.id', '=', 'cc.status_id')
                        ->join('users as u', 'u.id', '=', 'ic.user_id')
                        ->when($request->status != null, function ($query) use ($request) {
                            return $query->where('st.code', $request->status);
                        })
                        ->when($user != null, function ($query) use ($user) {
                            return $query->where('u.uuid', $user->uuid);
                        })
                        ->where('ic.community_id', $community->id)
                        ->groupBy('cp.collect_control_id')
                        ->orderBy('created_at', 'desc')
                        ->paginate(15);

        return response()->json($collecControl, 200);
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
     *       @OA\Property(property="address_description", description="", type="string"),
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
            'pieces' => 'required|array|min:1',
            'address' => 'nullable|string',
            'location' => 'nullable|string',
            'province' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'address_description' => 'nullable|string',
            'cp' => 'nullable|string|regex:/^[0-9]+$/'
        ], [
            'community.required' => 'La comunidad es requerida',
            'user.required' => 'El usuario es requerido',
            'status.required' => 'El estado es requerido',
            'pieces.required' => 'Las piezas son requeridas',
            'pieces.array' => 'Las piezas deben de estar en un array',
            'pieces.min' => 'La colleción de piezas tiene que tener al menos una pieza',
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

        if ($inCommunity->isDisabledUser() || $inCommunity->isBlockUser()) {
            return response()->json(['error' => 'El mak3r en la comunidad está dado de baja o bloqueado'], 422);
        }

        $inCommunityUserAuth = $community->InCommunitiesUser();

        if ($inCommunityUserAuth == null) {
            return response()->json(['error' => 'Tu no pertences a la comunidad indicada'], 422);
        }

        // Check permissions in community
        if (!auth()->user()->hasRole('USER:ADMIN') && !$inCommunityUserAuth->hasRole('MAKER:ADMIN')) {
            return response()->json(['error' => 'No tienes permisos para gestionar recogidas'], 403);
        }

        // Check user only collect control
        $status = Status::where('code', 'COLLECT:REQUESTED')->pluck('id')->toArray();
        $collectControl = CollectControl::where('in_community_id', $inCommunity->id)->whereIn('status_id', $status)->count();

        if ($collectControl > 0) {
            return response()->json(['error' => 'El usuario ya tiene una recogida en curso'], 422);
        }

        $status = null;
        $collectControl = null;

        // Obtains status
        $status = Status::where('code', $request->status)->first();

        // Create transactions
        DB::beginTransaction();

        $collectControl = new CollectControl();
        $collectControl->in_community_id = $inCommunity->id;
        $collectControl->status_id = $status != null ? $status->id : Status::where('code', 'COLLECT:REQUESTED')->first()->id;
        $collectControl->address = $request->address;
        $collectControl->location = $request->location != null ? Str::ucfirst($request->location) : null;
        $collectControl->province = $request->province != null ? Str::ucfirst($request->province) : null;
        $collectControl->state = $request->state != null ? Str::ucfirst($request->state) : null;
        $collectControl->country = $request->country != null ? Str::ucfirst($request->country) : null;
        $collectControl->address_description = $request->address_description;
        $collectControl->cp = $request->cp;

        if (!$collectControl->save()) {
            DB::rollBack();
            return response()->json(['error' => 'No se ha podido crear la recogida'], 500);
        }

        $count = 0;
        foreach ($request->pieces as $piece) {
            if (intval($piece['units']) > 0) {
                $p = Piece::where('uuid', $piece['uuid'])->first();

                if ($p == null) {
                    DB::rollBack();
                    return response()->json(['error' => 'No se ha podido crear la recogida, por que no se ha encontrado una pieza'], 500);
                }

                $collectPiece = new CollectPieces();
                $collectPiece->collect_control_id = $collectControl->id;
                $collectPiece->piece_id = $p->id;
                $collectPiece->units = intval($piece['units']);

                if (!$collectPiece->save()) {
                    DB::rollBack();
                    return response()->json(['error' => 'No se ha podido añadir la pieza a la recogida'], 500);
                }

                $count++;
            }
        }

        if ($count == 0) {
            DB::rollBack();
            return response()->json(['error' => 'Debe haber al menos una pieza en la recogida'], 422);
        }

        DB::commit();
        return response()->json(['message' => 'La recogida se ha creado correctamente'], 200);
    }
}
