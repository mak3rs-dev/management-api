<?php

namespace App\Http\Controllers;

use App\Models\Community;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommunityController extends Controller
{
    /**
     * Create a new CommunityController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
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

        $communities = Community::when($request->alias != null, function ($query) use ($request) {
            return $query->where('alias', 'like', "%$request->alias%");
        })
        ->when($request->name != null, function ($query) use ($request) {
            return $query->where('name', 'like', "%$request->name%");
        })
        ->paginate(15);

        return response()->json($communities);
    }
}
