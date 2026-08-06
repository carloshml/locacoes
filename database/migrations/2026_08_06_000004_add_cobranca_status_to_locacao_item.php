<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: alterar o enum para incluir 'cobranca'
        DB::statement("ALTER TABLE locacao_item MODIFY COLUMN status ENUM('ativo', 'cobranca', 'finalizado', 'cancelado') DEFAULT 'ativo'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE locacao_item MODIFY COLUMN status ENUM('ativo', 'finalizado', 'cancelado') DEFAULT 'ativo'");
    }
};
