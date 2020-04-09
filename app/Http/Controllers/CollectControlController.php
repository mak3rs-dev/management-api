<?php

namespace App\Http\Controllers;

use App\Exports\CollectControlExport;
use App\Models\CollectControl;
use App\Models\CollectMaterial;
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
     *         @OA\Property(property="status_code", description="export", type="string"),
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
            'status_code' => $request->status
        ], [
            'community' => 'required|string',
            'user' => 'nullable|string',
            'status_code' => 'nullable|string'
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
        $admin = false;

        $inCommunity = $community->InCommunitiesUser();

        // Check permissions in community
        if (auth()->user()->hasRole('USER:ADMIN') || $inCommunity->hasRole('MAKER:ADMIN')) {
            $admin = true;
        }

        if ($inCommunity == null) {
            return response()->json(['error' => 'No perteneces a la comunidad'], 422);
        }

        if ($inCommunity->isDisabledUser() || $inCommunity->isBlockUser()) {
            return response()->json(['error' => 'Estás dado de baja o bloqueado'], 422);
        }

        if ($request->user != null) {
            // Check permissions in community
            if (!$admin) {
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
        }

        $select = ['cc.id as id', 'u.name as user_name', 'ic.mak3r_num as mak3r_num', DB::raw('SUM(cp.units) as units_collected'),
                    'cc.address as collect_address', 'cc.location as collect_location', 'cc.province as collect_province',
                    'cc.state as collect_state', 'cc.country as collect_country', 'cc.cp as collect_cp',
                    'cc.address_description as collect_address_description', 'cc.created_at as created_at', 'st.name as status',
                    'st.code as status_code', 'u.uuid as user_uuid'];

        $collecControl = CollectControl::select($select)
                        ->from('collect_control as cc')
                        ->join('in_community as ic', 'cc.in_community_id', '=', 'ic.id')
                        ->join('collect_pieces as cp', 'cp.collect_control_id', '=', 'cc.id')
                        ->join('status as st', 'st.id', '=', 'cc.status_id')
                        ->join('users as u', 'u.id', '=', 'ic.user_id')
                        ->when($request->status_code != null, function ($query) use ($request) {
                            return $query->where('st.code', $request->status_code);
                        })
                        ->when($user != null, function ($query) use ($user) {
                            return $query->where('u.uuid', $user->uuid);
                        })
                        ->when($admin && $request->user == null, function ($query) use ($community)  {
                            return $query->where('ic.community_id', $community->id);
                        })
                        ->when(!$admin, function ($query) use ($inCommunity)  {
                            return $query->where('ic.id', $inCommunity->id);
                        })
                        ->with([
                            'Pieces' => function ($query) {
                                return $query->select('collect_control_id', 'units', 'piece_id')
                                    ->with([
                                        'Piece' => function ($query) {
                                            return $query->select('id', 'uuid', 'name', 'picture', 'description');
                                        }
                                    ]);
                            },
                            'Materials' => function ($query) {
                                return $query->select('collect_control_id', 'material_requests_id', 'units_delivered')
                                        ->with([
                                            'MaterialRequest' => function ($query) {
                                                return $query->select('id', 'piece_id', 'units_request')
                                                    ->with([
                                                        'Piece' => function ($query) {
                                                            return $query->select('id', 'uuid', 'name', 'picture', 'description');
                                                        },
                                                    ]);
                                            }
                                        ]);
                            }
                        ])
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
     *         @OA\Property(property="status_code", description="", type="string"),
     *         @OA\Property(property="pieces", description="", type="array", @OA\Items(type="string", format="binary")),
     *         @OA\Property(property="materials", description="", type="array", @OA\Items(type="string", format="binary")),
     *         @OA\Property(property="address", description="", type="string"),
     *         @OA\Property(property="location", description="", type="string"),
     *         @OA\Property(property="province", description="", type="string"),
     *         @OA\Property(property="state", description="", type="string"),
     *         @OA\Property(property="country", description="", type="string"),
     *         @OA\Property(property="address_description", description="", type="string"),
     *         @OA\Property(property="cp", description="", type="string")
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
            'status_code' => 'required|string',
            'pieces' => 'required|array|min:1',
            'materials' => 'nullable|array|min:1',
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
            'status_code.required' => 'El estado es requerido',
            'pieces.required' => 'Las piezas son requeridas',
            'pieces.array' => 'Las piezas deben de estar en un array',
            'pieces.min' => 'La colleción de piezas tiene que tener al menos una pieza',
            'materials.array' => 'Los materiales deben de estar en un array',
            'materials.min' => 'La colleción de materiales tiene que tener al menos una pieza',
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
        $status = Status::where('code', $request->status_code)->first();

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

        foreach ($request->pieces as $piece) {
            if (intval($piece['units']) > 0) {
                $p = Piece::where('uuid', $piece['uuid'])->first();

                if ($p == null) {
                    DB::rollBack();
                    return response()->json(['error' => 'No se ha podido crear la recogida, por que no se ha encontrado una pieza'], 500);
                }

                $units = intval($piece['units']);

                // Check stock
                $stock = $inCommunity->StockControl->where('piece_id', $p->id)->sum('units_manufactured') < $units;

                if ($stock) {
                    DB::rollBack();
                    return response()->json(['error' => 'Has pedido más piezas de las que tienes en stock'], 500);
                }

                $collectPiece = new CollectPieces();
                $collectPiece->collect_control_id = $collectControl->id;
                $collectPiece->piece_id = $p->id;
                $collectPiece->units = $units;

                if (!$collectPiece->save()) {
                    DB::rollBack();
                    return response()->json(['error' => 'No se ha podido añadir la pieza a la recogida'], 500);
                }
            }
        }

        if ($request->materials != null) {
            foreach ($request->materials as $material) {
                if (intval($material['units']) > 0) {
                    $p = Piece::where('uuid', $material['uuid'])->where('is_material', 1)->first();

                    if ($p == null) {
                        DB::rollBack();
                        return response()->json(['error' => 'No se ha podido crear la recogida, por que no se ha encontrado los materiales indicados'], 500);
                    }

                    // Obtains MaterialsRequest
                    $materialRequest = $inCommunity->MaterialsRequest->where('piece_id', $p->id)->first();

                    if ($materialRequest == null) {
                        DB::rollBack();
                        return response()->json(['error' => 'El material solicitado no esta creado como pedido de material'], 500);
                    }

                    $units = intval($material['units']);

                    if ($materialRequest->units_request < $units) {
                        DB::rollBack();
                        return response()->json(['error' => 'Has solicitado más material del que habías pedido previamente'], 500);
                    }

                    $collectMaterial = new CollectMaterial();
                    $collectMaterial->material_requests_id = $materialRequest->id;
                    $collectMaterial->collect_control_id = $collectControl->id;
                    $collectMaterial->units_delivered = $units;

                    if (!$collectMaterial->save()) {
                        DB::rollBack();
                        return response()->json(['error' => 'No se ha podido añadir el material a la recogida'], 500);
                    }
                }
            }
        }

        DB::commit();
        return response()->json(['message' => 'La recogida se ha creado correctamente'], 200);
    }


    /**
     * @OA\PUT(
     *     path="/communities/collect/update",
     *     tags={"Collect Control"},
     *     description="Actualizamos unas piezas a una recogida",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="collect", description="", type="integer"),
     *         @OA\Property(property="status_code", description="", type="string"),
     *         @OA\Property(property="pieces", description="", type="array", @OA\Items(type="string", format="binary")),
     *         @OA\Property(property="materials", description="", type="array", @OA\Items(type="string", format="binary")),
     *         @OA\Property(property="address", description="", type="string"),
     *         @OA\Property(property="location", description="", type="string"),
     *         @OA\Property(property="province", description="", type="string"),
     *         @OA\Property(property="state", description="", type="string"),
     *         @OA\Property(property="country", description="", type="string"),
     *         @OA\Property(property="address_description", description="", type="string"),
     *         @OA\Property(property="cp", description="", type="string")
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
    public function update(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'collect' => 'required|integer',
            'status_code' => 'required|string',
            'pieces' => 'required|array|min:1',
            'materials' => 'nullable|array|min:1',
            'address' => 'nullable|string',
            'location' => 'nullable|string',
            'province' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'address_description' => 'nullable|string',
            'cp' => 'nullable|string|regex:/^[0-9]+$/'
        ], [
            'status_code.required' => 'El estado es requerido',
            'pieces.required' => 'Las piezas son requeridas',
            'pieces.array' => 'Las piezas deben de estar en un array',
            'pieces.min' => 'La colleción de piezas tiene que tener al menos una pieza',
            'materials.array' => 'Los materiales deben de estar en un array',
            'materials.min' => 'La colleción de materiales tiene que tener al menos una pieza',
            'cp.regex' => 'El código postal no puede contener letras'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->collect < 0) {
            return response()->json(['error' => 'El identificador no puede ser inferior a 0'], 422);
        }

        // Check exist id
        $collect_control = CollectControl::where('id', $request->collect)->first();

        if ($collect_control == null) {
            return response()->json(['error' => 'No se encuentra la recogida'], 404);
        }

        // Check in_community
        $inCommunity = InCommunity::where('id', $collect_control->in_community_id)->first();

        if ($inCommunity == null) {
            return response()->json(['error' => 'La recogida no pertenece a ninguna comunidad'], 404);
        }

        $admin = false;

        if (!auth()->user()->hasRole('USER:ADMIN')) {
            // Different user in community
            if (auth()->user()->id != $inCommunity->user_id) {
                // Check user admin in community
                $userCommunity = auth()->user()->InCommunities->where('community_id', $inCommunity->id)->first();

                if ($userCommunity == null) {
                    return response()->json(['error' => 'Tu no perteneces a la comunidad'], 404);
                }

                if ($userCommunity->hasRole('MAKER:ADMIN')) {
                    $admin = true;

                } else {
                    return response()->json(['error' => 'No tienes permisos para gestionar esta recogida'], 403);
                }
            }

        } else {
            $admin = true;
        }

        if (!$admin && ( $inCommunity->isDisabledUser() || $inCommunity->isBlockUser() )) {
            return response()->json(['error' => 'No perteneces a esta comunidad o estas bloqueado'], 403);
        }

        if (!$admin && $collect_control->hasStatus('COLLECT:DELIVERED|COLLECT:RECEIVED')) {
            return response()->json(['error' => 'La recogida ha sido entregada o recibida, por el cual no se puede modificar'], 422);
        }

        // Obtains status
        $status = Status::where('code', $request->status_code)->first();

        // Create transactions
        DB::beginTransaction();

        $collect_control->status_id = $status != null ? $status->id : $collect_control->status_id;
        $collect_control->address = $request->address != null ? $request->address : $collect_control->location;
        $collect_control->location = $request->location != null ? Str::ucfirst($request->location) :  $collect_control->location;
        $collect_control->province = $request->province != null ? Str::ucfirst($request->province) : $collect_control->province;
        $collect_control->state = $request->state != null ? Str::ucfirst($request->state) : $collect_control->state;
        $collect_control->country = $request->country != null ? Str::ucfirst($request->country) : $collect_control->country;
        $collect_control->address_description = $request->address_description != null ? $request->address_description : $collect_control->address_description;
        $collect_control->cp = $request->cp != null ? $request->cp :  $collect_control->cp;

        if (!$collect_control->save()) {
            DB::rollBack();
            return response()->json(['error' => 'No se ha podido crear la recogida'], 500);
        }

        $count = 0;
        foreach ($request->pieces as $piece) {
            $p = Piece::where('uuid', $piece['uuid'])->first();

            $units = intval($piece['units']);

            // Check stock
            $stock = $inCommunity->StockControl->where('piece_id', $p->id)->sum('units_manufactured') < $units;

            if ($stock) {
                DB::rollBack();
                return response()->json(['error' => 'Has pedido más piezas de las que tienes en stock'], 500);
            }

            $collect = $collect_control->CollectPieces;

            foreach ($collect as $pieceCollect) {
                if ($p != null && $p->id == $pieceCollect->piece_id) {
                    if ($units > 0) {

                        $pieceCollect->units = intval($piece['units']);
                        if (!$pieceCollect->save()) {
                            DB::rollBack();
                            return response()->json(['error' => 'No se ha podido actualizar la pieza a la recogida'], 500);
                        }

                    } else {
                        if (count($request->pieces) > 1) {
                            $pieceCollect->delete();

                        } else {
                            DB::rollBack();
                            return response()->json(['error' => 'La recogida no se puede quedar sin piezas'], 500);
                        }
                    }

                } else {
                    $count++;
                }
            }

            if (count($collect) == $count) {
                $collectPiece = new CollectPieces();
                $collectPiece->collect_control_id = $collect_control->id;
                $collectPiece->piece_id = $p->id;
                $collectPiece->units = $units;

                if (!$collectPiece->save()) {
                    DB::rollBack();
                    return response()->json(['error' => 'No se ha podido añadir la pieza a la recogida'], 500);
                }
            }
        }

        $count = 0;
        foreach ($request->materials as $material) {
            $p = Piece::where('uuid', $material['uuid'])->where('is_material', 1)->first();

            if ($p == null) {
                DB::rollBack();
                return response()->json(['error' => 'No se ha podido crear la recogida, por que no se ha encontrado los materiales indicados'], 500);
            }

            // Obtains MaterialsRequest
            $materialRequest = $inCommunity->MaterialsRequest->where('piece_id', $p->id)->first();

            if ($materialRequest == null) {
                DB::rollBack();
                return response()->json(['error' => 'El material solicitado no esta creado como pedido de material'], 500);
            }

            $units = intval($material['units']);

            if ($p != null && $p->id == $materialRequest->piece_id && $materialRequest->units_request >= $units) {

                $collect = $collect_control->CollectMaterial;
                foreach ($collect as $materialCollect) {

                        if ($materialRequest->id == $materialCollect->material_requests_id) {
                            if ($units > 0) {
                                $materialCollect->units_delivered = $units;

                                if (!$materialCollect->save()) {
                                    DB::rollBack();
                                    return response()->json(['error' => 'No se ha podido añadir el material a la recogida'], 500);
                                }

                            } else {
                                $materialCollect->delete();
                            }

                        } else {
                            $count++;
                        }
                }

                if (count($collect) == $count) {
                    $collectMaterial = new CollectMaterial();
                    $collectMaterial->material_requests_id = $materialRequest->id;
                    $collectMaterial->collect_control_id = $collect_control->id;
                    $collectMaterial->units_delivered = $units;

                    if (!$collectMaterial->save()) {
                        DB::rollBack();
                        return response()->json(['error' => 'No se ha podido añadir el material a la recogida'], 500);
                    }
                }

            } else {
                DB::rollBack();
                return response()->json(['error' => 'Has indicado más materiales de los que has solicitado'], 500);
            }

        }

        DB::commit();
        return response()->json(['message' => 'La recogida se ha actualizado correctamente'], 200);
    }
}
