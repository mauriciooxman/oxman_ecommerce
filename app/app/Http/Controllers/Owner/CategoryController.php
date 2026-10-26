<?php

namespace App\Http\Controllers\Owner;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class CategoryController
{
    public function index(Request $request)
    {
        return view('owner.categories', ['store' => $request->user()->store, 'categories' => $request->user()->store->categories()->orderBy('name')->get()]);
    }

    public function save(Request $request, ?int $category = null)
    {
        $item = $category ? $request->user()->store->categories()->findOrFail($category) : null;
        if ($item) { Gate::authorize('update', $item); }
        $request->merge(['name' => is_string($request->input('name')) ? preg_replace('/\s+/u', ' ', trim($request->input('name'))) : $request->input('name')]);
        $rules = ['name' => ['required', 'string', 'max:60', Rule::unique('categories')->where('store_id', $request->user()->store_id)->ignore($category)]];
        foreach (array_diff(array_keys($request->all()), ['name', '_token', '_method']) as $field) {
            throw ValidationException::withMessages([$field => 'Campo no permitido.']);
        }
        // Identidad derivada de la ruta: el formulario fallido conserva su propio borrador.
        $request->merge(['category_form' => $category ? 'category-'.$category : 'category-new']);
        $data = $request->validate($rules);
        try {
            DB::transaction(function () use ($request, $item, $data) {
            if ($item) {
                $item = $request->user()->store->categories()->lockForUpdate()->findOrFail($item->id);
                $item->update(['name' => $data['name']]);
                $request->user()->store->products()->where('category_id', $item->id)->update(['category' => $data['name'], 'version' => DB::raw('version + 1')]);
            } else {
                $request->user()->store->categories()->create(['name' => $data['name']]);
            }
            });
        } catch (QueryException $error) {
            if (($error->errorInfo[1] ?? null) !== 1062) { throw $error; }
            throw ValidationException::withMessages(['name' => 'Este nombre ya existe en tu tienda.']);
        }
        return redirect()->route('owner.categories')->with('status', 'Categoría guardada. Los productos conservan su asignación.');
    }
}
