# Compra y gestión de pedidos local — 3A + 3B

Solo datos ficticios; Laravel, MySQL y Blade locales. No hay pagos reales, correos ni transportista conectado. No se requiere nueva migración para 3B. Para iniciar MySQL/PHP usa [la guía local](probar-demo-local.md); no repitas instalación ni seed. [Evidencia técnica y pendientes actuales](retoma-pedidos-epic3.md#actualización-3b--2026-11-02).

1. Abre <http://127.0.0.1:8000>, agrega pocas unidades al carro y pulsa **Revisar pedido**. Completa contacto ficticio y elige retiro o despacho (este último requiere región, comuna y dirección ficticias).
2. Confirma y guarda la referencia y el enlace privado. El pedido reserva unidades; aún no descuenta el stock físico. Conserva esa pestaña para actuar como comprador.
3. En otra pestaña abre <http://127.0.0.1:8000/dueno/ingresar> e ingresa con tu cuenta local existente. Ve a **Pedidos**. Filtra por estado y abre la referencia creada; revisa comprador, precios históricos e historial.
4. Para **despacho**, registra un monto CLP mayor que cero. En el seguimiento privado del comprador pulsa **Aceptar cotización de despacho**. El dueño ve esa aceptación al recargar el detalle. Para **retiro**, pulsa **Confirmar retiro sin costo**: queda aceptado directamente, sin cambiar la modalidad elegida al comprar.
5. Registra **pago recibido** (confirmación manual ficticia) y pasa a **preparación**. Son estados independientes: preparar no confirma pago ni acepta cotización.
6. Cuando se cumplan los tres requisitos, pulsa **Registrar despacho** o **Registrar retiro entregado**. Es el cierre de inventario: físico −cantidad y reserva −cantidad, una sola vez. El panel de stock muestra el movimiento auditado. Un despacho registrado representa salida al transporte, no certifica recepción en domicilio.
7. Para probar cancelación, crea otro pedido; antes de despacharlo/retirarlo abre **Cancelar pedido**, indica motivo y confirma. La reserva se libera sin aumentar el stock físico. Si ya registraste pago, conserva ese estado: no se efectúa devolución.

No se puede cancelar una salida registrada ni entregar un cancelado. Reenviar entrega/cancelación ya aplicada devuelve 409 sin repetir efectos. Si faltan pago, preparación o aceptación, la salida devuelve 422. Otras tiendas no pueden consultar ni modificar el pedido.

Las reservas **no vencen automáticamente**. Mientras un pedido siga pendiente/en preparación, mantiene sus unidades reservadas; cancélalo con motivo si abandonas una prueba. Un eventual vencimiento requiere una entrega futura: comando `orders:expire --hours=N`, con autorización de un actor de sistema, usando la misma transacción de cancelación y pruebas de carrera; no está implementado ni programado.

Para repetir la validación, con PHPRC local y desde `app/`, ejecuta en secuencia:

```powershell
php artisan test --compact
php ../tools/verify-orders-runtime.php
php ../tools/verify-owner-runtime.php
```

Usan exclusivamente `oxman_demo_test`. El arnés de pedidos abre temporalmente 8015/8016 y limpia sus fixtures; no lo ejecutes junto a PHPUnit. Quedan pendientes revisión visual a 360 px/escritorio, teclado y aceptación manual. No se ejecutó revisión BMAD.
