<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Piece;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
     *         @OA\Property(property="community", description="uuid", type="string"),
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
            'community' => 'nullable|string'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $community = null;

        if ($request->community != null) {
            $community = Community::where('uuid', $request->community)->first();
        }

        $pieces = Piece::when($request->name != null, function ($query) use ($request) {
            return $query->where('name', 'like', "$request->name%");
        })
        ->when($community != null, function ($query) use ($community) {
            return $query->where('community_id', $community->id);
        })
        ->paginate(15);

        return response()->json($pieces);
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
            'name' => 'required|string',
            'description' => 'nullable|string'
        ], [
            'name.required' => 'El nombre es requerido'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check permission USER:ADMIN
        if (auth()->user()->hasRole('USER:ADMIN')) {
            return response()->json(['errors' => 'No tienes permisos para crear una pieza &#128532;'], 403);
        }

        $piece = new Piece();
        $piece->uuid = Str::uuid();
        $piece->name = $request->name;
        $piece->description = $request->description;

        if (!$piece->save()) {
            return response()->json(['errors' => 'La pieza no se ha podido crear correctamente'], 500);
        }

        return response()->json([
            'piece' => $piece,
            'message' => 'La pieza se ha creado correctamente'
        ], 200);
    }
}
