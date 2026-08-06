<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['name', 'valor', 'descricao'];

    public function locacoes()
    {
        return $this->hasMany(LocacaoItem::class);
    }

    public function locacaoAtiva()
    {
        return $this->hasOne(LocacaoItem::class)
            ->where('status', 'ativo')
            ->where('inicio', '<=', now())
            ->where('fim', '>=', now());
    }
}
