<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Cria a tabela-cabeca `locacoes` (uma locacao = um cabecalho + 1..N itens).
 *
 * Nao altera nem remove nada da tabela legada `locacao_item`, que continua
 * servindo os endpoints antigos (formato antigo) sem quebrar o APK instalado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('nome')->nullable();
            $table->string('location');
            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->enum('status', ['ativo', 'cobranca', 'finalizado', 'cancelado'])->default('ativo');
            $table->decimal('valor', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'inicio']);
        });

        // Backfill: cada linha legada de locacao_item vira 1 locacao (cabeca)
        // e a propria linha e vinculada a ela via `locacao_id`.
        // A coluna `locacao_id` e criada na migration seguinte.
    }

    public function down(): void
    {
        Schema::dropIfExists('locacoes');
    }
};
