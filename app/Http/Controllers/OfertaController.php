<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Oferta;
use App\Models\Demanda;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use App\Mail\OfertaGerenteMailable;
use App\Models\StockVirtual;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class OfertaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        // return $user->id;
        // $ofertas = Oferta::where('FK_proveedor', $user->id)->get();
        // return $ofertas;
        $demandas = Demanda::all();
        return view('oferta.index', compact('ofertas', 'demandas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // return view('oferta.index');

    }
    public function crear($id)
    {
        $user = Auth::user();
        $proveedor = Proveedor::where('correo', $user->email)->first();
        $demanda = Demanda::find($id);
        return view('oferta.create', compact('demanda', 'proveedor'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // return $request;
        $proveedor = Proveedor::find($request->proveedor_id);
        $oferta =  Oferta::create([
            'demanda_id' => $request->demanda_id,
            'proveedor_id' => $request->proveedor_id,
            'fecha_entrega' => $request->fecha_entrega
        ]);
        $demanda = Demanda::find($request->demanda_id);

        // return redirect()->route('oferta.detalle_oferta', $request->demanda_id)->with('success', 'se creo la orden exitosamente, puede agregar  los materiales');
        return view('oferta.detalle_oferta', compact('demanda', 'oferta'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function finalizar_oferta()
    {
        // $user = Auth::user();
        // $user->id = 2;
        // $user =  User::where('email', 'martin@gmail.com')->get();
        // return $user;
        // $oferta = Oferta::where('proveedor_id', $user->id)->get();
        // Mail::to($user->email)->send(new OfertaGerenteMailable($user, $oferta));
        // $user[0]->delete();
        return redirect()->route('demandas.index')->with('success', 'Se realizó correctamente la oferta quede a la espera de la confirmacion por parte de la empresa');
    }

    public function listado_ofertas()
    {
        $user =  User::where('tipo_usuario', 'proveedor')->get();
        $oferta =  Oferta::all();

        // return  $user;
        return view('oferta.listadoGerente', compact('user', 'oferta'));
    }

    public function confirmarOferta($id)
    {

        $ofertaConfirmada = Oferta::find($id);

        $ofertaConfirmada->update(['estado' => "aceptada"]);
        $demanda_id = $ofertaConfirmada->demanda;
        $detalles = $ofertaConfirmada->detalleOferta;
        foreach ($detalles as $key => $detalle) {
            $virtual_stock = StockVirtual::where('material_id', $detalles[0]->material_id)->first();
            $virtual_stock->update(['cantidad' => $virtual_stock->cantidad + $detalle->cantidad]);
        }
        $ofertas = Oferta::where('id', '!=', $ofertaConfirmada->id)->where('demanda_id', $demanda_id)->get();
        foreach ($ofertas as $o) {
            $o->estado = 'cancelada';
            $o->save();
        }
        // cuando confirmes la oferta 
        // se tiene que cambiar el estado de la oferta a "Aceptada"
        // Se tiene cambiar el estado de todas las otras ofertas a "Cancelada"
        // Se tiene que notificar al proveedor
        // return $demanda_id;
        return redirect()->route('demandas.show', $demanda_id)->with('success', 'La oferta Nro: ' . $ofertaConfirmada->id . ' ha sido Aceptada con exito');
    }
}