@extends('layouts.app')

@section('title', 'Faturamento de Locações')

@section('content')
    <div id="app" class="container mx-auto px-4 py-8 max-w-7xl">
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Faturamento de Locações</h1>
                    <p class="text-gray-600 mt-1">Acompanhe os valores recebidos mensalmente</p>
                </div>
                <div class="flex gap-4">
                    <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700 transition-all shadow-lg">Voltar</a>
                </div>
            </div>
        </div>
        <faturamento-locacoes></faturamento-locacoes>
    </div>
@endsection
