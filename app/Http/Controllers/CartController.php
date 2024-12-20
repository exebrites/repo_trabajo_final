<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Pedido;
use App\Models\Producto;
use Darryldecode\Cart\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\ContactoController;
use App\Models\CostoDisenio;
use Illuminate\Validation\ValidationException;


class CartController extends Controller
{
    public function shop()
    {
        $products = Producto::orderBy('visitas', 'desc')->paginate(8);
        return view('shop', compact('products'));
    }
    public function cart()
    {
        $cartCollection = \Cart::getContent();
        //dd($cartCollection);

        $costo = CostoDisenio::costo_total_disenio();
        return view('cart')->with(['cartCollection' => $cartCollection, 'costo'  => $costo]);
    }
    public function remove(Request $request)
    {
        \Cart::remove($request->id);
        return redirect()->route('cart.index')->with('success_msg', 'Producto removido!');
    }
    public function add(Request $request)
    {
        // return $request;
        // Aplica reglas de validación
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|mimes:jpeg,png,jpg|dimensions:max=2048', // Ajusta las extensiones y el tamaño máximo según tus necesidades
        ]);

        // Verifica si las reglas de validación han sido cumplidas
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Sube el archivo recibido en la solicitud con el nombre 'file' al directorio 'public' del sistema de archivos de Laravel.
        $imagen = $request->file('file')->store('public');

        // Obtiene la URL pública del archivo recién almacenado utilizando el servicio Storage de Laravel.
        $url_imagen = Storage::url($imagen);
        //agrega el producto y su diseño al carrito

        // dd("disenio asistido");
        /**
         Si pasa por aca se que es un diseño asistido por ende se puede realizar el calculo del costo de diseño y luego asignar el valor del costo 
         al atribito
         */
        $disenio_estado = true;
        $costo_disenio_asistido = CostoDisenio::costo_disenio($request->price, $request->quantity, $disenio_estado);
        // dd($costo_disenio_asistido);
        \Cart::add(array(
            'id' => $request->id,
            'name' => $request->name,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'attributes' => array(
                'imagen_path' => $request->img,
                'slug' => $request->slug,
                'url_disenio' => $url_imagen,
                'disenio_estado' => $disenio_estado, // $request->disenio_estado
                'costo_disenio' => $costo_disenio_asistido
            )
        ));

        return redirect()->route('cart.index')->with('success_msg', 'Producto agregado a su Carrito!');

        // return     $url_disenio;
    }

    public function add_boceto(Request $request)
    {
        // return $request;
        // Sube el archivo recibido en la solicitud con el nombre 'file' al directorio 'public' del sistema de archivos de Laravel.

        try {
            $request->validate([
                'logo' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Puedes ajustar los tipos de archivos y el tamaño máximo
                'img' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                'nombre' => 'required|string|max:255',
                'objetivo' => 'required|string',
                'publico' => 'required|string',
                'contenido' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            // Manejar los errores de validación aquí
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        if ($request->logo) {
            $logo = $request->file('logo')->store('public');
            $url_logo = Storage::url($logo);
        } else {
            $url_logo = "";
        }
        if ($request->img) {
            $img = $request->file('img')->store('public');
            $url_img = Storage::url($img);
        } else {
            $url_img = "";
        }
        //agrega el producto y su diseño al carrito
        // dd("disenio completo");
        $disenio_estado = false;
        $costo_disenio_completo = CostoDisenio::costo_disenio($request->price, $request->quantity, $disenio_estado);

        \Cart::add(array(
            'id' => $request->id,
            'name' => $request->name,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'attributes' => array(
                'imagen_path' => $request->img_path,
                "nombre" => $request->nombre,
                "objetivo" => $request->objetivo,
                "publico" => $request->publico,
                "contenido" => $request->contenido,
                "logo" => $url_logo,
                "img" => $url_img,
                "disenio_estado" => $disenio_estado, // $request->disenio_estado
                'costo_disenio' => $costo_disenio_completo
            )
        ));

        return redirect()->route('cart.index')->with('success_msg', 'Producto agregado a su Carrito!');
    }

    public function update(Request $request)
    {

        // dd("");
        \Cart::update(
            $request->id,
            array(
                'quantity' => array(
                    'relative' => false,
                    'value' => $request->quantity
                ),
            )
        );
        $p = \Cart::get($request->id);
        $precio  = $p->price;
        $cantidad = $p->quantity;
        $disenio_estado = $p->attributes['disenio_estado'];
        $imagen_path =  $p->attributes['imagen_path'];

        // dump($p);
        // dd("");

        if ($p->attributes['disenio_estado']) {

            $url_disenio = $p->attributes['url_disenio'];

            $costo = CostoDisenio::costo_disenio($precio, $cantidad, $disenio_estado);
            \Cart::update(
                $request->id,
                array(
                    'attributes' => array(
                        "imagen_path" => $imagen_path,
                        "slug" => "Carpetas de Presentación",
                        "url_disenio" => $url_disenio,
                        "disenio_estado" => true,
                        "costo_disenio" => $costo

                    )
                )
            );
        } else {

            $costo = CostoDisenio::costo_disenio($precio, $cantidad, $disenio_estado);

            $nombre = $p->attributes['nombre'];
            $objetivo =  $p->attributes['objetivo'];
            $publico = $p->attributes['publico'];
            $contenido = $p->attributes['contenido'];
            $logo   = $p->attributes['logo'];
            $img = $p->attributes['img'];
            \Cart::update(
                $request->id,
                array(
                    'attributes' => array(
                        "imagen_path" => $imagen_path,
                        "nombre" => $nombre,
                        "objetivo" => $objetivo,
                        "publico" => $publico,
                        "contenido" => $contenido,
                        "logo" => $logo,
                        "img" => $img,
                        "disenio_estado" => false,
                        "costo_disenio" => $costo

                    )
                )
            );
        }

        return redirect()->route('cart.index')->with('success_msg', 'Carrito actualizado!');
    }

    public function clear()
    {
        \Cart::clear();
        return redirect()->route('cart.index')->with('success_msg', 'Carrito borrado!');
    }
}
