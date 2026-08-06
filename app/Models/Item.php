<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['user_id', 'name', 'valor', 'descricao'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

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
