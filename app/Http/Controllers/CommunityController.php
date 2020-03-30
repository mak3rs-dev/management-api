<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\InCommunity;
use App\Models\StockControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    /**
     * Create a new CommunityController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['communities', 'alias']]);
    }

    /**
     * @OA\GET(
     *     path="/communities/all",
     *     tags={"Community"},
     *     description="Obtenemos todas las comunidades",
     *     @OA\RequestBody( required=false,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="alias", description="", type="string"),
     *         @OA\Property(property="name", description="", type="string"),
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description="List Communities"),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function communities(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'alias' => 'nullable|string',
            'name' => 'nullable|string'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $communities = Community::select('name', 'alias', 'description', 'created_at')
        ->when($request->alias != null, function ($query) use ($request) {
            return $query->where('alias', 'like', "%$request->alias%");
        })
        ->when($request->name != null, function ($query) use ($request) {
            return $query->where('name', 'like', "%$request->name%");
        })
        ->paginate(15);

        return response()->json($communities);
    }

    /**
     * @OA\GET(
     *     path="/communities/alias/{alias}",
     *     tags={"Community"},
     *     description="Obtenemos la comunidad por su alias",
     *     @OA\Response(response=200, description="Object Community or null"),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function alias(Request $request, $alias = "") {
        // Validate request
        $validator = Validator::make([
            'alias' => $alias,
        ],[
            'alias' => 'required|string',
        ], [
            'alias.required' => 'El alias es requerido'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $checkUser = auth()->check();
        $select = [];

        if ($checkUser) {
            $select = ['id', 'uuid', 'alias', 'description', 'created_at', 'user'];

        } else {
            $select = ['alias', 'description', 'created_at', 'user'];
        }

        $community = Community::where('alias', $alias)->first();

        if ($community == null) {
            return response()->json(['errors' => 'La comunidad no se encuentra'], 404);
        }

        $community->user = false;

        if ($checkUser) {
            $inCommunity = $community->InCommunities()->where('user_id', auth()->user()->id)->count();
            if ($inCommunity > 0) {
                $community->user = true;
            }
        }

        unset($community->id);

        return response()->json($community, 200);
    }

    /**
     * @OA\POST(
     *     path="/communities/create",
     *     tags={"Community"},
     *     description="Creamos la comunidad",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="alias", description="", type="string"),
     *         @OA\Property(property="name", description="", type="string"),
     *         @OA\Property(property="description", description="", type="string"),
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description="Object Community or null"),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'alias' => 'required|string|unique:community',
            'name' => 'required|string',
            'description' => 'nullable|string'
        ], [
            'alias.required' => 'El alias es requerido',
            'alias.unique' => 'El alias introducido ya está en uso',
            'name.required' => 'El nombre es requerido'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check controller access
        if (!auth()->user()->hasRole('USER:ADMIN')) {
            return response()->json(['errors' => 'No tienes permisos para crear comunidades &#128532;'], 422);
        }

        $community = new Community();
        $community->uuid = Str::uuid();
        $community->alias = $request->alias;
        $community->name = $request->name;
        $community->description = $request->description;

        if (!$community->save()) {
            return response()->json(['errors' => 'No se ha podido crear la comunidad'], 500);
        }

        return response()->json([
            'community' => $community,
            'message' => 'La comunidad se ha creado correctamente'
        ], 200);
    }

    /**
     * @OA\PUT(
     *     path="/communities/update",
     *     tags={"Community"},
     *     description="Actualizamos la comunidad",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="uuid", description="", type="string"),
     *         @OA\Property(property="name", description="", type="string"),
     *         @OA\Property(property="description", description="", type="string"),
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description="Object Community or null"),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|string',
            'name' => 'required|string',
            'description' => 'nullable|string'
        ], [
            'uuid.required' => 'El uuid es requerido',
            'alias.required' => 'El alias es requerido',
            'name.required' => 'El nombre es requerido'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check community exists
        $community = Community::where('uuid', $request->uuid)->first();

        if ($community == null) {
            return response()->json(['errors' => 'La comunidad no existe'], 404);
        }

        $community->name = $request->name;
        $community->description = $request->description;

        if (!$community->save()) {
            return response()->json(['errors' => 'No se ha podido actualizar la comunidad'], 500);
        }

        return response()->json([
            'community' => $community,
            'message' => 'La comunidad se ha actualizado correctamente'
        ], 200);
    }

    /**
     * @OA\DELETE(
     *     path="/communities/delete",
     *     tags={"Community"},
     *     description="Borramos la comunidad",
     *     @OA\RequestBody( required=false,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="uuid", description="", type="string"),
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description="ok"),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|string'
        ], [
            'uuid.required' => 'El uuid es requerido'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check community exists
        $community = Community::where('uuid', $request->uuid)->first();

        if ($community == null) {
            return response()->json(['errors' => 'La comunidad no existe'], 404);
        }

        $countInCommunity = $community->InCommunities()->select('id')->get()->ToArray();

        if ($countInCommunity > 0) {
            return response()->json(['errors' => 'La comunidad no se puede eliminar por que tiene usuarios asignados'], 500);
        }

        $countPieces = $community->InCommunities()->StockControl()->count();

        // TODO: Calculate pieces in stock community

        if ($countPieces > 0) {
            return response()->json(['errors' => 'La comunidad no se puede eliminar por que tiene piezas en stock'], 500);
        }

        if (!$community->delete()) {
            return response()->json(['errors' => 'No se ha podido borrar la comunidad'], 500);
        }

        return response()->json([
            'message' => 'La comunidad se ha borrado correctamente'
        ], 200);
    }
}
