<?php

namespace Tests\Support;

use RuntimeException;

trait GuardsDemoDatabase
{
    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.connections.mysql');
        if (config('database.default') !== 'mysql'
            || ($connection['host'] ?? null) !== '127.0.0.1'
            || (string) ($connection['port'] ?? '') !== '3307'
            || ($connection['database'] ?? null) !== 'oxman_demo_test'
            || ! empty($connection['url']) || ! empty($connection['unix_socket'])) {
            throw new RuntimeException('Pruebas detenidas antes de migrar: solo se permite MySQL 127.0.0.1:3307/oxman_demo_test.');
        }
    }
}
