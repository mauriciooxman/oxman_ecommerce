@extends('owner.layout')
@section('title', 'Categorías')
@section('content')
@php($failedForm = is_string(old('category_form')) ? old('category_form') : '')
<div class="owner-heading"><h1>Categorías de {{ $store->name }}</h1></div><p>Renombrar se refleja en los productos publicados, ocultos y archivados de esta tienda.</p><section class="panel"><h2>Crear categoría</h2><form method="post" action="{{ route('owner.categories.create') }}">@csrf<label for="category-new-name">Nombre</label><input id="category-new-name" name="name" maxlength="60" value="{{ $failedForm === 'category-new' && is_string(old('name')) ? old('name') : '' }}" required aria-invalid="{{ $failedForm === 'category-new' && $errors->has('name') ? 'true' : 'false' }}" aria-describedby="category-new-name-error"><p class="field-error" id="category-new-name-error">@if($failedForm === 'category-new') @error('name'){{ $message }}@enderror @endif</p><button>Crear categoría</button></form></section>
@foreach($categories as $category)
@php($formId = 'category-'.$category->id)
<section class="panel"><h2>{{ $category->name }}</h2><form method="post" action="{{ route('owner.categories.update', $category) }}">@csrf @method('PUT')<label for="{{ $formId }}-name">Nuevo nombre</label><input id="{{ $formId }}-name" name="name" maxlength="60" value="{{ $failedForm === $formId && is_string(old('name')) ? old('name') : $category->name }}" required aria-invalid="{{ $failedForm === $formId && $errors->has('name') ? 'true' : 'false' }}" aria-describedby="{{ $formId }}-name-error"><p class="field-error" id="{{ $formId }}-name-error">@if($failedForm === $formId) @error('name'){{ $message }}@enderror @endif</p><button class="outline">Guardar nombre</button></form></section>@endforeach
@endsection
