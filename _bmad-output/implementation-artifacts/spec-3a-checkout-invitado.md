---
title: '3A — Checkout invitado: pedido persistente con reserva atómica'
type: 'feature'
created: '2026-10-26'
status: 'ready-for-dev'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '_bmad-output/implementation-artifacts/epic-3-context.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** el comprador puede armar un carro pero no puede registrar un pedido; no hay pedido persistente, referencia de seguimiento ni reserva de stock.

**Approach:** implementar el flujo completo de checkout invitado: página de revisión previa, confirmación del pedido en transacción atómica (snapshot de líneas + reserva de stock), token de seguimiento para el comprador y aceptación de cotización de despacho cuando el dueño la registre. Los datos del comprador (nombre, teléfono, modalidad, dirección) se validan en servidor; precio y disponible se re-calculan al confirmar.

## Boundaries & Constraints

**Siempre:** datos ficticios; precio, stock y publicación re-validados en servidor al confirmar (precio del cliente ignorado); reserva atómica con `lockForUpdate` (pedido + stock_reservado o nada); disponible = stock − stock_reservado ≥ cantidad solicitada; reintentos del mismo formulario idempotentes (una referencia por idempotency_key); aislamiento por tienda en toda operación; despacho desconocido siempre "pendiente de cotización" sin total definitivo; snapshot de líneas inmutable tras crear el pedido; CSRF en todos los formularios.

**Nunca:** precio ni store_id del cliente; total definitivo antes de cotización aceptada; pasarelas de pago, correo automático, vencimiento de reservas, devoluciones, túnel ni hosting; crear pedido sin reserva ni reserva sin pedido (fallo no deja estado parcial); modificar stock_reservado dos veces por la misma operación.

## I/O & Edge-Case Matrix

| Scenario | Input / Estado | Resultado esperado | Manejo de error |
|---|---|---|---|
| Checkout feliz — retiro | Carro válido, nombre, teléfono, modalidad=retiro | Pedido creado, reserva +N, confirmación con "Retiro en tienda" y sin total definitivo | — |
| Checkout feliz — despacho | Carro válido + región, comuna, dirección | Pedido creado, reserva +N, confirmación con "Despacho pendiente de cotización" | — |
| Reintento mismo idempotency_key | Mismo key, segundo POST antes de nueva sesión | Devuelve referencia del pedido ya creado; sin duplicar pedido ni stock_reservado | — |
| Precio cambiado entre carro y confirmación | unit_price en sesión difiere del calculado en servidor | Rechaza; re-muestra revisión con aviso de precio; no crea pedido | Redirige a revisión |
| Producto oculto/archivado entre revisión y confirmación | archived_at o published=false al momento de confirmar | Línea retirada; si queda vacío, no crea pedido; si quedan otras líneas, rechaza con aviso | Redirige a revisión |
| Stock insuficiente al confirmar | disponible < cantidad solicitada | Rechaza; no crea pedido ni reserva parcial; aviso con disponible actual | Redirige a revisión |
| Dos confirmaciones simultáneas — última unidad | Dos requests concurrentes, disponible = 1 | Una confirma (disponible 0), la otra falla con aviso de stock. products.stock_reservado no excede stock | SELECT FOR UPDATE garantiza solo una |
| Datos de contacto faltantes | Nombre o teléfono vacíos | Errores de validación junto al campo; sin pedido | Redirige con errores |
| Despacho sin dirección | modalidad=despacho, sin región/comuna/dirección | Error de validación; sin pedido | Redirige con errores |
| Correo con formato inválido | correo=texto-sin-arroba | Error de validación; sin pedido | — |
| Token de seguimiento inexistente | GET /pedido/seguimiento/{token-invalido} | 404 | — |
| Token correcto, tienda distinta a la configurada | Tienda resuelta en servidor difiere | 404 (no revela existencia) | — |
| Aceptación de cotización sin cotización registrada | POST /pedido/seguimiento/{token}/aceptar-cotizacion, estado_despacho ≠ cotizado | 422; sin cambios | — |
| Carro vacío en revisión | Sesión sin líneas válidas | Redirige al catálogo con mensaje | — |

</frozen-after-approval>

## Code Map

