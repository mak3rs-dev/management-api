<?php

namespace App\Http\Controllers;

use App\Exports\RankingExport;
use App\Models\Community;
use App\Models\InCommunity;
use App\Models\StockControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class InCommunityController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * @OA\GET(
     *     path="/communities/ranking/{alias}/{export}",
     *     tags={"Community"},
     *     description="Ranking de la comunidad",
     *     @OA\Response(response=200, description="ok"),
     * )
     *
     * @param Request $request
     * @param string $alias
     * @param string $export
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function ranking(Request $request, $alias = "", $export = "") {
        // Validate request
        $validator = Validator::make([
            'alias' => $alias,
            'export' => $export
        ],[
            'alias' => 'required|string',
            'export' => 'nullable|string',
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

        $inCommunity = $community->InCommunities()->count();

        if ($inCommunity == 0) {
            return response()->json(['errors' => 'La comunidad no tiene ningún mak3r'], 404);
        }

        $select = ['u.name as user_name', 'sc.units_manufactured as units_manufactured'];

        if (auth()->check()) {
            array_push($select, 'u.uuid as user_uuid');
            array_push($select, 'u.alias as user_alias');

            $inCommunity = null;
            $inCommunity = $community->InCommunities()->where('user_id', auth()->user()->id)->first();

            if ($inCommunity != null && $inCommunity->hasRole('MAKER:ADMIN')) {
                array_push($select, 'u.address as user_address');
                array_push($select, 'u.location as user_location');
                array_push($select, 'u.province as user_province');
                array_push($select, 'u.state as user_state');
                array_push($select, 'u.country as user_country');
                array_push($select, 'u.cp as user_cp');
            }
        }

        if ($export == "export") {
            return Excel::download(new RankingExport($community, $select),'ranking.csv', \Maatwebsite\Excel\Excel::CSV);
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
