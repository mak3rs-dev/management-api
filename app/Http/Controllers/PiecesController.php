<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Piece;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PiecesController extends Controller
{
    /**
     * Create a new CommunityController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['uuid']]);
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
}
