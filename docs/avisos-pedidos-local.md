# Avisos de pedidos — captura local

Implementado el 2026-11-02 sin BMAD. Se reutilizan Laravel Mail, transporte `log`, sesión/autorización del panel y transacciones MySQL. **No se envían correos reales:** el mailer específico `owner_order_capture` captura en un archivo privado, independientemente de `MAIL_MAILER`. No hay SMTP externo, WhatsApp, worker ni cron.

Para comprobarlo:

1. Usa la demo local en <http://127.0.0.1:8000>. Crea un pedido con datos ficticios siguiendo [el recorrido de compra](pedidos-local.md) y anota su referencia.
2. Abre `app/storage/app/private/order-notices/mail.log` en el editor y busca esa referencia. Debe incluir el pago real (`pendiente` para una compra nueva) y el enlace `/dueno/pedidos/ID`. No incluye contacto, dirección ni enlace privado del comprador. El archivo contiene también capturas ficticias de los arneses; identifica tu referencia concreta.
3. Entra al panel con tu cuenta existente. La navegación muestra **N sin revisar** y el panel enlaza esos pedidos. En **Pedidos** verás **Nuevo**. Abre el detalle: se registra la primera revisión y baja el contador, sin cambiar pedido/pago/despacho, inventario ni historial comercial. Recargar no cambia la fecha de primera revisión. El contador también se actualiza automáticamente según el apartado siguiente.

## Alertas automáticas del panel

Desde cualquier pantalla autenticada del panel, una consulta GET cada 20 segundos devuelve únicamente cursor de llegada (ID), contador y hasta 25 pedidos nuevos con ID, referencia, unidades, modalidad y subtotal de productos. No modifica pedidos; el cursor continúa por lotes sin saltarlos y no depende de las variaciones del contador. Se reutiliza la serialización por tienda de OrderService para que el orden de IDs sea estable al confirmar compras. La respuesta no contiene datos de comprador ni estado de pago. Una ventana flotante tipo chat dice **Tienes una compra nueva**, con referencia, unidades, modalidad, subtotal y **Ver pedido**; contador y título de pestaña se actualizan. Mostrar o cerrar el aviso no marca revisión.

Para probarlo, deja el panel abierto, pulsa **Activar sonido** y luego **Probar sonido**. Crea un pedido ficticio desde otra ventana y espera hasta 20 segundos en condiciones normales: debe aparecer el aviso, actualizarse el título y sonar brevemente. Las llegadas del mismo lote se agrupan en una sola señal. **Silenciar** desactiva los avisos sonoros de esa tienda en las pestañas del mismo navegador; las alertas visuales continúan. Abrir el panel muestra los pendientes existentes sin alarmas históricas. Al recargar o abrir otra pestaña, activa el sonido allí mediante el botón si quieres que esa pestaña pueda reproducirlo; no se crea ni reanuda audio antes de esa interacción.

Prueba dos pestañas con sonido activado: el lote debe sonar una vez en total. Cursor, silencio y reclamación de sonido se coordinan con localStorage y Web Locks del mismo origen/perfil. Solo una pestaña consulta cuando corresponde. Sin almacenamiento o bloqueo compartido disponibles, se mantienen alertas visuales y se deshabilita la activación sonora para evitar duplicados. No se coordinan navegadores/perfiles distintos. Los avisos ya reclamados no se reproducen otra vez por consultar o recargar.

Se requiere **panel abierto y conexión**. El navegador puede limitar consultas/audio en segundo plano; no se garantiza aviso con el equipo suspendido o el navegador cerrado. No son notificaciones del sistema. Ante errores se espacian reintentos a 40/80/160/300 segundos; una respuesta de sesión vencida o permiso denegado detiene consultas y solicita ingresar de nuevo. No hay WebSockets, dependencias, migraciones ni procesos de servidor nuevos; no se modificó hosting ni se habilitó acceso público al panel local.

Validación nueva: **41 pruebas Laravel / 343 aserciones**, incluidas regresiones de gestión, revisión y captura de correo; **9 pruebas JavaScript** con transporte, DOM, audio y pestañas simulados (cursor, repetidos/recarga, agrupación, silencio, título/contador, sesión, backoff y audio suspendido). Sintaxis, rutas protegidas y Blade aprobados. **Pendiente:** inspección visual, teclado y prueba sonora/múltiples pestañas en navegador real. Estas simulaciones no acreditan haber visto ni escuchado las alertas reales.

El destinatario se configura por slug de tienda en `app/config/owner_orders.php`. Para `tecnologia-demo`, `OWNER_ORDER_DEMO_EMAIL` tiene por defecto `dueno-demo@example.test`, ficticio. Nunca se usa `correo` ni otro campo del comprador. Otra tienda necesita su propia entrada explícita; sin destinatario válido el aviso queda fallido y el pedido se conserva. No se agregaron formularios para editar esta configuración. El host del enlace se toma de `APP_URL`, no de encabezados del comprador.

La transacción crea una única fila en `order_owner_notices` por pedido. Después del commit exterior, un proceso reclama el aviso y captura el correo. Reenviar el mismo checkout no crea ni intenta otro aviso. La primera visita autorizada al detalle guarda `orders.reviewed_at`; los pedidos existentes se consideran sin revisar al incorporar la columna. No se generaron correos retroactivos de pedidos anteriores.

Estados técnicos del aviso: `pending`, `attempting`, `captured`, `failed`. `captured` significa escrito en la captura local, **no entregado al correo del dueño**. Un fallo registra solo ID de pedido y código (`capture_failed` o `recipient_not_configured`), sin texto de excepción, credenciales ni contacto del comprador. La compra ya confirmada permanece válida.

No se reintentan automáticamente avisos fallidos o reclamados: evita duplicados cuando el resultado es incierto. Una caída después del commit pero antes de reclamar deja `pending`; puede procesarse una vez, desde `app/` con PHPRC local:

```powershell
php artisan orders:notify-pending --store=ID
```

Indica el ID real de la tienda local. El comando limita cada ejecución a 1.000 pendientes de esa tienda y no toca `attempting`, `failed` ni `captured`. Una caída después de reclamar puede dejar `attempting` y perder el aviso; hay que inspeccionar la captura y diagnosticar antes de considerar un reenvío manual. No se implementó una interfaz de reenvío ni entrega garantizada. Procesar un pendiente toma el estado de pago actual, sin asumir que está pagado.

Respaldo previo: `.local/backups/antes-avisos-pedidos-20261102.sql`, restaurado/comparado en pruebas antes de aplicar la migración. Datos previos preservados. Evidencia nueva: 65 pruebas focalizadas/691 aserciones; 96 checks HTTP/MySQL (incluye regresiones de pedidos y carrera de captura única); Blade y sintaxis correctos. Revisión visual y aceptación manual pendientes. Los tests usan solo `oxman_demo_test`; no se configuró envío real ni entrega externa.
