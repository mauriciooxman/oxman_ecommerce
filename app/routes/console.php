<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('owner:account {email} {--store=} {--role=} {--reset}', function () {
    $db = config('database.connections.mysql');
    if (! app()->environment(['local', 'testing']) || config('database.default') !== 'mysql'
        || ($db['host'] ?? '') !== '127.0.0.1' || (string) ($db['port'] ?? '') !== '3307'
        || ! in_array($db['database'] ?? '', ['oxman_demo', 'oxman_demo_test'], true)
        || ! empty($db['url']) || ! empty($db['unix_socket'])) {
        $this->error('Solo se permite el entorno local con la base ficticia dedicada.');
        return 1;
    }
    if ($this->option('role') !== 'owner' || ! $this->option('store')) {
        $this->error('Indica --role=owner y --store=ID explícitamente.');
        return 1;
    }
    $store = \App\Models\Store::where('active', true)->find($this->option('store'));
    $email = strtolower(trim($this->argument('email')));
    if (! $store || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $this->error('Tienda o correo inválidos.');
        return 1;
    }
    $user = \App\Models\User::where('email', $email)->first();
    if (($user && ! $this->option('reset')) || (! $user && $this->option('reset'))
        || ($user && ((int) $user->store_id !== $store->id || $user->role !== 'owner'))) {
        $this->error('La operación no corresponde a una cuenta dueña de esta tienda.');
        return 1;
    }
    if (! $this->input->isInteractive()) {
        $this->error('Se requiere consola interactiva con contraseña sin eco.');
        return 1;
    }
    $password = $this->secret('Contraseña nueva (mínimo 12 caracteres, máximo 72 bytes)', false);
    $confirmation = $this->secret('Repite la contraseña', false);
    if (! is_string($password) || mb_strlen($password) < 12 || strlen($password) > 72 || $password !== $confirmation) {
        $this->error('Contraseña inválida o confirmación diferente. No se guardó.');
        return 1;
    }
    \Illuminate\Support\Facades\DB::transaction(function () use ($email, $store, $password, $user) {
        $account = $user ? \App\Models\User::lockForUpdate()->findOrFail($user->id) : new \App\Models\User;
        $account->forceFill(['name' => 'Dueño de tienda ficticia', 'email' => $email, 'store_id' => $store->id, 'role' => 'owner',
            'password' => $password, 'credential_version' => ($account->credential_version ?? 0) + 1, 'remember_token' => null])->save();
    });
    unset($password, $confirmation);
    $this->info('Cuenta guardada. Las sesiones anteriores quedaron revocadas.');
    return 0;
})->purpose('Crear o restablecer un dueño ficticio exclusivamente por consola local');

Artisan::command('owner:stores', function () {
    $db = config('database.connections.mysql');
    if (! app()->environment(['local', 'testing']) || config('database.default') !== 'mysql'
        || ($db['host'] ?? '') !== '127.0.0.1' || (string) ($db['port'] ?? '') !== '3307'
        || ! in_array($db['database'] ?? '', ['oxman_demo', 'oxman_demo_test'], true)
        || ! empty($db['url']) || ! empty($db['unix_socket'])) {
        $this->error('Solo se permite el entorno local con la base ficticia dedicada.');
        return 1;
    }
    $this->table(['ID', 'Tienda ficticia', 'Identificador', 'Activa'], \App\Models\Store::orderBy('id')->get()->map(fn ($store) => [$store->id, $store->name, $store->slug, $store->active ? 'Sí' : 'No'])->all());
    return 0;
})->purpose('Consultar los ID de tiendas ficticias para aprovisionar al dueño');
