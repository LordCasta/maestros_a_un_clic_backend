---
name: nuevo-modulo-api
description: Implementa uno o varios endpoints nuevos de la API (un módulo o parte de una historia de usuario) con la misma estructura que el resto del backend - rutas, FormRequest, Policy, Service, Resource, tests y documentación del contrato. Úsalo al empezar el backend de una HU.
---

# Implementar endpoints nuevos

Sigue los pasos en orden. Los ejemplos reales a copiar son `FavoriteController` (lectura simple) y `BookingController` + `BookingService` (reglas y estados).

## 0. Antes de escribir código

1. Lee el issue de la HU y sus criterios de aceptación.
2. Busca los endpoints en `docs/api/endpoints.md` (Parte 2, "Planeados"): ruta, método, acceso y HU. **Respeta la ruta acordada.** Si hace falta cambiarla, dilo en el issue antes.
3. Revisa en `docs/modelo-de-datos.md` las tablas que vas a usar. Las tablas de todos los módulos ya existen.

## 1. Esquema (solo si hace falta)

Si falta una columna o tabla: **migración nueva** (`php artisan make:migration`), nunca editar una existente. Actualiza `docs/modelo-de-datos.md` en el mismo PR. Estados nuevos → caso nuevo en el enum de `app/Enums`.

## 2. Ruta (`routes/api.php`)

Dentro del grupo correcto y con el rol si aplica:

```php
Route::middleware(['auth:sanctum', 'not_blocked'])->group(function () {
    Route::middleware('role:professional')->group(function () {
        Route::get('professional/services', [ProfessionalServiceController::class, 'index']);
        Route::post('professional/services', [ProfessionalServiceController::class, 'store']);
    });
});
```

## 3. FormRequest (`app/Http/Requests`) — solo valida

```php
class StoreProfessionalServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // los permisos van en la ruta (role:) y en la Policy
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'price_type' => ['required', Rule::enum(PriceType::class)],
            'price' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
```

Campos nuevos → nombre legible en `lang/es/validation.php` → `attributes`.

## 4. Policy (`app/Policies`) — reglas sobre un recurso concreto

```php
public function update(User $user, ProfessionalService $service): bool
{
    return $service->professionalProfile->user_id === $user->id;
}
```

Usa `Response::deny('mensaje en español')` cuando el mensaje ayude al usuario. Regístrala en `AuthServiceProvider` si el nombre no sigue la convención.

## 5. Service (`app/Services`) — solo si hay reglas, transacciones, estados o archivos

Lanza `ValidationException::withMessages([...])` para errores de dato y `abort(409, '…')` para conflictos de estado. Archivos con `UploadService`. Cambios de estado de reservas con `BookingService::transition()`. Lecturas simples pueden quedarse en el controller con scopes.

## 6. Resource (`app/Http/Resources`)

Fechas con `->toIso8601String()`, dinero `(float)`, URLs de archivos con `Storage::disk('public')->url()`, relaciones con `whenLoaded`. Nunca expongas email, teléfono o dirección de otra persona en un recurso público.

## 7. Controller (`app/Http/Controllers/Api`)

```php
public function store(StoreProfessionalServiceRequest $request): JsonResponse
{
    $service = $this->services->create($request->user(), $request->validated());

    return $this->created(new ProfessionalServiceResource($service), 'Servicio creado.');
}
```

Siempre `$this->ok()`, `$this->created()` o `$this->paginated()`. `$this->authorize()` antes de tocar un recurso ajeno.

## 8. Tests (`tests/Feature/<Recurso>Test.php`)

Por endpoint, como mínimo: caso feliz, validación (422 con los campos) y permisos (401 sin token, 403 con otro rol u otro dueño). Factories con estados (`User::factory()->professional()->verified()`), `Sanctum::actingAs($user)`, helper `professionalWithService()` de `tests/Pest.php`.

## 9. Documentación

- `docs/api/endpoints.md`: mueve el endpoint de "Planeados" a "Implementados" con request, respuesta y errores.
- Revisa que `/docs/api` (Scramble) lo muestre bien.
- Si creaste o cambiaste un Resource, indícalo en el PR: el frontend debe actualizar `src/shared/types/models.ts`.

## 10. Verificación final

```bash
php artisan test
vendor/bin/pint --dirty
```

Todo en verde. Rama `feature/HU0xx-…`, commit convencional y PR con `Refs #N` (ver `docs/guia-de-trabajo.md`).
