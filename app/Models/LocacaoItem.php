<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocacaoItem extends Model
{
    protected $table = 'locacao_item';

    protected $fillable = ['item_id', 'cliente_id', 'location', 'valor', 'inicio', 'fim', 'status'];

    protected $casts = [
        'inicio' => 'datetime',
        'fim' => 'datetime',
        'valor' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
