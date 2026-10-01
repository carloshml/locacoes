@extends('layouts.app')

@section('title', 'Arquivos | Locações')

@section('content')
    <div id="app" class="container mx-auto px-4 py-8 max-w-5xl">
        <div class="mb-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Gerenciador de Arquivos</h1>
                <p class="text-gray-600 mt-1">Envie, baixe e remova arquivos do servidor (até 100 MB)</p>
            </div>
            <a href="{{ url('/') }}"
                class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Voltar
            </a>
        </div>

        <files-manager></files-manager>
    </div>
@endsection
