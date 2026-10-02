<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = validator($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // O primeiro usuario do sistema vira admin ativo (senao ninguem
        // conseguiria administrar). Os demais sao criados INATIVOS e precisam
        // ser ativados por um administrador antes de acessar o sistema.
        $isFirstUser = User::count() === 0;
        $role = $isFirstUser ? 'admin' : 'user';
        $isActive = $isFirstUser;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'is_active' => $isActive,
        ]);

        // Criar perfil
        UserProfile::create([
            'user_id' => $user->id,
            'preferences' => ['theme' => 'light', 'notifications' => true]
        ]);

        // Usuario inativo: NAO faz login, apenas informa que aguarda ativacao.
        if (!$isActive) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'pending_activation' => true,
                    'message' => 'Cadastro realizado! Sua conta aguarda ativação por um administrador.',
                ], 201);
            }
            return redirect('/login')->with('status', 'Cadastro realizado! Aguarde a ativação por um administrador.');
        }

        // Primeiro usuario (admin ativo): login automatico normal.
        Auth::login($user);
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;
        session(['api_token' => $token]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => '/dashboard',
                'user' => $user,
                'token' => $token
            ]);
        }

        return redirect('/dashboard');
    }
}