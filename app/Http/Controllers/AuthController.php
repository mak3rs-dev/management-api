<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerified;
use App\Mail\RecoveryPassword;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $this->middleware('jwt.auth', ['except' => ['login', 'register', 'verifiedHash', 'recoveryPasword', 'recoveryHash']]);
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
            'email' => 'required',
            'password' => 'required|string'
        ], [
            'email.required' => 'El email es requerido',
            'password.required' => 'La contraseña es requerida',
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        /*$user = User::where('alias', $request->email)->first();

        if ($user != null) {
            $request->email = $user->email;
        }*/

        if (!$token = auth()->attempt(['email' => $request->email, 'password' => $request->password])) {
            return response()->json(['error' => 'Los datos de inicio de sesión son incorrectos'], 401);
        }

        $user = auth()->user();

        // We check that the user has validated their email
        if ($user->email_verified_at == null && $user->hash_email_verified != null) {
            auth()->logout();
            return response()->json(['error' => 'No has verificado el Email!!'], 500);
        }

        // Check user not recovery password
        if ($user->hash_password_verified != null) {
            auth()->logout();
            return response()->json(['error' => 'Has solicitado recuperar tu contraseña, hasta que no indiques tu nueva contraseña no podrás iniciar sesión.'], 500);
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
     *         @OA\Property(property="alias", description="", type="string"),
     *         @OA\Property(property="address", description="Dirección", type="string"),
     *         @OA\Property(property="location", description="Localidad", type="string"),
     *         @OA\Property(property="province", description="Provincia", type="string"),
     *         @OA\Property(property="state", description="Comunidad", type="string"),
     *         @OA\Property(property="country", description="País", type="string"),
     *         @OA\Property(property="address_description", description="Descripción sobre la dirección", type="string"),
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
            'email' => 'required|email|unique:users',
            'phone' => 'required|string',
            'name' => 'required|string',
            'alias' => 'nullable|string|unique:users',
            'password' => 'required|string|min:8|max:12',
            'password_confirm' => 'required|string|same:password',
            'address' => 'nullable|string',
            'location' => 'nullable|string',
            'province' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'address_description' => 'nullable|string',
            'cp' => 'nullable|string|regex:/^[0-9]+$/'
        ], [
            'email.required' => 'El email es requerido',
            'email.email' => 'El email no es válido',
            'email.unique' => 'El email introducido ya ha sido registrado',
            'phone.required' => 'El teléfono es requerido',
            'name.required' => 'El nombre es requerido',
            'alias.unique' => 'El alias introducido ya está en uso :(',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La longitud mínima de la contraseña es de 8 caracteres',
            'password.max' => 'La longitud máxima de la contraseña es de 12 caracteres',
            'password_confirm.required' => 'La contraseña de confirmación es requerida',
            'password_confirm.same' => 'Las contraseñas no son iguales',
            'cp.regex' => 'El código postal no es correcto, porfavor elimine las letras'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create user
        $user = new User();
        $user->uuid = Str::uuid();
        $user->email = Str::lower($request->email);
        $user->password = bcrypt($request->password);
        $user->name = $request->name;
        $user->alias = $request->alias;
        $user->phone = $request->phone;
        $user->hash_email_verified = Str::uuid();
        $user->role_id = Role::where('name', 'USER:COMMON')->first()->id;
        $user->address = $request->address;
        $user->location = $request->location != null ? Str::ucfirst($request->location) : null;
        $user->province = $request->province != null ? Str::ucfirst($request->province) : null;
        $user->state = $request->state != null ? Str::ucfirst($request->state) : null;
        $user->country = $request->country != null ? Str::ucfirst($request->country) : null;
        $user->address_description = $request->address_description;
        $user->cp = $request->cp;

        if (!$user->save()) {
            return response()->json(['error' => 'El usuario no se ha podido registrar correctamente'], 500);
        }

        try {
            Mail::to($user->email)->send(new EmailVerified(route('verified_hash', $user->hash_email_verified)));
        } catch (\Exception $e) {
            $user->delete();
            return response()->json([
                'error' => 'El usuario no se ha podido registrar correctamente'
            ], 500);
        }

        return response()->json(['message' => 'Registrado correctamente'], 200);
    }

    /**
     *@OA\GET(
     *     path="/auth/verified-hash/{hash}",
     *     tags={"Auth"},
     *     description="Validación del hash",
     *     @OA\Response(response=200, description=""),
     * )
     */
    public function verifiedHash(Request $request) {
        $user = User::where('hash_email_verified', $request->hash)->first();

        if ($user == null) {
            return response()->json(['error' => 'El hash no es válido']);
        }

        $user->hash_email_verified = null;
        $user->email_verified_at = Carbon::now();

        if (!$user->save()) {
            return response()->json(['error' => 'No se ha podido validar el email']);
        }

        return redirect()->to(env('APP_CLIENT').'/login?msg=accountactivated');
    }

    /**
     * @OA\POST(
     *     path="/auth/recovery-password",
     *     tags={"Auth"},
     *     description="Recuperación de contraseña",
     *     @OA\RequestBody(required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="email", description="",  type="string")
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description=""),
     *     @OA\Response(response=422, description=""),
     *     @OA\Response(response=500, description=""),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recoveryPasword(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ], [
            'email.required' => 'El email es requerido',
            'email.email' => 'El email no es válido'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        $message = 'Si el email es correcto te habrá llegado un correo indicando como recuperar tu contraseña';

        if ($user == null) {
            return response()->json(['message' => $message], 500);
        }

        try {
            if ($user->hash_email_verified != null) {
                Mail::to($user->email)->send(new EmailVerified(route('verified_hash', $user->hash_email_verified)));
                return response()->json(['message' => $message], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se ha podido recuperar la contraseña, porfavor inténtelo nuevamente'
            ], 500);
        }

        // Create hash
        $user->hash_password_verified = Str::uuid();

        if (!$user->save()) {
            return response()->json(['error' => 'No se ha podido recuperar la contraseña, porfavor inténtelo nuevamente'], 500);
        }

        $url = url(env('APP_CLIENT').'/recover?hash='.$user->hash_password_verified);

        try {
            Mail::to($user->email)->send(new RecoveryPassword($url));

        } catch (\Exception $e) {
            return response()->json([
                'error' => $message
            ], 500);
        }

        return response()->json(['message' => $message], 200);
    }

    /**
     *@OA\POST(
     *     path="/auth/recovery-hash",
     *     tags={"Auth"},
     *     description="Recuperación de contraseña",
     *     @OA\RequestBody(required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="hash", description="",  type="string"),
     *         @OA\Property(property="password", description="", type="string"),
     *         @OA\Property(property="password_confirm", description="", type="string")
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description=""),
     *     @OA\Response(response=422, description="")
     * )
     */
    public function recoveryHash(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'hash' => 'required|string',
            'password' => 'required|string|min:8|max:12',
            'password_confirm' => 'required|string|same:password'
        ], [
            'hash.required' => 'El hash es requerido',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La longitud mínima de la contraseña es de 8 caracteres',
            'password.max' => 'La longitud máxima de la contraseña es de 12 caracteres',
            'password_confirm.required' => 'La contraseña de confirmación es requerida',
            'password_confirm.same' => 'Las contraseñas no son iguales'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('hash_password_verified', $request->hash)->first();

        if ($user == null) {
            return response()->json(['error' => 'El hash no es válido'], 500);
        }

        $user->hash_password_verified = null;
        $user->password = bcrypt($request->password);

        if (!$user->save()) {
            return response()->json(['error' => 'No se ha podido actualizar la contraseña'], 500);
        }

        return response()->json(['message' => 'La contraseña se ha actualizado correctamente'], 200);
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
        $user = auth()->user();
        unset($user->id);
        return response()->json($user);
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
