<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Vincula a tabela-filha legada `locacao_item` a nova cabeca `locacoes`
 * e adiciona `quantidade`.
 *
 * Estrategia (SOLID / sem quebrar o APK):
 *  - `locacao_id` e nullable: linhas antigas e novos itens convivem;
 *  - os endpoints antigos (LocacaoItemController) seguem usando `locacao_item`
 *    sem filtro por `locacao_id` -> respondem no formato antigo;
 *  - os endpoints novos (LocacaoController em /api/v2) usam `locacao_id`.
 *
 * Backfill: para cada linha existente em `locacao_item`, cria uma cabeca em
 * `locacoes` copiando cliente/location/inicio/fim/status/valor e aponta
 * `locacao_item.locacao_id` para ela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locacao_item', function (Blueprint $table) {
            $table->foreignId('locacao_id')
                ->nullable()
                ->after('user_id')
                ->constrained('locacoes')
                ->cascadeOnDelete();

            $table->unsignedInteger('quantidade')->default(1)->after('valor');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('locacao_item', function (Blueprint $table) {
            if (Schema::hasColumn('locacao_item', 'locacao_id')) {
                $table->dropConstrainedForeignId('locacao_id');
            }
            if (Schema::hasColumn('locacao_item', 'quantidade')) {
                $table->dropColumn('quantidade');
            }
        });
    }

    /**
     * Cria 1 cabeca por linha legada e vincula. Idempotente: ignora linhas
     * que ja possuem `locacao_id`.
     */
    private function backfill(): void
    {
        $linhas = DB::table('locacao_item')
            ->whereNull('locacao_id')
            ->orderBy('id')
            ->get();

        foreach ($linhas as $linha) {
            $nomeItem = DB::table('items')->where('id', $linha->item_id)->value('name');

            $locacaoId = DB::table('locacoes')->insertGetId([
                'user_id'    => $linha->user_id,
                'cliente_id' => $linha->cliente_id,
                'nome'       => $nomeItem ?: 'Locação #' . $linha->id,
                'location'   => $linha->location,
                'inicio'     => $linha->inicio,
                'fim'        => $linha->fim,
                'status'     => $linha->status,
                'valor'      => $linha->valor,
                'created_at' => $linha->created_at ?? now(),
                'updated_at' => $linha->updated_at ?? now(),
            ]);

            DB::table('locacao_item')
                ->where('id', $linha->id)
                ->update([
                    'locacao_id' => $locacaoId,
                    'quantidade' => 1,
                ]);
        }
    }
};
