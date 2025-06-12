<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\User;
use App\Mail\VerifiedMail;
use Illuminate\Http\Request;
use App\Mail\ForgotPasswordMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Google_Client;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', [
            'except' => [
                'login', 'register', 'login_ecommerce', 'googleLogin',
                'verified_auth', 'verified_email', 'verified_code', 'new_password',
            ]
        ]);
    }

    public function register()
    {
        $data = request()->only(['name', 'email', 'password']);

        $validator = Validator::make($data, [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => bcrypt($data['password']),
            'type_user' => 1,
            'uniqd'     => uniqid(),
        ]);

        $user->roles()->sync([2]); // Rol Usuario

        // Mail::to($data['email'])->send(new VerifiedMail($user));

        return response()->json($user, 201);
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'type_user' => 1
        ])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function login_ecommerce()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'type_user' => 2
        ])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth('api')->user();
        if (! $user->email_verified_at) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function googleLogin(Request $request)
    {
        $validator = Validator::make($request->only('id_token'), [
            'id_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'id_token es requerido'], 422);
        }

        try {
            $client = new Google_Client();
            $client->setClientId(config('services.google.client_id'));
            $payload = $client->verifyIdToken($request->id_token);

            if (! $payload) {
                return response()->json(['error' => 'Token de Google inválido'], 401);
            }

            $email     = $payload['email'];
            $googleId  = $payload['sub'];
            $firstName = $payload['given_name'] ?? ($payload['name'] ?? '');
            $lastName  = $payload['family_name'] ?? '';
            $avatar    = $payload['picture'] ?? null;

            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleId,
                    'avatar'    => $avatar ?: $user->avatar,
                    'email_verified_at' => $user->email_verified_at ?: now(),
                ]);
            } else {
                $user = User::create([
                    'name'              => $firstName,
                    'surname'           => $lastName,
                    'email'             => $email,
                    'google_id'         => $googleId,
                    'avatar'            => $avatar,
                    'type_user'         => 1,
                    'password'          => bcrypt(Str::random(16)),
                    'email_verified_at' => now(),
                    'uniqd'             => uniqid(),
                ]);
                $user->roles()->sync([2]);
            }

            $token = auth('api')->login($user);
            return $this->respondWithToken($token);

        } catch (\Exception $e) {
            \Log::error('Error en googleLogin: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno al autenticar con Google'], 500);
        }
    }

    public function me()
    {
        $user = auth('api')->user();

        return response()->json([
            'name'         => $user->name,
            'surname'      => $user->surname,
            'phone'        => $user->phone,
            'email'        => $user->email,
            'bio'          => $user->bio,
            'fb'           => $user->fb,
            'sexo'         => $user->sexo,
            'address_city' => $user->address_city,
            'avatar'       => $user->avatar
                ? env('APP_URL') . '/storage/' . $user->avatar
                : 'https://cdn-icons-png.flaticon.com/512/1476/1476614.png',
        ]);
    }

    public function permissions()
    {
        if (! auth('api')->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $user = User::with('roles.permissions')->find(auth('api')->id());

        $allPermissions = [
            'manage-users'        => false,
            'manage-products'     => false,
            'manage-own-products' => false,
        ];

        $roles = [];

        foreach ($user->roles as $role) {
            $roles[] = $role->name;

            if ($role->id == 1) {
                $allPermissions = array_map(fn() => true, $allPermissions);
            } elseif ($role->id == 2) {
                $allPermissions['manage-own-products'] = true;
            }
        }

        if (empty($roles) && $user->type_user == 1) {
            $roles[] = 'Usuario';
            $allPermissions['manage-own-products'] = true;
        }

        return response()->json([
            'permissions' => $allPermissions,
            'roles'       => $roles,
        ]);
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    protected function respondWithToken($token)
    {
        $user = auth('api')->user();

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'user'         => [
                'full_name' => $user->name . ' ' . $user->surname,
                'email'     => $user->email,
            ],
        ]);
    }

    public function loginJson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $credentials = $request->only(['email', 'password']);

        if (! $token = auth('api')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'type_user' => 1,
        ])) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'success'     => true,
            'token'       => $token,
            'token_type'  => 'bearer',
            'expires_in'  => auth('api')->factory()->getTTL() * 60,
            'user'        => auth('api')->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth('api')->user();

        if ($request->password) {
            $user->update(['password' => bcrypt($request->password)]);
            return response()->json(["message" => 200]);
        }

        $exists = User::where("id", "<>", $user->id)
                      ->where("email", $request->email)->first();

        if ($exists) {
            return response()->json([
                "message" => 403,
                "message_text" => "El usuario ya existe"
            ]);
        }

        if ($request->hasFile("file_imagen")) {
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }

            $path = Storage::putFile("users", $request->file("file_imagen"));
            $request->merge(["avatar" => $path]);
        }

        $user->update($request->except(['password', 'file_imagen']));

        return response()->json(["message" => 200]);
    }

    public function verified_email(Request $request)
    {
        $user = User::where("email", $request->email)->first();

        if ($user) {
            $user->update(["code_verified" => uniqid()]);
            Mail::to($request->email)->send(new ForgotPasswordMail($user));
            return response()->json(["message" => 200]);
        }

        return response()->json(["message" => 403]);
    }

    public function verified_code(Request $request)
    {
        $user = User::where("code_verified", $request->code)->first();
        return response()->json(["message" => $user ? 200 : 403]);
    }

    public function new_password(Request $request)
    {
        $user = User::where("code_verified", $request->code)->first();

        if ($user) {
            $user->update([
                "password" => bcrypt($request->new_password),
                "code_verified" => null
            ]);
            return response()->json(["message" => 200]);
        }

        return response()->json(["message" => 403]);
    }

    public function verified_auth(Request $request)
    {
        $user = User::where("uniqd", $request->code_user)->first();

        if ($user) {
            $user->update(["email_verified_at" => now()]);
            return response()->json(["message" => 200]);
        }

        return response()->json(["message" => 403]);
    }
}
