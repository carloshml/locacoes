<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cabeca da locacao: dados do contrato (cliente, local, periodo, status, valor).
 * Os bens alugados ficam em `locacao_item`, ligados por `locacao_id`.
 */
class Locacao extends Model
{
    protected $table = 'locacoes';

    protected $fillable = [
        'user_id', 'cliente_id', 'nome', 'location',
        'inicio', 'fim', 'status', 'valor',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fim'    => 'datetime',
        'valor'  => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Itens (bens) que compoem a locacao. Sempre 1..N.
     */
    public function itens()
    {
        return $this->hasMany(LocacaoItem::class, 'locacao_id');
    }
}
