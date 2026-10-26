---
title: '3B — Gestión de pedidos por el dueño: cotización, pago, preparación, entrega y cancelación'
type: 'feature'
created: '2026-10-26'
status: 'ready-for-dev'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '_bmad-output/implementation-artifacts/epic-3-context.md'
  - '_bmad-output/implementation-artifacts/spec-3a-checkout-invitado.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** el dueño no puede ver ni gestionar los pedidos de su tienda; no hay transiciones de estado con historial, no hay mecanismo de cotización ni de cierre de inventario al entregar.

**Approach:** extender el panel del dueño con una sección de pedidos: lista filtrable por estado, detalle con snapshot y historial de eventos, y acciones de cotizar despacho, confirmar pago, pasar a preparación, registrar entrega y cancelar con motivo. Cada transición valida el estado actual, actualiza los tres estados independientes (pedido / pago / despacho), descuenta inventario al entregar y libera la reserva exactamente una vez. Depende de los modelos y servicios creados en 3A.

## Boundaries & Constraints

**Siempre:** aislamiento por tienda en toda lectura y escritura privada; transiciones de estado validadas en servidor (estado actual comprobado antes de actuar); cancelación libera stock_reservado exactamente una vez (idempotente); entrega descuenta stock físico y libera stock_reservado en transacción; historial de eventos append-only (nunca borrar ni editar); dueño solo opera pedidos de su propia tienda; cotización aceptada antes de poder registrar entrega; CSRF en todas las acciones.

**Nunca:** cancelar un pedido ya despachado o entregado; entregar un pedido cancelado; liberar o descontar reserva dos veces; exponer pedidos de otra tienda; confirmar pago automáticamente al crear el pedido; modificar el snapshot de líneas después de creado el pedido; pasarelas de pago, correo automático, vencimiento de reservas, devoluciones, túnel ni hosting.

## I/O & Edge-Case Matrix

| Scenario | Input / Estado | Resultado esperado | Manejo de error |
|---|---|---|---|
| Listar pedidos | Dueño autenticado, pedidos de su tienda | Lista con referencia, fecha, antigüedad, modalidad, estado pedido/pago/despacho. Sin pedidos de otra tienda | — |
| Ver detalle | Pedido propio | Snapshot de líneas, datos de contacto, historial de eventos con fecha Santiago | — |
| Ver detalle — pedido ajeno | ID de pedido de otra tienda | 404 | — |
| Cotizar despacho — monto CLP | estado_despacho=pendiente_cotizacion, monto >0 | estado_despacho → cotizado, monto_despacho guardado, evento registrado | — |
| Cotizar despacho — retiro sin costo | estado_despacho=pendiente_cotizacion, monto=0 o checkbox retiro | estado_despacho → aceptado directamente (no requiere aceptación del comprador), evento registrado | — |
| Cotizar despacho — estado incorrecto | estado_despacho ≠ pendiente_cotizacion | 422; sin cambios | — |
| Confirmar pago | estado_pago=pendiente | estado_pago → recibido, evento registrado | — |
| Confirmar pago — reintento | estado_pago=recibido | 409 o mensaje; sin cambios | — |
| Pasar a preparación | estado_pedido=pendiente | estado_pedido → preparacion, evento registrado | — |
| Registrar entrega — feliz | estado_pedido=preparacion, estado_despacho=aceptado, estado_pago=recibido | estado_pedido → despachado o retirado, stock −N, stock_reservado −N en transacción, evento registrado | — |
| Registrar entrega — sin cotización aceptada | estado_despacho ≠ aceptado | 422; sin cambios de inventario | — |
| Registrar entrega — sin pago confirmado | estado_pago ≠ recibido | 422; sin cambios de inventario | — |
| Registrar entrega — reintento | estado_pedido ya en despachado/retirado | 409; sin duplicar descuento de stock ni liberación de reserva | — |
| Registrar entrega — pedido cancelado | estado_pedido=cancelado | 422; sin cambios | — |
| Cancelar — estado válido | estado_pedido=pendiente o preparacion, motivo provisto | estado_pedido → cancelado, stock_reservado −N (exactamente una vez), evento registrado | — |
| Cancelar — reintento | estado_pedido=cancelado | 409 o mensaje; stock_reservado sin cambio | — |
| Cancelar — ya entregado | estado_pedido=despachado o retirado | 422; sin cambios | — |
| Cancelar sin motivo | motivo vacío | Error de validación; sin cambio de estado | — |
| Filtrar por estado | Parámetro estado en URL | Lista muestra solo pedidos de ese estado de la tienda | — |