**Existente a reutilizar:**
- `app/app/Services/CartService.php` — `snapshot(Request, Store)` devuelve `['lines'=>[...], 'notices'=>[...], 'subtotal'=>int]`. Cada línea: `['product'=>Product, 'quantity'=>int, 'unit_price'=>int, 'subtotal'=>int, 'minimum'=>int, 'next'=>null|array]`. Usar para cargar el carro en la revisión.
- `app/app/Services/PricingService.php` — `quote(Product $product, int $quantity)`, requiere `tiers` eager-loaded. Re-ejecutar en el servidor al confirmar; no usar precios de sesión.
- `app/app/Models/Product.php` — columnas: `store_id`, `sku`, `name`, `stock`, `unit_price`, `published`, `archived_at`, `version`. Scope `scopePubliclyVisible()`: `published=true AND archived_at IS NULL`. Nueva columna `stock_reservado` a agregar aquí. `lockForUpdate()` en transacción.
- `app/app/Models/Store.php` — `slug`, `name`, `active`. Tienda resuelta en servidor (slug fijo para demo pública).
- `app/app/Services/StockService.php` — patrón de concurrencia a replicar: `DB::transaction(...,3)` + `lockForUpdate()` + idempotencia por UUID. NO usar StockService directamente para reservas de pedido; crear `OrderService`.
- `app/routes/web.php` — rutas públicas del storefront sin auth. Agregar rutas de checkout y seguimiento en este mismo grupo sin auth.

**Nuevos archivos:**
- `app/database/migrations/2026_10_26_000100_create_orders.php` — tablas `orders`, `order_lines`, `order_events`; columna `stock_reservado UNSIGNED INT NOT NULL DEFAULT 0 AFTER stock` en `products`. Guardia: verificar destino MySQL exclusivo.
- `app/app/Models/Order.php` — Eloquent. Columnas: `id`, `store_id` (FK), `reference` (string único, alfanumérico legible), `idempotency_key` (string único), `access_token` (string único, UUID), `modalidad` (enum: despacho|retiro), `estado_pedido` (enum: pendiente|preparacion|despachado|retirado|cancelado), `estado_pago` (enum: pendiente|recibido), `estado_despacho` (enum: pendiente_cotizacion|cotizado|aceptado|despachado_registrado|retirado_registrado), `monto_despacho` (unsignedInteger nullable), `nombre`, `telefono`, `correo` (nullable), `region` (nullable), `comuna` (nullable), `direccion` (nullable), `motivo_cancelacion` (nullable), `timestamps`. Relaciones: `store()`, `lines()`, `events()`.
- `app/app/Models/OrderLine.php` — snapshot inmutable: `order_id`, `product_id`, `sku`, `nombre_producto`, `cantidad`, `precio_unitario`, `subtotal`. Sin timestamps de actualización.
- `app/app/Models/OrderEvent.php` — append-only: `order_id`, `tipo` (enum: estado_pedido|estado_pago|estado_despacho|nota), `valor_anterior` (nullable), `valor_nuevo` (nullable), `notas` (nullable), `usuario_id` (nullable), `created_at` (solo).
- `app/app/Services/OrderService.php` — método `confirmar(Store $store, array $datos, string $idempotencyKey): Order`. Dentro de `DB::transaction`: busca orden por `idempotency_key` (idempotencia); si no existe: carga productos con `lockForUpdate`, re-calcula precios con `PricingService::quote`, verifica publicación y disponible (stock − stock_reservado ≥ cantidad), crea `Order` + `OrderLine[]` + registra evento inicial + incrementa `stock_reservado` de cada producto. Método `aceptarCotizacion(string $accessToken): Order`: busca orden por access_token (y store), verifica estado_despacho=cotizado, transiciona a aceptado, registra evento.
- `app/app/Http/Controllers/CheckoutController.php` — `review()` GET: llama `CartService::snapshot`, re-valida, genera `idempotency_key` en sesión si no existe, renderiza revisión. `confirm()` POST: valida `CheckoutRequest`, llama `OrderService::confirmar`, limpia carro y key de sesión, redirige a confirmación. `confirmation()` GET: muestra confirmación por `reference`. `tracking()` GET: muestra seguimiento por `access_token`. `acceptQuote()` POST: llama `OrderService::aceptarCotizacion` por `access_token`.
- `app/app/Http/Requests/CheckoutRequest.php` — validación: nombre (requerido, string, max:160), telefono (requerido, string, max:20), correo (nullable, email, max:254), modalidad (requerido, in:despacho,retiro), region/comuna/direccion (requeridos solo si modalidad=despacho, max:100/100/255). No acepta: precios, cantidades, store_id, product_id.
- `app/resources/views/checkout/review.blade.php` — líneas re-validadas con avisos, selector de modalidad con campos condicionales de dirección, campo oculto idempotency_key, CSRF. Labels, errores junto al campo, 360 px.
- `app/resources/views/checkout/confirmation.blade.php` — referencia visible, enlace de seguimiento (con access_token), resumen de líneas/subtotal, mensaje de despacho sin total definitivo.
- `app/resources/views/checkout/tracking.blade.php` — estado pedido/pago/despacho, líneas, fecha y antigüedad. Si estado_despacho=cotizado: monto y formulario de aceptación (POST CSRF con token). Solo nombre y modalidad visibles (no dirección completa).
- `app/tests/Feature/CheckoutTest.php`
- `tools/verify-orders-runtime.php` (parcial: escenarios del comprador)

## Tasks & Acceptance

