<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerified;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['login', 'register', 'verifiedHash']]);
    }

    /**
     * @OA\POST(
     *     path="/auth/login",
     *     tags={"Auth"},
     *     description="Logueo",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="email", description="", type="string"),
     *         @OA\Property(property="password", description="", type="string"),
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description=""),
     *     @OA\Response(response=401, description="Unauthorized; Email not verified")
     * )
     *
     * Get a JWT via given credentials.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string'
        ], [
            'email.required' => 'El email es requerido',
            'password.required' => 'La contraseña es requerida',
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!$token = auth()->attempt(['email' => $request->email, 'password' => $request->password])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();

        // We check that the user has validated their email
        if ($user->email_verified_at == null && $user->hash_email_verified != null) {
            return response()->json(['error' => 'Email not verified'], 200);
        }

        return $this->respondWithToken($token);
    }

    /**
     * @OA\POST(
     *     path="/auth/register",
     *     tags={"Auth"},
     *     description="Registro",
     *     @OA\RequestBody(required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="email", description="",  type="string"),
     *         @OA\Property(property="password", description="", type="string"),
     *         @OA\Property(property="password_confirm", description="", type="string"),
     *         @OA\Property(property="phone", description="", type="string"),
     *         @OA\Property(property="name", description="", type="string"),
     *         @OA\Property(property="address", description="Dirección", type="string"),
     *         @OA\Property(property="location", description="Localidad", type="string"),
     *         @OA\Property(property="province", description="Provincia", type="string"),
     *         @OA\Property(property="state", description="Comunidad", type="string"),
     *         @OA\Property(property="country", description="País", type="string"),
     *         @OA\Property(property="cp", description="Código Postal", type="string")
     *       ),
     *     ),
     *     ),
     *     @OA\Response( response=200, description="Registrado correctamente"),
     *     @OA\Response( response=500, description="El usuario no se ha podido registrar correctamente")
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function register(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|unique:users',
            'phone' => 'required|string',
            'name' => 'required|string',
            'password' => 'required|string|min:8|max:12',
            'password_confirm' => 'required|string|same:password',
            'address' => 'nullable|string',
            'location' => 'nullable|string',
            'province' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'cp' => 'nullable|string'
        ], [
            'email.required' => 'El email es requerido',
            'email.unique' => 'El email introducido ya ha sido registrado',
            'phone.required' => 'El teléfono es requerido',
            'name.required' => 'El nombre es requerido',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La longitud mínima de la contraseña es de 8 caracteres',
            'password.max' => 'La longitud máxima de la contraseña es de 12 caracteres',
            'password.same' => 'Las contraseñas no son iguales'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create user
        $user = new User();
        $user->uuid = Str::uuid();
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->hash_email_verified = Str::uuid();
        $user->role_id = Role::where('name', 'USER:COMMON')->first()->id;
        $user->address = $request->address;
        $user->location = $request->location;
        $user->province = $request->province;
        $user->state = $request->state;
        $user->country = $request->country;
        $user->cp = $request->cp;

        if (!$user->save()) {
            return response()->json(['errors' => 'El usuario no se ha podido registrar correctamente'], 500);
        }

        try {
            Mail::to($user->email)->send(new EmailVerified(route('verified_hash', $user->hash_email_verified)));
        } catch (\Exception $e) {
            $user->delete();
            return response()->json([
                'errors' => 'El usuario no se ha podido registrar correctamente'
            ], 500);
        }

        return response()->json(['message' => 'Registrado correctamente'], 200);
    }

    /**
     *@OA\GET(
     *     path="/auth/verified-hash",
     *     tags={"Auth"},
     *     description="Validación del hash",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="hash", description="", type="string"),
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description=""),
     * )
     */
    public function verifiedHash(Request $request) {
        $user = User::where('hash_email_verified', $request->hash)->first();

        if ($user == null) {
            return redirect('/');
        }

        $user->hash_email_verified = null;
        $user->email_verified_at = Carbon::now();

        if (!$user->save()) {
            return redirect('/');
        }

        return redirect()->to('https://management.mak3rs.tk/login?action=accountactivated');
    }

    /**
     *@OA\GET(
     *     path="/auth/me",
     *     tags={"Auth"},
     *     description="Obtener información de mi",
     *     @OA\Response(response=200, description=""),
     * )
     *
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * @OA\GET(
     *     path="/auth/logout",
     *     tags={"Auth"},
     *     description="Desloguear mi usuario",
     *     @OA\Response(response=200, description="Desconectado correctamente"),
     * )
     *
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Desconectado correctamente']);
    }

    /**
     * *@OA\GET(
     *     path="/auth/refresh",
     *     tags={"Auth"},
     *     description="Actualizar mi token",
     *     @OA\Response(response=200, description=""),
     * )
     *
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