</frozen-after-approval>

## Code Map

**Existente a reutilizar (heredado de 3A):**
- `app/app/Models/Order.php`, `OrderLine.php`, `OrderEvent.php` — creados en 3A. Constantes de estado. `Order::scopeForStore(Store)` para aislamiento.
- `app/app/Services/OrderService.php` — ampliar con: `cancelar(Order, User, string $motivo): void`; `cotizarDespacho(Order, User, ?int $montoClp): void`; `confirmarPago(Order, User): void`; `pasarAPreparacion(Order, User): void`; `registrarEntrega(Order, User): void`. Todas en `DB::transaction`. `cancelar` y `registrarEntrega` usan `lockForUpdate` sobre `products` afectados.
- `app/app/Models/Product.php` — `stock_reservado` creado en 3A. Al cancelar: `stock_reservado -= N`. Al entregar: `stock -= N` y `stock_reservado -= N` en la misma transacción.
- `app/app/Http/Controllers/Owner/CatalogController.php` — patrón de autorización: tienda desde `$request->user()->store`, proteger con middleware `owner`.
- `app/routes/web.php` — grupo `/dueno` con middleware `owner:login` externo y `owner` en rutas protegidas.

**Nuevos archivos:**
- `app/app/Http/Controllers/Owner/OrderController.php` — acciones: `index()`, `show()`, `cancel()` POST, `quoteShipping()` POST, `confirmPayment()` POST, `toPreparation()` POST, `deliver()` POST. Todas validan pertenencia a tienda; IDs ajenos → 404.
- `app/app/Http/Requests/Owner/CancelOrderRequest.php` — valida motivo (requerido, string, max:500).
- `app/app/Http/Requests/Owner/QuoteShippingRequest.php` — valida monto (nullable, integer, min:0) y tipo_retiro (boolean opcional).
- `app/resources/views/owner/orders/index.blade.php` — tabla con referencia, fecha, antigüedad, nombre, modalidad, tres estados. Filtros por estado en URL. 360 px, teclado.
- `app/resources/views/owner/orders/show.blade.php` — snapshot de líneas con subtotales, datos de contacto/entrega, historial de eventos (fecha America/Santiago), sección de acciones según estado actual (botones POST CSRF). Separación visual pedido/pago/despacho. Cotización: campo monto CLP y opción retiro sin costo.
- `app/tests/Feature/Owner/OrderManagementTest.php`
- `tools/verify-orders-runtime.php` — ampliar arnés con escenarios del dueño: cancelación concurrente (dos solicitudes simultáneas de cancelación de un pedido), entrega con reintento, entrega de cancelado rechazada. Limpiar fixtures en `finally`.

## Tasks & Acceptance

**Ejecución:**
- [ ] `app/app/Services/OrderService.php` — ampliar con métodos `cancelar`, `cotizarDespacho`, `confirmarPago`, `pasarAPreparacion`, `registrarEntrega`. Cada uno verifica estado actual antes de actuar; lanza excepción de estado inválido en lugar de silenciar. Cancelar y registrarEntrega usan `lockForUpdate` sobre productos.
- [ ] `app/app/Http/Requests/Owner/CancelOrderRequest.php`, `QuoteShippingRequest.php` — validación de campos de entrada de acciones del dueño.
- [ ] `app/app/Http/Controllers/Owner/OrderController.php` — siete acciones (index, show, cancel, quoteShipping, confirmPayment, toPreparation, deliver). Resuelve tienda del usuario autenticado; `Order::scopeForStore($store)` en toda consulta.
- [ ] `app/resources/views/owner/orders/index.blade.php`, `show.blade.php` — vistas accesibles, 360 px, teclado, fechas en America/Santiago.
- [ ] `app/routes/web.php` — bajo `/dueno` con middleware `owner`: `GET /pedidos`, `GET /pedidos/{id}`, `POST /pedidos/{id}/cancelar`, `POST /pedidos/{id}/cotizar-despacho`, `POST /pedidos/{id}/confirmar-pago`, `POST /pedidos/{id}/preparacion`, `POST /pedidos/{id}/entregar`. Nombres con prefijo `owner.orders.`.
- [ ] `app/tests/Feature/Owner/OrderManagementTest.php` — cubre: listar solo propios, ver detalle, todos los caminos de la matriz I/O (incluye reintentos y estados inválidos), dos tiendas aisladas.
- [ ] `tools/verify-orders-runtime.php` — ampliar con escenarios del dueño: cancelación doble concurrente (sin duplicar liberación), entrega con reintento, entrega de cancelado.
- [ ] `docs/pedidos-local.md` — documentar uso, política de reserva sin vencimiento (limitación explícita y cómo incorporar vencimiento después), evidencia ejecutada y límites.

