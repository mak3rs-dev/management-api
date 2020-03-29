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
    public function ranking($alias) {
        // Validate request
        if ($alias == null) {
            return response()->json(['errors' => 'El alias no puede ser nulo'], 422);
        }

        $community = Community::where('alias', $alias)->first();

        if ($community == null) {
            return response()->json(['errors' => 'No se encuentra la comunidad'], 404);
        }

        $inCommunity = InCommunity::where('community_id', $community->id)->count();

        if ($inCommunity == 0) {
            return response()->json(['errors' => 'La comunidad no tiene ningún mak3r'], 404);
        }

        $ranking = StockControl::from('stock_control as sc')
            ->join('in_community as ic', 'sc.in_community_id', '=', 'ic.id')
            ->join('users as u', 'u.id', '=', 'ic.user_id')
            ->select('u.name as name', 'u.uuid as user_uuid', 'sc.units_manufactured as units_manufactured')
            ->where('ic.community_id', $community->id)
            ->orderBy('sc.units_manufactured', 'desc')
            ->paginate(15);

        return response()->json($ranking);
    }
}
