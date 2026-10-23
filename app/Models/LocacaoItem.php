<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocacaoItem extends Model
{
    protected $table = 'locacao_item';

    protected $fillable = [
        'user_id', 'locacao_id', 'item_id', 'cliente_id',
        'location', 'valor', 'quantidade', 'inicio', 'fim', 'status',
    ];

    protected $casts = [
        'inicio'     => 'datetime',
        'fim'        => 'datetime',
        'valor'      => 'decimal:2',
        'quantidade' => 'integer',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cabeca da locacao (novo modelo multi-item). Pode ser null em linhas
     * legadas que ainda nao foram vinculadas.
     */
    public function locacao()
    {
        return $this->belongsTo(Locacao::class, 'locacao_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}

