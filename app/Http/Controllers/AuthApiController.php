<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * API de autenticacao para clientes mobile (app Android).
 *
 * Diferente do LoginController (web, baseado em sessao/CSRF), aqui tudo e
 * stateless: recebe email/senha em JSON e devolve um token Sanctum que o
 * app guarda e envia no header Authorization: Bearer <token>.
 */
class AuthApiController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Mensagem generica unica (evita enumeracao de usuarios).
        $erroCredenciais = 'As credenciais informadas não correspondem aos nossos registros.';

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => $erroCredenciais], 401);
        }

        // Conta precisa estar ativa (mesma regra do login web).
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Sua conta está inativa. Entre em contato com o administrador.',
            ], 403);
        }

        $user->update(['last_login_at' => now()]);

        // Token identificado por dispositivo (facilita revogar depois).
        $deviceName = $request->input('device_name', 'android-app');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    /** Retorna os dados do usuario autenticado pelo token. */
    public function me(Request $request)
    {
        return response()->json($this->userPayload($request->user()));
    }

    /**
     * Campos do usuario devolvidos ao app mobile.
     *
     * Inclui avatar (data URI base64), telefone, cargo e datas para a tela de
     * perfil do app — os mesmos dados que o card "Meu Perfil" do dashboard usa.
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar' => $user->avatar,
            'phone' => $user->phone,
            'position' => $user->position,
            'created_at' => $user->created_at?->toIso8601String(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ];
    }

    /** Revoga o token atual (logout do dispositivo). */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }
}
