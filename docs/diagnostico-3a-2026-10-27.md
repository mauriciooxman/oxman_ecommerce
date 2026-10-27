# Retoma de 3A tras interrupción por créditos

Inspección: 2026-10-27, America/Santiago. Solo diagnóstico; implementación no reanudada.

## Punto de interrupción

Git HEAD: `3cac5ae`. Cambios encontrados y conservados:

- `spec-3a-checkout-invitado.md`: pasó de `ready-for-dev` a `in-progress`; `route: dispatch` fue sustituido por `baseline_commit: NO_VCS`. Hay Git disponible, por lo que este último valor no identifica una base real.
- `spec-panel-dueno-catalogo-local.md`: pasó de `in-review` a `done`. C8 tiene aceptación humana registrada; el diff solo cambia el estado y no aporta nueva evidencia del cierre formal BMAD. Las notas internas aún describen el estado histórico anterior.
- Archivo nuevo sin seguimiento: `app/database/migrations/2026_10_27_000100_create_orders.php`. No se agregó al índice ni se modificó durante el diagnóstico.

3A quedó al comienzo de la implementación: respaldo preparado y migración escrita, sin aplicación en el estado actual. No existen los modelos Order/OrderLine/OrderEvent, OrderService, CheckoutController, CheckoutRequest, vistas de checkout, CheckoutTest ni verify-orders-runtime.php. No hay rutas de checkout/seguimiento ni integración de reservas en Product, carro o StockService.

## Migración: creada, no aplicada

MySQL estaba detenido. Se inició únicamente la instancia existente del proyecto con el comando documentado, sin inicializar datos, migrar ni sembrar. Antes de consultar las tablas se verificaron driver, host, puerto, base, ausencia de URL/socket alternativos y `@@datadir` dentro de `.local/mysql/data`.

Consultas SELECT verificaron ambas bases, `oxman_demo` y `oxman_demo_test`:

- Registro `migrations`: contiene las cinco migraciones anteriores; ninguna `create_orders`.
- `information_schema`: no existen `orders`, `order_lines`, `order_events` ni `products.stock_reservado`.
- La base demo conserva actualmente 7 productos y 9 tramos; no se sustituyeron por los 6/7 históricos.

Esto confirma que el estado actual no contiene aplicación total ni restos parciales del DDL de pedidos. No permite reconstruir operaciones históricas que hubieran sido revertidas.

Respaldo existente: `.local/backups/antes-pedidos-3a.sql`, 19.932 bytes; marca local de finalización 2026-10-27 15:31:12. No contiene el esquema nuevo. Se conservó; no se probó su restauración.

## Spec, código y pruebas

La spec 3A mantiene todas las tareas desmarcadas y las notas de implementación pendientes: corresponde a trabajo inicial, no a funcionalidad entregada. Su ruta de migración menciona `2026_10_26_000100`; el archivo creado usa `2026_10_27_000100`. Sus comandos de Verification expresan resultados esperados, no resultados de esta ejecución.

Evidencia anterior conservada:

- Documentación del panel: 53 pruebas/980 aserciones y 34 checks HTTP/concurrencia aprobados históricamente. No son pruebas de pedidos.
- `.phpunit.result.cache` tiene marca local 2026-10-27 15:31:31, anterior a la migración (15:31:55). Contiene 55 entradas acumuladas, incluidas dos ExampleTest históricos, y 48 defectos históricos; no es un informe de una ejecución completa. No permite afirmar que la última suite pasó ni atribuirle 53/980. No contiene pruebas de Checkout.
- `.local/cierre-tests.log` registra 27 pruebas/200 aserciones del catálogo anterior; no acredita checkout ni la suite completa de esta interrupción.

Verificado en este diagnóstico: diferencias Git, existencia y contenido de archivos, esquema/registro de migraciones por SELECT, conteos agregados y sintaxis PHP de la migración (correcta). No se ejecutaron PHPUnit, migraciones, rollback, seed, arneses HTTP, Composer, caché Blade ni inspección visual.

## Bloqueo concreto antes de continuar

La guardia `assertDemoDatabase()` de la nueva migración permite exclusivamente `oxman_demo` y rechaza expresamente `oxman_demo_test`. PHPUnit exige esta última y usa RefreshDatabase. Por tanto, la suite funcional actual intentaría ejecutar la migración y fallaría en su guardia. No se lanzó para evitar DDL durante esta inspección. La sintaxis correcta no resuelve esta incompatibilidad.

StockService aún solo comprueba stock físico entre 0 y 1.000.000: falta impedir ajustes/conteos por debajo de lo reservado bajo el mismo bloqueo. La nota anterior de retoma ya exige esta integración.

## Continuación pendiente

1. Retomar el workflow BMAD aplicable sin regenerar ni reiniciar por rutina; reconciliar las notas de estado y la base Git con evidencia, conservando la intención aprobada.
2. Corregir y verificar la guardia para los destinos locales autorizados, manteniendo la protección contra bases ajenas; alinear la ruta de migración de la spec.
3. Validar la suite base en el destino protegido y verificar la utilidad del respaldo antes de una primera aplicación autorizada de la migración. Volver a consultar estado antes de aplicar; no repetirla a ciegas.
4. Completar código e integración de reservas, pruebas funcionales, atomicidad/idempotencia/aislamiento y concurrencia MySQL/HTTP real; después revisión y aceptación de 3A. 3B sigue pendiente y depende de 3A.

No se descartaron ni sobrescribieron cambios existentes. MySQL dedicado quedó iniciado para consultas locales; no se abrió servidor web ni túnel.
