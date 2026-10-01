<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que o usuario autenticado possui papel administrativo
 * (admin ou manager) antes de acessar rotas de gestao de usuarios.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isManager()) {
            return response()->json([
                'message' => 'Acesso negado. Permissao insuficiente.'
            ], 403);
        }

        return $next($request);
    }
}
