<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
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
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
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
            return response()->json(['error' => 'Email not verified'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Set credentials user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|unique:users',
            'phone' => 'required|string',
            'name' => 'required|string',
            'password' => 'required|string|min:8|max:12',
            'password_confirm' => 'required|string|same:password'
        ], [
            'email.required' => 'El email es requerido',
            'email.unique' => 'El email no introducido ya ha sido registrado',
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
        $user->email_verified_at = Carbon::now();
        //$user->hash_email_verified = Str::uuid();
        $user->role_id = Role::where('name', 'User')->first()->id;

        //TODO: Enivar el correo de confirmación

        if (!$user->save()) {
            return response()->json(['message' => 'El usuario no se ha podido registrar correctamente'], 500);
        }

        return response()->json(['message' => 'Registrado correctamente'], 200);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
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
