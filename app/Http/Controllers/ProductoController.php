<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\DetalleProducto;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use SebastianBergmann\Environment\Runtime;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        // $pedidos = Pedido::paginate(10)
        // $productos = Producto::paginate();
        // $productos= Producto::all();
        $productos = Producto::orderBy('id', 'desc')->get();
        // dd($productos);
        return view('producto.index', compact('productos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categorias = Categoria::all();
        return view('producto.create', compact('categorias'));
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
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'price' => 'required|numeric|min:0|max:100000',
                'alias' => ['required', 'string', 'max:255', Rule::unique('productos', 'alias')],
                // 'description' => ['required', 'string'],
                'description' => ['string', 'max:255'],
                'file' => ['required', 'file', 'mimes:jpeg,png', 'max:2048'],
                'categoria_id' => ['required', 'exists:categorias,id'],
            ]);
        } catch (ValidationException $e) {
            // Manejar los errores de validación aquí
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $imagen =  $request->file('file')->store('public');
        $url = Storage::url($imagen);

        $producto = Producto::create(
            [
                'name' => Str::upper($request->name),
                'price' => $request->price,
                'slug' => $request->name,
                'description' => $request->description,
                'category_id' => $request->categoria_id,
                'image_path' => $url,
                'alias' => $request->alias,
                'visitas' => 0
            ]
        );

        return redirect()->route('productos.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $producto = Producto::find($id);
        return view('producto.show', compact('producto'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $producto = Producto::find($id);
        $categorias = Categoria::all();

        return view('producto.edit', compact('producto', 'categorias'));
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
        // return $request;
        if ($request->file('file') == null) {
            // sino $url toma el valor que tenia imagen_path cuando no se actualiza la foto
            $p = Producto::find($request->id);
            $url = $p->image_path;
        } else {
            // si actualiza debe pasar ...
            $imagen =  $request->file('file')->store('public');
            $url = Storage::url($imagen);
        }
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'price' => 'required|numeric|min:0|max:100000',
                'alias' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('productos', 'alias')->ignore($request->id)
                ],
                // 'description' => ['required', 'string'],
                'description' => ['string', 'max:255'],
                'file' => ['file', 'mimes:jpeg,png', 'max:2048'],
                'categoria_id' => ['required', 'exists:categorias,id']

            ]);
        } catch (ValidationException $e) {
            // dd($e);
            // Manejar los errores de validación aquí
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        Producto::find($request->id)->update([
            'name' => $request->name,
            'price' => $request->price,
            'slug' => $request->name,
            'description' => $request->description,
            'category_id' => $request->categoria_id,
            'image_path' => $url,
            'alias' => $request->alias
        ]);
        return redirect()->route('productos.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        // return $id;
        try {
            $producto = Producto::find($id);
            $detalles  = $producto->detalleProducto;
            // dd($detalles);
            foreach ($detalles as $key => $detalle) {
                # code...

                $detalle->delete();
            }
            $producto->delete();
            return redirect()->back()->with('success', 'Producto eliminado con éxito!');
        } catch (\Illuminate\Database\QueryException $e) {
            // Manejar la excepción y proporcionar un mensaje descriptivo
            return redirect()->back()->with('error', 'El producto ' . $id . ' está relacionado a uno o varios pedidos.');
        }
    }

    //idea manejar con otra funcion la parte del detalle de productos
    public function detalle($id)
    {

        $pro = Producto::find($id);
        // Incrementa el valor del campo 'cantidad' en 1
        $pro->increment('visitas');

        // Guarda los cambios en la base de datos
        $pro->save();

        $url_imagen = '';
        return view('detalleProducto', compact('pro', 'url_imagen'));
    }

    public function buscarProducto()
    {
        $buscar = request()->get('buscar', '');

        $producto = Producto::all();

        if (request()->has('buscar')) {
            $busqueda = Producto::where('name', 'like', '%' . $buscar . '%')->take(5)->get();
        }
        return view('producto.busqueda', compact('busqueda'));
    }
}
