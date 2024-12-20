<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entrega extends Model
{
    protected $table = 'entregas';
    protected $fillable = ['pedido_id', 'direccion', 'telefono', 'recepcion', 'nota', 'local'];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id', '');
    }
    use HasFactory;
}
