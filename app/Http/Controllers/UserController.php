<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cooperativa;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', User::class);
        
        // Esta consulta es correcta. No necesita cambios.
        return Inertia::render('Users/Index', [
            'users' => User::with('cooperativas')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', [
            'allCooperativas' => Cooperativa::all(['id', 'nombre']),
            'personas' => Persona::all(['id', 'nombre_completo', 'numero_documento']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'tipo_usuario' => 'required|in:admin,gestor,abogado,cliente',
            'cooperativas' => 'nullable|array',
            'persona_id' => 'nullable|exists:personas,id'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tipo_usuario' => $request->tipo_usuario,
            'persona_id' => $request->persona_id,
        ]);

        // ===== ¡CORRECCIÓN CLAVE AQUÍ! =====
        // Ahora permitimos asignar cooperativas a gestores, abogados Y clientes.
        if ($request->has('cooperativas') && in_array($user->tipo_usuario, ['abogado', 'gestor', 'cliente'])) {
            $user->cooperativas()->sync($request->cooperativas);
        }

        return to_route('admin.users.index')->with('success', '¡Usuario creado exitosamente!');
    }
    
    public function show(User $user): RedirectResponse
    {
        $this->authorize('view', $user);
        return to_route('admin.users.edit', $user->id);
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);
        
        return Inertia::render('Users/Edit', [
            'user' => $user->load('cooperativas'),
            'allCooperativas' => Cooperativa::all(['id', 'nombre']),
            'personas' => Persona::all(['id', 'nombre_completo', 'numero_documento']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'tipo_usuario' => 'required|in:admin,gestor,abogado,cliente',
            'password' => 'nullable|string|min:8|confirmed',
            'estado_activo' => 'required|boolean',
            'preferencias_notificacion.email' => 'required|boolean',
            'preferencias_notificacion.in-app' => 'required|boolean',
            'cooperativas' => 'nullable|array',
            'cooperativas.*' => 'exists:cooperativas,id',
            'persona_id' => 'nullable|exists:personas,id'
        ]);
        
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->tipo_usuario = $validated['tipo_usuario'];
        $user->estado_activo = $validated['estado_activo'];
        $user->persona_id = $validated['persona_id'] ?? null;
        $user->preferencias_notificacion = $validated['preferencias_notificacion'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        
        $user->save();
        
        // ===== ¡CORRECCIÓN CLAVE AQUÍ! =====
        // Aplicamos la misma lógica al actualizar.
        if (in_array($user->tipo_usuario, ['abogado', 'gestor', 'cliente'])) {
             $user->cooperativas()->sync($request->input('cooperativas', []));
        } else {
             $user->cooperativas()->sync([]);
        }
        
        return to_route('admin.users.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        $user->delete();
        return to_route('admin.users.index')->with('success', 'Usuario eliminado exitosamente.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($user->id === auth()->id()) {
            return to_route('admin.users.index')->with('error', 'No puede suspender su propia cuenta.');
        }

        $user->estado_activo = !$user->estado_activo;
        $user->save();

        $message = $user->estado_activo ? 'Usuario reactivado exitosamente.' : 'Usuario suspendido exitosamente.';

        return to_route('admin.users.index')->with('success', $message);
    }
}