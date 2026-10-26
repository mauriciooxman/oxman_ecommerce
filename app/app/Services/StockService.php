<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class StockService
{
    public function adjust(User $user, int $id, array $data): StockMovement
    {
        try {
            return DB::transaction(function () use ($user, $id, $data) {
            $product = $user->store->products()->lockForUpdate()->findOrFail($id);
            Gate::forUser($user)->authorize('update', $product);
            $existing = StockMovement::where('operation_id', $data['operation_id'])->first();
            if ($existing) {
                abort_unless((int) $existing->product_id === $id && (int) $existing->user_id === $user->id
                    && $existing->mode === $data['mode'] && $existing->quantity === (int) $data['quantity']
                    && $existing->reason === $data['reason'], 409, 'La identidad de operación ya se usó con otros datos.');
                return $existing;
            }
            app(OwnerCatalogService::class)->assertVersion($product, (int) $data['version']);
            abort_if($product->archived_at !== null, 409, 'No se ajustan productos archivados.');
            $quantity = (int) $data['quantity'];
            if (($data['mode'] === 'variation' && $quantity === 0) || ($data['mode'] === 'count' && $quantity < 0)) {
                throw ValidationException::withMessages(['quantity' => 'La variación debe ser distinta de cero y el conteo no puede ser negativo.']);
            }
            $after = $data['mode'] === 'count' ? $quantity : $product->stock + $quantity;
            if ($after < 0 || $after > 1000000) {
                throw ValidationException::withMessages(['quantity' => 'El resultado debe estar entre 0 y 1.000.000 unidades.']);
            }
            $movement = $product->movements()->create([
                'store_id' => $user->store_id, 'user_id' => $user->id, 'operation_id' => $data['operation_id'],
                'mode' => $data['mode'], 'quantity' => $quantity, 'before' => $product->stock,
                'after' => $after, 'delta' => $after - $product->stock, 'reason' => $data['reason'], 'created_at' => now(),
            ]);
            $product->stock = $after;
            $product->version++;
            $product->save();
            return $movement;
            }, 3);
        } catch (QueryException $error) {
            if (($error->errorInfo[1] ?? null) === 1062) {
                abort(409, 'La identidad de operación ya se usó.');
            }
            throw $error;
        }
    }
}
