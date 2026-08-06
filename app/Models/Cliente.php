<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = ['user_id', 'nome', 'idade', 'documento', 'endereco', 'telefone', 'foto'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function locacoes()
    {
        return $this->hasMany(LocacaoItem::class);
    }
}
