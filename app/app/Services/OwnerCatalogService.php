<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;
use Illuminate\Database\QueryException;

class OwnerCatalogService
{
    public function __construct(private PhotoService $photos) {}

    public function save(User $user, ?int $id, array $data, ?UploadedFile $photo): Product
    {
        $path = null;
        $oldPath = null;
        try {
            $product = DB::transaction(function () use ($user, $id, $data, $photo, &$path, &$oldPath) {
                $category = $user->store->categories()->lockForUpdate()->findOrFail($data['category_id']);
                $product = $id ? $user->store->products()->lockForUpdate()->findOrFail($id) : new Product;
                if ($id) {
                    Gate::forUser($user)->authorize('update', $product);
                    $this->assertVersion($product, $data['version']);
                    abort_if($product->archived_at !== null, 409, 'Restaura el producto antes de editarlo.');
                }
                $values = array_intersect_key($data, array_flip(['sku', 'name', 'description', 'unit_price']));
                $values['compatibility'] = $data['compatibility'] ?? '';
                $values['category_id'] = $category->id;
                $values['category'] = $category->name;
                if (! $id) {
                    $values += ['store_id' => $user->store_id, 'stock' => 0, 'published' => false, 'image' => 'placeholder.svg', 'version' => 1];
                } else {
                    $values['version'] = $product->version + 1;
                }
                if ($photo) {
                    $path = $this->photos->store($photo, $user->store_id);
                    $oldPath = $product->photo_path;
                    $values['photo_path'] = $path;
                }
                $product->forceFill($values)->save();
                $product->tiers()->delete();
                foreach ($data['tiers'] as $tier) {
                    $product->tiers()->create($tier);
                }
                return $product;
            });
        } catch (Throwable $error) {
            if ($path) { Storage::disk('catalog')->delete($path); }
            if ($error instanceof QueryException && ($error->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages(['sku' => 'Este SKU ya existe en tu tienda.']);
            }
            throw $error;
        }
        if ($oldPath) { Storage::disk('catalog')->delete($oldPath); }
        return $product;
    }

    public function assertVersion(Product $product, int $version): void
    {
        abort_if($product->version !== $version, 409, 'El producto cambió. Recarga la página antes de guardar.');
    }

    public function changeState(User $user, int $id, array $data): void
    {
        DB::transaction(function () use ($user, $id, $data) {
            $product = $user->store->products()->lockForUpdate()->findOrFail($id);
            Gate::forUser($user)->authorize('update', $product);
            $this->assertVersion($product, (int) $data['version']);
            $action = $data['action'];
            abort_if($product->archived_at && $action !== 'restore', 409, 'Restaura el producto primero.');
            abort_if(! $product->archived_at && $action === 'restore', 409, 'El producto no está archivado.');
            if ($action === 'archive') {
                $product->archived_at = now();
                $product->published = false;
            } elseif ($action === 'restore') {
                $product->archived_at = null;
                $product->published = false;
            } else {
                $product->published = $action === 'publish';
            }
            $product->version++;
            $product->save();
        });
    }
}
