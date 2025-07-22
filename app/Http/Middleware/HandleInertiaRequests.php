<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string {
        return parent::version($request);
    }

    public function share(Request $request): array {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? $user->only('id', 'name', 'email', 'tipo_usuario') : null,
                // --- INICIO: LÓGICA DEL CONTADOR CORREGIDA ---
                // Ahora solo cuenta notificaciones no leídas Y cuya fecha de envío es hoy o anterior.
                'unreadNotifications' => $user 
                    ? $user->notificaciones()->where('leido', false)->where('fecha_envio', '<=', now())->count() 
                    : 0,
                // --- FIN: LÓGICA DEL CONTADOR CORREGIDA ---
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
