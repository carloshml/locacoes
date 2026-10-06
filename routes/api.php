<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LocacaoItemController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\AuthApiController;
use App\Http\Controllers\ContratoTemplateController;

// ===== Autenticacao mobile (stateless, sem sessao/CSRF) =====
// Login publico com rate limiting (anti brute-force / bots).
Route::post('/login', [AuthApiController::class, 'login'])->middleware('throttle:5,1');

// Rotas protegidas pelo Sanctum.
// throttle:120,1 = no maximo 120 requisicoes por minuto por usuario autenticado
// (folgado para uso normal da SPA/app, mas corta abuso/scraping/DoS leve).
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    // Autenticacao mobile (precisa de token)
    Route::get('/me', [AuthApiController::class, 'me']);
    Route::post('/logout', [AuthApiController::class, 'logout']);

    // Modelo de contrato do proprio usuario
    Route::prefix('meu-contrato')->group(function () {
        Route::get('/', [ContratoTemplateController::class, 'status']);
        Route::post('/', [ContratoTemplateController::class, 'upload']);
        Route::get('/download', [ContratoTemplateController::class, 'download']);
        Route::delete('/', [ContratoTemplateController::class, 'destroy']);
    });

    // Rotas de Clientes
    Route::prefix('clientes')->group(function () {
        Route::get('/', [ClienteController::class, 'list']);
        Route::get('/paginated', [ClienteController::class, 'paginated']);
        Route::get('/stats', function(Request $request) {
            $clientes = \App\Models\Cliente::where('user_id', $request->user()->id)->get();
            return response()->json([
                'total' => $clientes->count(),
                'media_idade' => $clientes->avg('idade'),
                'documentos_unicos' => $clientes->unique('documento')->count()
            ]);
        });
        Route::get('/{id}', [ClienteController::class, 'getById']);
        Route::post('/', [ClienteController::class, 'store']);
        Route::put('/{id}', [ClienteController::class, 'update']);
        Route::delete('/{id}', [ClienteController::class, 'destroy']);
    });
    
    // Rotas de Usuários
    Route::prefix('usuarios')->group(function () {
        // Operacoes do proprio usuario (dono) ou admin — a checagem de dono
        // e feita no controller (ver updateProfile/updateAvatar/getActivities)
        Route::post('/{id}/avatar', [UserController::class, 'updateAvatar']);
        Route::put('/{id}/profile', [UserController::class, 'updateProfile']);
        Route::get('/{id}/activities', [UserController::class, 'getActivities']);

        // Gestao de usuarios — restrita a admin/manager
        Route::middleware('admin')->group(function () {
            Route::get('/', [UserController::class, 'list']);
            Route::get('/paginated', [UserController::class, 'paginated']);
            Route::get('/{id}', [UserController::class, 'getById']);
            Route::post('/', [UserController::class, 'store']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::patch('/{id}/status', [UserController::class, 'toggleStatus']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
        });
    });
    
    // Rotas de Itens
    Route::prefix('items')->group(function () {
        Route::get('/', [ItemController::class, 'list']);
        Route::get('/paginated', [ItemController::class, 'paginated']);
        Route::get('/{id}', [ItemController::class, 'getById']);
        Route::post('/', [ItemController::class, 'store']);
        Route::put('/{id}', [ItemController::class, 'update']);
        Route::delete('/{id}', [ItemController::class, 'destroy']);
    });

    // Rotas de Locação de Item
    Route::prefix('locacoes')->group(function () {
        Route::get('/', [LocacaoItemController::class, 'list']);
        Route::get('/paginated', [LocacaoItemController::class, 'paginated']);
        Route::get('/faturamento', [LocacaoItemController::class, 'faturamento']);
        Route::get('/{id}', [LocacaoItemController::class, 'getById']);
        Route::get('/{id}/contrato', [LocacaoItemController::class, 'gerarContrato']);
        Route::post('/', [LocacaoItemController::class, 'store']);
        Route::put('/{id}', [LocacaoItemController::class, 'update']);
        Route::patch('/{id}/status', [LocacaoItemController::class, 'updateStatus']);
        Route::delete('/{id}', [LocacaoItemController::class, 'destroy']);
    });

    // Rotas de Atividades (log global) — restrita a admin/manager
    Route::middleware('admin')->get('/activities', [UserController::class, 'getAllActivities']);

    // Gerenciador de arquivos — restrito a admin/manager
    Route::middleware('admin')->prefix('arquivos')->group(function () {
        Route::get('/', [FileController::class, 'index']);
        Route::post('/', [FileController::class, 'store']);
        Route::get('/{id}/download', [FileController::class, 'download']);
        Route::delete('/{id}', [FileController::class, 'destroy']);
    });
});
