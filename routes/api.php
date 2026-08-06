<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LocacaoItemController;

// Rotas protegidas pelo Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Rotas de Clientes
    Route::prefix('clientes')->group(function () {
        Route::get('/', [ClienteController::class, 'list']);
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
        Route::get('/', [UserController::class, 'list']);
        Route::get('/{id}', [UserController::class, 'getById']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::post('/{id}/avatar', [UserController::class, 'updateAvatar']);
        Route::put('/{id}/profile', [UserController::class, 'updateProfile']);
        Route::get('/{id}/activities', [UserController::class, 'getActivities']);
    });
    
    // Rotas de Itens
    Route::prefix('items')->group(function () {
        Route::get('/', [ItemController::class, 'list']);
        Route::get('/{id}', [ItemController::class, 'getById']);
        Route::post('/', [ItemController::class, 'store']);
        Route::put('/{id}', [ItemController::class, 'update']);
        Route::delete('/{id}', [ItemController::class, 'destroy']);
    });

    // Rotas de Locação de Item
    Route::prefix('locacoes')->group(function () {
        Route::get('/', [LocacaoItemController::class, 'list']);
        Route::get('/faturamento', [LocacaoItemController::class, 'faturamento']);
        Route::get('/{id}', [LocacaoItemController::class, 'getById']);
        Route::post('/', [LocacaoItemController::class, 'store']);
        Route::put('/{id}', [LocacaoItemController::class, 'update']);
        Route::patch('/{id}/status', [LocacaoItemController::class, 'updateStatus']);
        Route::delete('/{id}', [LocacaoItemController::class, 'destroy']);
    });

    // Rotas de Atividades
    Route::get('/activities', [UserController::class, 'getAllActivities']);
});