**Acceptance Criteria:**
- Dado el dueño autenticado, cuando lista pedidos, entonces ve solo los de su tienda con fecha y antigüedad. Un ID de pedido de otra tienda devuelve 404.
- Dado un pedido en estado pendiente con cotización pendiente, cuando el dueño cotiza monto CLP > 0, entonces estado_despacho → cotizado y evento registrado. La página de seguimiento del comprador muestra el monto y el formulario de aceptación.
- Dado modalidad=retiro y cotización sin costo (monto=0), cuando el dueño cotiza, entonces estado_despacho → aceptado directamente (sin esperar aceptación del comprador).
- Dado estado_despacho=aceptado, estado_pago=recibido, estado_pedido=preparacion, cuando el dueño registra entrega, entonces stock −N y stock_reservado −N en transacción; estado_pedido → despachado o retirado; evento registrado.
- Dado que ya se registró entrega (reintento), cuando el dueño intenta registrar entrega de nuevo, entonces 409 sin alterar stock ni reserva.
- Dado estado_pedido=pendiente o preparacion, cuando el dueño cancela con motivo, entonces estado_pedido → cancelado, stock_reservado −N exactamente una vez, evento registrado con motivo.
- Dado pedido cancelado, cuando el dueño intenta cancelar de nuevo, entonces 409 sin alterar stock_reservado.
- Dado pedido cancelado, cuando el dueño intenta registrar entrega, entonces 422 sin cambios.
- Dado estado_despacho ≠ aceptado, cuando el dueño intenta registrar entrega, entonces 422 sin cambios de inventario.
- Dado historial de eventos de un pedido, cuando el dueño consulta el detalle, entonces todos los eventos aparecen en orden cronológico con fecha en America/Santiago; ningún evento se borra ni modifica.

## Implementation Notes

Pendiente hasta implementación.

## Spec Change Log

## Review Triage Log

## Design Notes

**Transición de entrega — requisitos previos:** la entrega exige `estado_pago=recibido` Y `estado_despacho=aceptado` (o `despachado_registrado`/`retirado_registrado` para el reintento idempotente). Esta validación se hace en `OrderService::registrarEntrega` antes de adquirir el lock, con re-verificación dentro de la transacción.

**Retiro sin costo — cotización simplificada:** cuando la modalidad es retiro y el dueño ingresa monto 0 (o activa checkbox "sin costo de despacho"), `cotizarDespacho` transiciona directamente a `aceptado` en lugar de `cotizado`, porque no requiere decisión del comprador. La vista del panel muestra esta distinción.

**Antigüedad de pedidos:** la vista `index` calcula la diferencia entre `created_at` y `now()` en PHP/Blade (horas o días según magnitud). No requiere campo adicional en la base de datos.

**Sin vencimiento automático:** documentado en `docs/pedidos-local.md` como limitación de esta entrega. Para incorporar vencimiento después: agregar comando artisan `orders:expire --hours=N` que cancele pedidos en estado `pendiente` más viejos que N horas, reutilizando `OrderService::cancelar`.

## Verification

**Commands:**
- `$env:PHPRC = 'C:/Users/mauro/Desktop/OXMANIT/oxman_ecommerce/.local/php.ini'; cd app; php artisan test --compact` — expected: todas las pruebas pasan (incluye 53 existentes + checkout 3A + gestión 3B)
- `php artisan route:list --except-vendor` — expected: rutas `/dueno/pedidos` presentes con middleware owner
- `php artisan view:cache` — expected: exit 0
- `php tools/verify-orders-runtime.php` — expected: todos los checks pasan incluyendo cancelación concurrente sin reserva duplicada
