<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

// ========== ROTAS PÚBLICAS (sem login) ==========
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// ========== ROTAS PROTEGIDAS (precisa estar logado) ==========
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // Rotas de Clientes
    Route::view('/clientes', 'clientes-list')->name('clientes.index');
    Route::get('/clientes/create', function () {
        return view('clientes-create-update', ['id' => 0]);
    })->name('clientes.create');
    Route::get('/clientes/{id}/edit', function ($id) {
        return view('clientes-create-update', ['id' => $id]);
    })->name('clientes.edit');
    Route::get('/clientes/{id}', function ($id) {
        return view('cliente-read', ['id' => $id]);
    })->name('clientes.show');
    
    // Rotas de Usuários
    Route::view('/usuarios', 'users-list')->name('users.index');
    Route::get('/usuarios/create', function () {
        return view('users-create-update', ['id' => 0]);
    })->name('users.create');
    Route::get('/usuarios/{id}/edit', function ($id) {
        return view('users-create-update', ['id' => $id]);
    })->name('users.edit');
    Route::get('/usuarios/{id}', function ($id) {
        return view('user-read', ['id' => $id]);
    })->name('users.show');
    Route::get('/perfil', function () {
        return view('user-profile', ['id' => auth()->id()]);
    })->name('profile');
    Route::get('/atividades', function () {
        return view('activities');
    })->name('activities');
    
    // Rotas de Itens
    Route::view('/itens', 'itens-list')->name('itens.index');
    Route::get('/itens/create', function () {
        return view('itens-create-update', ['id' => 0]);
    })->name('itens.create');
    Route::get('/itens/{id}/edit', function ($id) {
        return view('itens-create-update', ['id' => $id]);
    })->name('itens.edit');
    Route::get('/itens/{id}', function ($id) {
        return view('item-read', ['id' => $id]);
    })->name('itens.show');

    // Rotas de Locação de Item
    Route::view('/locacoes', 'locacoes-list')->name('locacoes.index');
    Route::get('/locacoes/create', function () {
        return view('locacoes-create-update', ['id' => 0]);
    })->name('locacoes.create');
    Route::get('/locacoes/{id}/edit', function ($id) {
        return view('locacoes-create-update', ['id' => $id]);
    })->name('locacoes.edit');
    Route::get('/locacoes/{id}', function ($id) {
        return view('locacao-read', ['id' => $id]);
    })->name('locacoes.show');

    // Faturamento
    Route::view('/faturamento', 'faturamento')->name('faturamento');
    
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
});
