<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Support\GuardsDemoDatabase;
use Tests\TestCase;

class DatabaseGuardTest extends TestCase
{
    public function test_configuracion_ajena_se_detiene_antes_de_ddl(): void
    {
        config(['database.connections.mysql.database' => 'base_ajena']);
        $probe = new class {
            use RefreshDatabase, GuardsDemoDatabase {
                GuardsDemoDatabase::beforeRefreshingDatabase insteadof RefreshDatabase;
            }

            public bool $ddlCalled = false;

            protected function refreshTestDatabase(): void
            {
                $this->ddlCalled = true;
            }
        };
        try {
            $probe->refreshDatabase();
            $this->fail('Debió bloquear antes de cualquier DDL.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('antes de migrar', $exception->getMessage());
            $this->assertFalse($probe->ddlCalled);
        }
    }
}
