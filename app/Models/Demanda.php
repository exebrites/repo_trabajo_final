<?php

namespace App\Models;

use App\Http\Controllers\RegDemandaProveedor;
use App\Models\DetalleDemanda;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OwenIt\Auditing\Contracts\Auditable;
use App\Models\RegistroPedidoDemanda;

class Demanda extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable;
    protected $fillable = ['estado', 'fecha_cierre'];
    protected $table = "demandas";

    public static function combinar($lista, $materiales_compra)
    {
        // dd([$lista, $materiales_compra]);
        // Crear un nuevo array para almacenar la combinación de ambos
        $resultado = [];
        // Recorrer el array $orden
        // dd($resultado);

        // Buscar el mismo material en el array $compra
        // Buscar el mismo material en el array $compra
        // Para hacer esto, creamos un nuevo array que tenga solo los valores de 'id'
        // de cada elemento del array $compra

        $arrays_de_ids = array_column($materiales_compra, 'id');

        foreach ($lista as $elemento_lista) {
            $materiales_id = $elemento_lista['id'];
            $cantidad_orden = $elemento_lista['cantidad'];


            // Luego, buscamos el valor de $materiales_id en ese nuevo array
            // y guardamos la clave en la que se encuentra en $clave_compra
            $clave_compra = array_search($materiales_id, $arrays_de_ids);
            // dd($arrays_de_ids);
            // dd($clave_compra);

            if ($clave_compra !== false) {

                // Si se encuentra, agregar las cantidades
                $cantidad_compra = $materiales_compra[$clave_compra]['cantidad'];
                // dd($cantidad_compra);
                $resultado[] = ['id' => $materiales_id, 'cantidad' => $cantidad_orden + $cantidad_compra];
            }
        }

        // dd($resultado);
        // Agregar los elementos de $compra que no estén en $orden

        foreach ($materiales_compra as $elemento_compra) {
            $materiales_id_compra = $elemento_compra['id'];

            // Verificar si el material ya está en $resultado
            $clave_resultado = array_search($materiales_id_compra, array_column($resultado, 'id'));

            if ($clave_resultado === false) {
                // Si no está, agregar el elemento de $compra tal cual está
                $resultado[] = $elemento_compra;
            }
        }

        // Imprimir el resultado
        return $resultado;
    }
    public function detalleDemandas()
    {
        return $this->hasMany(DetalleDemanda::class, 'demandas_id', 'id');
    }

    public function registroDemandaProveedor()
    {
        return $this->hasOne(registroPedidoDemanda::class, 'demanda_id', '');
    }
    public function demandaProveedor()
    {
        return $this->hasMany(DemandaProveedor::class, 'demanda_id', '');
    }
    public function oferta()
    {
        return $this->hasMany(Oferta::class, 'demanda_id', '');
    }
    public function demandaPedido()
    {
        return $this->hasMany(RegistroPedidoDemanda::class, 'demanda_id', '');
    }
    public function fechaCierre()
    {

        // Convertir la fecha de cierre a un objeto Carbon
        $fechaCierre = Carbon::parse($this->fecha_cierre);

        // Obtener la fecha actual
        $hoy = Carbon::now();
        $bandera = false;
        // Comparar las fechas
        if ($fechaCierre->isBefore($hoy)) {
            // La fecha de cierre es menor que la fecha actual
            $bandera = false;
        } elseif ($fechaCierre->isAfter($hoy)) {
            // La fecha de cierre es mayor que la fecha actual
            $bandera = true;
        } else {
            // La fecha de cierre es igual a la fecha actual
            $bandera = true;
        }

        return $bandera;
    }
    use HasFactory;
}
