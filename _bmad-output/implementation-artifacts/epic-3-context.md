# Epic 3 Context: Registrar un pedido de demostración sin comprometer inventario dos veces

<!-- Compiled from planning artifacts. Edit freely. Regenerate with compile-epic-context if planning docs change. -->

## Goal

El comprador puede confirmar un pedido ficticio persistente desde el carro, obteniendo una referencia única y reservando existencias de forma atómica. El dueño gestiona esos pedidos desde el panel: cotiza despacho, registra confirmación manual de pago, prepara, despacha o cancela con motivo, conservando inventario consistente en todo momento. La épica cierra el recorrido completo de compra demo sin pasarelas de pago ni transportistas externos.

## Stories

- Story 3A: Flujo del comprador — checkout invitado, pedido persistente con reserva atómica
- Story 3B: Flujo del dueño — gestión de pedidos y estados desde el panel

## Requirements & Constraints

**Funcionales confirmados por Mauro (2026-10-26):**
- Comprador invitado: nombre y teléfono obligatorios; correo opcional. Para despacho: región, comuna y dirección obligatorias. Para retiro: sin dirección.
- Revisar productos, precios y condición de entrega antes de confirmar (página de revisión previa).
- Referencia de pedido accesible por token aleatorio no predecible (no ID numérico expuesto). El número de referencia visible solo no permite acceder.
- Reserva sin vencimiento automático. El dueño cancela manualmente. Mostrar fecha y antigüedad del pedido.
- Solo el dueño cancela (desde el panel), con motivo registrado, en pedidos no despachados ni entregados.
- Reserva liberada exactamente una vez al cancelar. Devoluciones posteriores fuera de esta entrega.
- Operaciones del dueño: listar/filtrar por estado, ver detalle (snapshot + historial), cotizar despacho, registrar aceptación de cotización, confirmar pago manual, pasar a preparación, marcar despachado/retirado, cancelar con motivo.
- Pago NO se marca automáticamente al crear el pedido.
- Cotización debe ser aceptada antes de despachar; mecanismo manual sencillo para registrar esa aceptación.
- Historial de cambios de estado por pedido. Estados de pedido, pago y despacho separados.

**Reglas de inventario:**
- Carro NO reserva ni modifica stock.
- Al confirmar pedido: existencia física sin cambio, reserva +N, disponible −N. Transacción atómica: pedido + reserva o nada.
- Al cancelar: reserva −N (una vez, idempotente). Existencia física sin cambio.
- Al entregar/despachar: existencia física −N, reserva −N (una vez). Pedido cancelado no se puede entregar.
- Ventas presenciales (panel existente): existencia física −N, reserva sin cambio. Ya implementado en StockService.
- Disponible = existencia física − reserva. Checkout valida contra disponible.

**No funcionales:**
- Precio, stock y publicación re-validados en servidor al confirmar (nunca precio del cliente).
- Dos intentos simultáneos por la última unidad → solo uno confirma, stock no negativo.
- Reintentos del mismo formulario → idempotentes (misma referencia, sin duplicar pedido ni reserva). Clave de idempotencia en el formulario de checkout.
- Aislamiento: pedidos de tienda A nunca accesibles desde tienda B ni sin autenticación del dueño.
- 360 px sin desborde; formularios con labels, errores junto a campo y acciones con teclado.
- Despacho desconocido siempre mostrado como "pendiente de cotización", nunca como gratis ni con total inventado.

**Fuera de esta entrega:** pasarelas de pago, transportistas, facturación, correo automático, vencimiento de reservas, devoluciones, hosting/túnel.

## Technical Decisions

**Stack heredado:** Laravel 13 / Blade / MySQL 8 / PHP 8.3 / sesiones archivo / cola sync / sin Node.

**Modelos existentes a reutilizar:** Store, Product, PriceTier, Category, User, StockMovement, PricingService::quote, CartService::snapshot, StockService.

**Nuevos modelos/tablas:**
- `orders`: id, store_id, reference (único, UUID corto), access_token (aleatorio), modalidad (despacho|retiro), estado_pedido (pendiente|preparacion|despachado|retirado|cancelado), estado_pago (pendiente|recibido), estado_despacho (pendiente_cotizacion|cotizado|aceptado|despachado_registrado|retirado_registrado), nombre, telefono, correo (nullable), region (nullable), comuna (nullable), direccion (nullable), motivo_cancelacion (nullable), created_at, updated_at.
- `order_lines`: id, order_id, product_id, sku (snapshot), nombre_producto (snapshot), cantidad, precio_unitario, subtotal, created_at.
- `order_events`: id, order_id, tipo (estado_pedido|estado_pago|estado_despacho), valor_anterior, valor_nuevo, notas, usuario_id (nullable = comprador), created_at.
- `stock_reservations`: id, order_id, product_id, cantidad, liberada_at (nullable), created_at. O bien columna `reservado` en products — **decisión de diseño: columna en products** (más simple para SELECT FOR UPDATE en transacción).

**Convención de dinero:** enteros CLP. Snapshot guarda precio_unitario y subtotal calculados en servidor.

**Idempotencia de checkout:** campo oculto `idempotency_key` (UUID generado en cliente al abrir formulario). Al confirmar, buscar pedido existente con esa clave antes de crear. La clave vive en la sesión del comprador hasta que se confirma el pedido.

**Acceso del comprador a su pedido:** `/pedido/seguimiento/{access_token}` — muestra estado actual, líneas, condición de despacho. No expone datos personales completos ni permite operaciones.

**Control de concurrencia:** `SELECT ... FOR UPDATE` sobre `products.stock` y columna `products.stock_reservado` en la transacción de confirmación. Misma estrategia que StockService ya usa.

**Tienda resuelta en servidor:** igual que el panel. El dueño solo ve pedidos de su tienda; el `store_id` no viene del formulario.

## UX & Interaction Patterns

- **Página de revisión previa al checkout:** el comprador llega desde el carro, ve resumen de líneas y precios re-validados, elige modalidad (retiro/despacho) y completa datos. Un solo botón "Confirmar pedido" con CSRF.
- **Página de confirmación:** muestra referencia visible, enlace de seguimiento (con token), resumen de líneas, estado de despacho "pendiente de cotización", y nota explicativa sin total definitivo.
- **Panel del dueño — lista de pedidos:** tabla filtrable por estado, muestra referencia, fecha, antigüedad, modalidad, estado pedido/pago/despacho. Enlace a detalle.
- **Panel del dueño — detalle de pedido:** snapshot de líneas, datos de contacto y entrega, historial de eventos, botones de acción disponibles según estado actual.
- **Cotización y aceptación:** el dueño ingresa monto CLP de despacho (o marca retiro sin costo). La página de seguimiento del comprador muestra la cotización y un formulario simple para aceptarla (un botón con CSRF y el token de acceso).

## Cross-Story Dependencies

- 3A depende de CartService::snapshot y PricingService::quote (existentes).
- 3A depende de la tabla `products` con columna `stock_reservado` nueva.
- 3B depende de 3A (modelos de pedido, servicios de transición).
- 3B depende del panel de autenticación del dueño (Épica 2, implementado): OwnerAuthController, sesión, aislamiento por tienda.
- StockService existente maneja ventas presenciales; los pedidos usan OrderService separado para no mezclar lógica.
