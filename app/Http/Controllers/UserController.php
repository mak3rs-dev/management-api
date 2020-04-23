<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\InCommunity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['jwt.auth', 'privacy.policy']);
    }

    /**
     * @OA\GET(
     *     path="/communities/{alias}/users",
     *     tags={"All Users of Community"},
     *     description="Cuando un usuario se quiere añadir a una comunidad",
     *     @OA\RequestBody( required=false,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="q", description="search", type="string"),
     *         @OA\Property(property="mak3r_num", description="", type="string"),
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
    public function getUserOfCommunity(Request $request, $alias = null) {
        // Validate request
        $validator = Validator::make([
            'community' => $alias,
            'q' => $request->q,
            'mak3r_num' => $request->mak3r_num
        ], [
            'community' => 'required|string',
            'q' => 'nullable|string',
            'mak3r_num' => 'nullable|integer'
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

        $inCommunity = $community->InCommunitiesUser();

        // Check permissions in community
        if (!auth()->user()->hasRole('USER:ADMIN') && ( $inCommunity == null || !$inCommunity->hasRole('MAKER:ADMIN'))) {
            return response()->json(['error' => 'No tienes permisos'], 403);
        }

        $users = User::select('u.name', 'u.alias', 'u.uuid', 'ic.mak3r_num')
            ->from('users as u')
            ->join('in_community as ic', 'u.id', '=', 'ic.user_id')
            ->where('ic.community_id', $community->id)
            ->when($request->q != null, function ($query) use ($request) {
                return $query->where('u.name', 'like', "%$request->q%")->orWhere('u.alias', 'like', "%$request->q%");
            })
            ->when($request->mak3r_num != null, function ($query) use ($request) {
                return $query->where('ic.mak3r_num', $request->mak3r_num);
            })
            ->limit(100)
            ->get();

        return response()->json($users, 200);
    }

    /**
     * @OA\POST(
     *     path="/communities/join",
     *     tags={"Community"},
     *     description="Cuando un usuario se quiere añadir a una comunidad",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="community", description="Community", type="string"),
     *         @OA\Property(property="alias", description="Community", type="string"),
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
    public function joinCommunity(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'community' => 'nullable|string',
            'alias' => 'nullable|string'
        ]);

        if ($request->community == null && $request->alias == null) {
            return response()->json(['error' => 'No se ha recibido ningún parámetro'], 422);
        }

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check Community exists
        $community = Community::when($request->community != null, function ($query) use ($request) {
            return $query->where('uuid', $request->community);
        })
        ->when($request->alias != null, function ($query) use ($request) {
            return $query->where('alias', $request->alias);
        })
        ->first();

        if ($community == null) {
            return response()->json(['error' => 'No se encuentra ninguna comunidad!!'], 404);
        }

        // Check user join in community
        $inCommunity = $community->InCommunitiesUser();

        if ($inCommunity == null) {
            // Calculate last mak3r_num
            $lastNumMaker = $community->InCommunities()->select('mak3r_num')->orderBy('mak3r_num', 'desc')->first();

            $inCommunity = null;
            $inCommunity = new InCommunity();
            $inCommunity->community_id = $community->id;
            $inCommunity->user_id = auth()->user()->id;
            $inCommunity->role_id = Role::where('name', 'MAKER:USER')->first()->id;
            $inCommunity->mak3r_num = $lastNumMaker == null ? 1 : $lastNumMaker->mak3r_num + 1;

            if (!$inCommunity->save()) {
                return response()->json(['error' => 'El usuario no se ha podido unir a la comunidad'], 500);
            }

            return response()->json(['message' => 'El usuario se ha añadido a la comunidad correctamente'], 200);

        } else {
            if ($inCommunity->isBlockUser()) {
                return response()->json(['error' => 'Estas bloquedado en esta comunidad, por el cual no te puedes unir'], 500);
            }

            if (!$inCommunity->isDisabledUser()) {
                return response()->json(['error' => 'Ya perteneces a esta comunidad!!'], 500);
            }

            $inCommunity->disabled_at = null;
            return response()->json(['message' => 'Te has reunido a la comunidad correctamente'], 200);
        }
    }

    /**
     * @OA\GET(
     *     path="/users/communities",
     *     tags={"User"},
     *     description="Listado de comunidades a las que pertenece el usuario",
     *     @OA\RequestBody( required=false,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="uuid", description="User", type="string"),
     *         @OA\Property(property="alias", description="", type="string"),
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=404, description=""),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function communities(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'uuid' => 'nullable|string',
            'alias' => 'nullable|string',
        ]);

        $user = null;

        if ($request->uuid == null && $request->alias == null) {
            $user = auth()->user();

        } else {
            $user = User::when($request->uuid != null, function ($query) use ($request) {
                return $query->where('uuid', $request->uuid);
            })
            ->when($request->alias != null, function ($query) use ($request) {
                return $query->where('alias', $request->alias);
           })->first();
        }

        if ($user == null) {
            return response()->json(['error' => 'No se ha encontrado ningún mak3r'], 404);
        }

        // Check user join comminities
        $inCommunity = InCommunity::select('community_id')
                        ->where('user_id', $user->id)
                        ->where([
                            ['disabled_at', '=', null],
                            ['blockuser_at', '=', null]
                        ])
                        ->get()->toArray();

        if (count($inCommunity) == 0) {
            return response()->json(['error' => 'No perteneces a ninguna comunidad!!'], 404);
        }

        $community = Community::select('uuid', 'name', 'alias', 'created_at', 'updated_at')
            ->whereIn('id', $inCommunity)->paginate(15);

        return response()->json($community, 200);
    }
}
