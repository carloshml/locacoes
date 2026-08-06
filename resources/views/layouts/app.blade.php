<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Locações')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->check() ? auth()->id() : '' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <nav class="bg-gray-800 shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-8">
                    <div class="text-white font-bold text-xl">
                        <a href="{{ route('dashboard') }}" class="hover:text-green-400 transition">
                            Locações
                        </a>
                    </div>

                    @auth
                        <div class="hidden md:flex space-x-4">
                            <a href="{{ route('dashboard') }}"
                                class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition">
                                Dashboard
                            </a>

                            <!-- MENU DE CLIENTES - Agrupado em dropdown -->
                            <div class="relative group">
                                <button
                                    class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition flex items-center gap-1">
                                    Clientes
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div
                                    class="absolute left-0 pt-2 w-48 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                    <div class="bg-white rounded-md shadow-lg">
                                        <a href="{{ route('clientes.index') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                                    </path>
                                                </svg>
                                                Lista de Clientes
                                            </div>
                                        </a>
                                        <a href="{{ route('clientes.create') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                                                    </path>
                                                </svg>
                                                Novo Cliente
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- MENU DE USUÁRIOS - visível apenas para admin/manager (CORRIGIDO) -->
                            @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                                <div class="relative group">
                                    <button
                                        class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition flex items-center gap-1">
                                        Administração
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    <div
                                        class="absolute left-0 pt-2 w-48 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                        <div class="bg-white rounded-md shadow-lg">
                                            <a href="{{ route('users.index') }}"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                                        </path>
                                                    </svg>
                                                    Gerenciar Usuários
                                                </div>
                                            </a>
                                            <a href="{{ route('users.create') }}"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                                                        </path>
                                                    </svg>
                                                    Novo Usuário
                                                </div>
                                            </a>
                                            <a href="{{ route('activities') }}"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                        </path>
                                                    </svg>
                                                    Log de Atividades
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <a href="{{ route('profile') }}"
                                class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition">
                                Meu Perfil
                            </a>
                            <a href="{{ route('faturamento') }}"
                                class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition">
                                Faturamento
                            </a>
                        </div>
                    @endauth
                </div>

                <div>
                    @auth
                        <div class="flex items-center gap-4">
                            <div class="hidden md:block">
                                <span class="text-gray-300 text-sm">
                                    Olá, <span class="font-semibold text-white">{{ auth()->user()->name }}</span>
                                </span>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit"
                                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                        </path>
                                    </svg>
                                    Sair
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200">
                            Entrar
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="py-8">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>