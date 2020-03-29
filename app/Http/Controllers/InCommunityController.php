<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\InCommunity;
use App\Models\StockControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InCommunityController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * @OA\GET(
     *     path="/communities/ranking/{alias}",
     *     tags={"Community"},
     *     description="Ranking de la comunidad",
     *     @OA\Response(response=200, description="ok"),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ranking(Request $request, $alias = "") {
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

        $community = Community::where('alias', $alias)->first();

        if ($community == null) {
            return response()->json(['errors' => 'No se encuentra la comunidad'], 404);
        }

        $inCommunity = InCommunity::where('community_id', $community->id)->count();

        if ($inCommunity == 0) {
            return response()->json(['errors' => 'La comunidad no tiene ningún mak3r'], 404);
        }

        $select = [];

        if (auth()->check()) {
            $select = ['u.name as name', 'u.uuid as user_uuid', 'sc.units_manufactured as units_manufactured'];

        } else {
            $select = ['u.name as name', 'sc.units_manufactured as units_manufactured'];
        }

        $ranking = StockControl::from('stock_control as sc')
            ->join('in_community as ic', 'sc.in_community_id', '=', 'ic.id')
            ->join('users as u', 'u.id', '=', 'ic.user_id')
            ->select($select)
            ->where('ic.community_id', $community->id)
            ->orderBy('sc.units_manufactured', 'desc')
            ->paginate(15);

        return response()->json($ranking);
    }
}
