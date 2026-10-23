@extends('layouts.app')

@section('title', 'Locação | Sistema de Locação')

@section('content')
    <div id="app" class="container mx-auto px-4 py-8 max-w-7xl">
        <div class="mb-8">
            <a href="{{ url('/locacoes') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700 transition">Voltar</a>
        </div>
        <locacoes-create :id="{{ $id }}"></locacoes-create>
    </div>
@endsection