**Ejecución:**
- [ ] `app/database/migrations/2026_10_26_000100_create_orders.php` — crear tablas `orders`, `order_lines`, `order_events`; agregar `stock_reservado` a `products`. Guardia de base exclusiva. Sin alterar datos existentes.
- [ ] `app/app/Models/Order.php`, `OrderLine.php`, `OrderEvent.php` — modelos Eloquent con relaciones, constantes de estado y casts.
- [ ] `app/app/Services/OrderService.php` — métodos `confirmar` y `aceptarCotizacion` con transacciones, lockForUpdate e idempotencia.
- [ ] `app/app/Http/Requests/CheckoutRequest.php` — validación con reglas condicionales por modalidad.
- [ ] `app/app/Http/Controllers/CheckoutController.php` — cuatro acciones (review, confirm, confirmation, tracking, acceptQuote).
- [ ] `app/resources/views/checkout/review.blade.php`, `confirmation.blade.php`, `tracking.blade.php` — vistas Blade accesibles.
- [ ] `app/routes/web.php` — agregar: `GET /checkout/revisar`, `POST /checkout/confirmar`, `GET /checkout/confirmacion/{referencia}`, `GET /pedido/seguimiento/{token}`, `POST /pedido/seguimiento/{token}/aceptar-cotizacion`.
- [ ] `app/tests/Feature/CheckoutTest.php` — pruebas de todos los escenarios de la matriz (incluye concurrencia real MySQL, reintentos, precios, stock, aislamiento).
- [ ] `tools/verify-orders-runtime.php` — arnés HTTP real: escenarios del comprador incluyendo dos procesos simultáneos sobre disponible=1.

**Acceptance Criteria:**
- Dado un carro válido con retiro, cuando completa checkout, entonces se crea pedido, stock_reservado += N por cada producto, página de confirmación muestra referencia y enlace de seguimiento sin total definitivo.
- Dado mismo idempotency_key, cuando se reintenta el POST, entonces devuelve la misma referencia sin crear un segundo pedido ni alterar stock_reservado.
- Dado disponible=1 y dos confirmaciones simultáneas, cuando ambas llegan al mismo tiempo, entonces exactamente una crea el pedido y la otra recibe error de stock; stock_reservado no excede stock.
- Dado precio cambiado en el servidor, cuando se confirma con precio viejo de sesión, entonces servidor rechaza, re-muestra revisión con aviso; precio del cliente nunca se usa.
- Dado token de seguimiento válido, cuando comprador consulta, entonces ve estado y líneas sin datos personales completos ni pedidos de otras tiendas.
- Dado estado_despacho=cotizado, cuando comprador acepta en seguimiento, entonces estado_despacho → aceptado; evento registrado.
- Dado carro vacío o producto despublicado al llegar a revisión, cuando abre /checkout/revisar, entonces redirige con mensaje claro.
- Dado fallo de transacción al confirmar, entonces ni pedido ni stock_reservado quedan modificados.

## Implementation Notes

Pendiente hasta implementación.

## Spec Change Log

## Review Triage Log

## Design Notes

**Columna stock_reservado vs tabla reservas:** se elige columna en `products` para permitir `SELECT ... FOR UPDATE` en una sola fila junto con `stock`, sin JOIN adicional. `disponible = stock − stock_reservado`. Las `order_lines` registran el snapshot de la reserva por pedido para poder liberar exactamente N al cancelar o entregar.

**Idempotencia del checkout:** `idempotency_key` (UUID) se genera en servidor al mostrar la página de revisión, se guarda en sesión y se incluye como campo oculto en el formulario POST. Al confirmar, se busca `orders.idempotency_key` primero. Si existe, retorna esa orden (sin tocar stock). Clave descartada de sesión tras éxito.

**Referencia visible vs access_token:** el `reference` es el número legible que el comprador puede anotar (ej. `OXM-20261026-A3K8`). El `access_token` es un UUID aleatorio, el único que da acceso a la URL de seguimiento. Ambos son únicos en la tabla.

**Aceptación de cotización:** el dueño registra el monto desde su panel (spec 3B). El comprador acepta desde `/pedido/seguimiento/{token}/aceptar-cotizacion` POST con CSRF. Sin autenticación adicional; el token es la autorización.

## Verification

**Commands:**
- `$env:PHPRC = 'C:/Users/mauro/Desktop/OXMANIT/oxman_ecommerce/.local/php.ini'; cd app; composer validate --strict` — expected: No errors found
- `php artisan test --compact` — expected: todas las pruebas pasan (53 existentes + nuevas de checkout)
- `php artisan route:list --except-vendor` — expected: rutas de checkout y seguimiento presentes
- `php artisan view:cache` — expected: exit 0
- `php tools/verify-orders-runtime.php` — expected: todos los checks pasan, stock_reservado consistente bajo concurrencia
