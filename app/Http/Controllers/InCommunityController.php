<?php

namespace App\Http\Controllers;

use App\Exports\RankingExport;
use App\Models\Community;
use App\Models\Piece;
use App\Models\StockControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use DB;

class InCommunityController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * @OA\GET(
     *     path="/communities/ranking/{alias}/{export?stock}",
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
            'export' => $export,
            'user' => $request->user,
            'piece' => $request->piece
        ],[
            'alias' => 'required|string',
            'export' => 'nullable|string',
            'user' => 'nullable|string',
            'piece' => 'nullable|string'
        ], [
            'alias.required' => 'El alias es requerido'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $community = Community::where('alias', $alias)->first();

        if ($community == null) {
            return response()->json(['error' => 'No se encuentra la comunidad'], 404);
        }

        $inCommunity = $community->InCommunities;

        if (count($inCommunity) == 0) {
            return response()->json(['error' => 'La comunidad no tiene ningún mak3r'], 404);
        }

        $piece_id = null;

        if ($request->piece != null) {
            $piece_id = Piece::where('uuid', $request->piece)->first();
        }

        $select = ['u.name as user_name', DB::raw('IFNULL(SUM(sc.units_manufactured), 0) as units_manufactured'),
                    DB::raw('IFNULL(SUM(cp.units), 0) as units_collected'),
                    DB::raw('(units_manufactured - IFNULL(units, 0)) as stock'), 'ic.mak3r_num as mak3r_num',
                    'u.uuid as user_uuid', 'u.alias as user_alias'];

        $inCommunity = null;
        $inCommunity = $community->InCommunitiesUser();

        if ($inCommunity != null && $inCommunity->hasRole('MAKER:ADMIN') || auth()->user()->hasRole('USER:ADMIN') )) {
            array_push($select, 'u.address as user_address');
            array_push($select, 'u.location as user_location');
            array_push($select, 'u.province as user_province');
            array_push($select, 'u.state as user_state');
            array_push($select, 'u.country as user_country');
            array_push($select, 'u.cp as user_cp');
        }

        if ($export == "export" && ( $inCommunity->hasRole('MAKER:ADMIN') || auth()->user()->hasRole('USER:ADMIN') )) {
            return Excel::download(new RankingExport($community),'ranking.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        $ranking = StockControl::from('stock_control as sc')
            ->join('in_community as ic', 'sc.in_community_id', '=', 'ic.id')
            ->join('users as u', 'u.id', '=', 'ic.user_id')
            ->leftJoin('collect_control as cc', 'cc.in_community_id', '=', 'ic.id')
            ->leftJoin('collect_pieces as cp', 'cp.collect_control_id', '=', 'cc.id')
            ->select($select)
            ->where('ic.community_id', $community->id)
            ->when($request->user != null, function ($query) use ($request) {
                return $query->where('u.uuid', $request->user);
            })
            ->when($request->uuid != null, function ($query) use ($request) {
                return $query->where('p.uuid', $request->id);
            })
            ->when($piece_id != null, function ($query) use ($piece_id) {
                return $query->where(function ($query) use ($piece_id) {
					return $query->where('sc.piece_id', $piece_id->id)->orWhere('cp.piece_id', $piece_id->id);
				});
            })
            ->groupBy('ic.user_id')
            ->when(true, function ($query) use ($export) {
                if ($export == "stock") {
                    return $query->orderBy('stock', 'desc');

                } else {
                    return $query->orderBy('sc.units_manufactured', 'desc');
                }
            })
            ->paginate(15);

        return response()->json($ranking);
    }
}
