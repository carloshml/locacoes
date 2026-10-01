<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Mensagem generica unica para evitar enumeracao de usuarios:
        // nao revelamos se o e-mail existe ou se a senha esta errada.
        $erroCredenciais = 'As credenciais informadas não correspondem aos nossos registros.';

        $user = \App\Models\User::where('email', $request->email)->first();

        // Verifica as credenciais ANTES de revelar qualquer informacao sobre a conta.
        // Se usuario nao existe OU senha incorreta -> mesma mensagem generica.
        if (!$user || !Auth::attempt($credentials, $request->remember)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $erroCredenciais], 401);
            }
            return back()->withErrors(['email' => $erroCredenciais])->onlyInput('email');
        }

        // Credenciais validas. Agora sim podemos checar se a conta esta ativa.
        if (!$user->is_active) {
            Auth::logout();
            $mensagemInativo = 'Sua conta está inativa. Entre em contato com o administrador.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $mensagemInativo], 403);
            }
            return back()->withErrors(['email' => $mensagemInativo])->onlyInput('email');
        }

        // Login confirmado
        $request->session()->regenerate();

        $user = Auth::user();
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => '/dashboard',
                'token' => $token,
                'user' => $user
            ]);
        }

        // Para web, armazenar token na sessão
        session(['api_token' => $token]);

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        // Revogar tokens do usuário (se existirem)
        if (Auth::check()) {
            $user = Auth::user();
            $user->tokens()->delete();
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Limpar token da sessão
        $request->session()->forget('api_token');
        
        // Limpar token do localStorage via JavaScript (opcional)
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        
        return redirect('/login');
    }
}