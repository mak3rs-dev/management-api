<?php

namespace App\Http\Controllers;

use App\Exports\RankingExport;
use App\Models\Community;
use App\Models\InCommunity;
use App\Models\Piece;
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

        $select = ['u.name as user_name', 'ic.mak3r_num as mak3r_num', 'u.uuid as user_uuid', 'u.alias as user_alias'];

        $inCommunity = null;
        $inCommunity = $community->InCommunitiesUser();

        if ($inCommunity != null && ( $inCommunity->hasRole('MAKER:ADMIN') || auth()->user()->hasRole('USER:ADMIN') )) {
            if ($request->piece != null) {
                $piece_id = Piece::where('uuid', $request->piece)->first();
            }

            array_push($select, 'u.address as user_address');
            array_push($select, 'u.location as user_location');
            array_push($select, 'u.province as user_province');
            array_push($select, 'u.state as user_state');
            array_push($select, 'u.country as user_country');
            array_push($select, 'u.cp as user_cp');
            array_push($select, 'u.address_description as user_address_description');
        }

        $ranking = DB::query()
            ->selectRaw('a.*, (a.units_manufactured - a.units_collected) as stock')
            ->fromSub(function ($query) use ($select, $community, $request, $export, $piece_id) {
                $query->select($select)
                    ->from('in_community as ic')
                    ->join('users as u', 'u.id', '=', 'ic.user_id')
                    ->where('ic.community_id', $community->id)
                    ->addSelect(
                        [
                            'units_manufactured' => function ($query) use ($piece_id) {
                                return $query->selectRaw('IFNULL(SUM(sc.units_manufactured), 0)')
                                    ->from('stock_control as sc')
                                    ->whereColumn('sc.in_community_id', 'ic.id')
                                    ->when($piece_id != null, function ($query) use ($piece_id) {
                                        return $query->where('sc.piece_id', $piece_id->id);
                                    });
                            },
                            'units_collected' => function ($query) use ($piece_id) {
                                return $query->selectRaw('IFNULL(SUM(cp.units), 0)')
                                    ->from('collect_control as cc')
                                    ->join('collect_pieces as cp', 'cp.collect_control_id', '=', 'cc.id')
                                    ->join('status as st', 'cc.status_id', '=', 'st.id')
                                    ->whereColumn('cc.in_community_id', 'ic.id')
                                    ->whereIn('st.code', ['COLLECT:DELIVERED', 'COLLECT:RECEIVED'])
                                    ->when($piece_id != null, function ($query) use ($piece_id) {
                                        return $query->where('cp.piece_id', $piece_id->id);
                                    });
                            }
                        ]
                    )
                    ->when($request->user != null, function ($query) use ($request) {
                        return $query->where('u.uuid', $request->user);
                    })
                    ->groupBy('ic.user_id');
            }, 'a')
            ->when(true, function ($query) use ($export) {
                if ($export == "stock") {
                    return $query->orderBy('stock', 'desc');

                } else {
                    return $query->orderBy('units_manufactured', 'desc');
                }
            });

        if ($export == "export" && ( $inCommunity->hasRole('MAKER:ADMIN') || auth()->user()->hasRole('USER:ADMIN') )) {
            return Excel::download(new RankingExport($community, $ranking),'ranking.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return response()->json($ranking->paginate(50));
    }
}
