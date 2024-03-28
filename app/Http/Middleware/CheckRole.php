<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $role)
    {
        // Verificar si el usuario tiene el rol necesario
        if (!$request->user() || !$request->user()->hasRole($role)) {
            abort(403, 'Acceso no autorizado');
        }
        if ($request->user()->hasRole('proveedor')) {
            return $next($request);
        }

        return $next($request);
    }
}
