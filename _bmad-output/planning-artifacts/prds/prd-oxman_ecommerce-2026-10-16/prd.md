---
title: "Oxman Ecommerce — requisitos del piloto"
status: draft
created: 2026-10-16
updated: 2026-10-16
owner: Mauro / OxmanIT
timezone: America/Santiago
---

# PRD: Oxman Ecommerce

## 1. Propósito y estado

Borrador para revisar con Mauro antes del diseño y la arquitectura. Define una aplicación web para comercios de Meiggs que venden al detalle y al por mayor. La primera demo será de tecnología y accesorios de celular, con productos ficticios. No autoriza operar ventas reales ni representa una aprobación de alcance, tecnología o proveedores.

Fuentes: `../../briefs/brief-oxman_ecommerce-2026-10-16/brief.md`, su `addendum.md` y `../../../../docs/intake.md`. Ante la mención antigua de rubro pendiente en intake, prevalece la elección posterior de Mauro: tecnología y accesorios de celular.

## 2. Problema, propuesta y usuarios

Mauro observó comercios que muestran productos mediante redes sociales y quiere ofrecerles una tienda propia para recibir pedidos a distancia, especialmente desde regiones. No se han realizado entrevistas ni demostrado pérdida de ventas, demanda o disposición de pago.

[ASSUMPTION A1] Un catálogo con disponibilidad y precios visibles podría reducir consultas repetidas y facilitar pedidos. La apuesta de OxmanIT es acompañar al comercio en la puesta en marcha y mantenimiento; las funciones de comercio electrónico no se presentan como una innovación exclusiva.

El comprador al detalle necesita identificar el accesorio y su compatibilidad. El comprador mayorista necesita entender cantidades y descuentos. El dueño necesita mantener productos y registrar ventas físicas y online. OxmanIT configura el producto; sus permisos sobre datos de comercios deben acordarse expresamente.

### Escenarios propuestos para validar

Son guiones de prueba, no recorridos observados ni testimonios. Se omiten historias con protagonistas supuestamente entrevistados hasta contar con sesiones reales.

- **ESC-1:** una persona encuentra un cable, compara compatibilidad, cambia cantidades, revisa el precio y registra un pedido como invitado. Recibe una referencia y conoce que el despacho requiere cotización.
- **ESC-2:** el dueño entra al panel, publica ese cable con fotos y tramos, registra una venta presencial y revisa el pedido. Al cancelarlo, verifica que las existencias comprometidas se liberaron una sola vez.
- **ESC-3:** dos compradores intentan pedir la última unidad; solo uno recibe confirmación de disponibilidad. Un dueño de otra tienda no puede consultar ese pedido.

## 3. Glosario y alcance

- **Tienda:** comercio con identidad, usuarios y datos propios.
- **Producto vendible:** artículo identificable por SKU dentro de una tienda, con precio y existencias propios.
- **Unidad base:** unidad mínima contabilizada del producto vendible.
- **Tramo:** cantidad mínima y precio unitario aplicable a una línea.
- **Línea:** producto vendible, cantidad y precio dentro del carro o pedido.
- **Existencia física:** unidades registradas aún no entregadas o retiradas.
- **Reserva:** unidades comprometidas para pedidos pendientes.
- **Disponible:** existencia física menos reserva.
- **Pedido:** registro persistente de líneas y datos de contacto y entrega.

La demo cubrirá catálogo, carro, pedido, acceso privado e inventario. [ASSUMPTION A2] Empezará con unidades base; cajas, paquetes y variantes complejas quedan pendientes. Si se habilitan presentaciones, su equivalencia en unidades será obligatoria y ninguna compartirá stock implícitamente.

Quedan fuera del piloto propuesto: marketplace entre comercios, crédito, precios negociados por cliente, integración automática con cajas físicas, facturación tributaria automática, transportistas conectados y cobro automático sin proveedor elegido. La demo no requiere contratación ni publicación externa.

## 4. Requisitos funcionales

Los criterios siguientes son propuestas verificables para el piloto; las políticas señaladas como supuestos necesitan confirmación.

### Catálogo y precios

**FR-1. Catálogo público.** Mostrar marca de la tienda, categorías, búsqueda por nombre o SKU y productos publicados. Aceptación: un producto despublicado desaparece del catálogo y no puede añadirse mediante un enlace anterior; una búsqueda sin resultados informa cómo continuar.

**FR-2. Ficha de producto.** Mostrar nombre, descripción, foto, precio, disponibilidad y compatibilidad cuando corresponda. Aceptación: un cable con conector registrado muestra ese conector; un campo desconocido no se rellena con afirmaciones inventadas; sin disponibilidad no permite confirmar cantidades positivas.

**FR-3. Precios por cantidad.** [ASSUMPTION A3] Aplicar el tramo alcanzado a todas las unidades de la misma línea, sin sumar productos diferentes. Aceptación con precios ficticios: 5 cables a $3.000 suman $15.000; 6 a $2.500 suman $15.000; 23 suman $57.500; 24 a $2.000 suman $48.000. Al bajar de 6 a 5 se recupera el precio correspondiente. No se aceptan umbrales repetidos, cantidades menores que uno ni precios negativos. [ASSUMPTION A4] La demo usa CLP y cantidades enteras.

### Carro y registro del pedido

**FR-4. Carro editable.** Agregar, modificar y quitar líneas sin crear cuenta. Aceptación: agregar dos veces el mismo SKU consolida cantidades; cambiar cantidad recalcula precio y subtotal; quitar la última línea deja el carro vacío. Una cantidad inválida conserva el último valor válido e informa el error.

**FR-5. Revalidación.** Verificar precios, publicación y disponible en el servidor al enviar el pedido. Aceptación: alterar el precio en el navegador no cambia el cálculo; si precio o stock cambiaron desde el carro, se explica el cambio y se pide revisar antes de continuar. Nunca se sustituye silenciosamente un producto.

**FR-6. Pedido como invitado.** Solicitar nombre y al menos un contacto; para despacho, región, comuna y dirección; para retiro, solo los datos necesarios. Aceptación: faltantes impiden enviar; el comprador revisa líneas, importes y modalidad antes de confirmar; recibe una referencia persistente. El mecanismo de notificación queda por elegir.

**FR-7. Despacho transparente.** Habilitar únicamente modalidades configuradas por la tienda. Aceptación: con costo desconocido, la revisión y confirmación muestran “Despacho pendiente de cotización”, subtotal de productos y total final pendiente; no muestran envío gratis ni un total definitivo. La cotización posterior requiere aceptación del comprador antes del cobro total o despacho. Tarifas, cobertura y canal de aceptación son OQ-3.

**FR-8. Pedido consistente.** Conservar copia de productos, cantidades, precios y condición de despacho. Aceptación: editar después el catálogo no modifica el pedido; reintentar el mismo envío genera una sola referencia y una sola reserva. Fallar la confirmación no deja un pedido confirmado sin reserva ni una reserva sin pedido recuperable.

### Administración y separación entre tiendas

**FR-9. Acceso privado.** Permitir administración solo a usuarios autenticados y autorizados de la tienda. Aceptación: una sesión ausente, cerrada o revocada no puede leer pedidos ni modificar productos; ocultar botones no sustituye la autorización del servidor. Recuperación de acceso y facultades de OxmanIT son OQ-5.

**FR-10. Aislamiento.** Asociar productos, fotos, precios, movimientos y pedidos a su tienda y verificar esa pertenencia en cada operación. Aceptación: el dueño A no puede leer, editar, descargar ni borrar recursos privados de B cambiando identificadores o rutas; un carro de A no admite productos de B. Las fotos publicadas del catálogo pueden ser públicas.

**FR-11. Gestión de productos y fotos.** Crear y editar SKU, descripción, categoría, características, precios, tramos y publicación. Aceptación: SKU duplicado dentro de una tienda se rechaza; reemplazar una foto solo cambia ese producto; un archivo inválido se rechaza conservando la foto anterior. [ASSUMPTION A5] Proponer JPEG, PNG y WebP hasta 5 MB por foto, validados en el servidor; confirmar límites en OQ-6. La tienda aporta contenido autorizado.

### Inventario y operación

**FR-12. Movimientos de inventario.** Registrar entradas, ventas presenciales y ajustes con usuario, fecha, cantidad y motivo. Aceptación: una venta presencial reduce existencia física y disponible una sola vez; se rechaza si invade unidades reservadas. Un ajuste por diferencia física que comprometa reservas requiere resolver los pedidos afectados, sin ocultar la discrepancia ni borrar el historial.

**FR-13. Reserva y cancelación.** [ASSUMPTION A6 — política propuesta, no confirmada] Crear la reserva al registrar exitosamente el pedido; el carro no reserva. No hay vencimiento automático inicial: el dueño debe revisar y cancelar pedidos pendientes. Aceptación: con una unidad disponible y dos confirmaciones simultáneas, solo una reserva tiene éxito. Cancelar libera la reserva sin aumentar existencia física; repetir la cancelación no altera cantidades.

**FR-14. Estados del pedido.** Bajo A6, proponer pendiente, en preparación, despachado/entregado y cancelado; registrar pago y cotización por separado. Aceptación: pendiente o en preparación puede cancelarse; al despachar/entregar, reducir existencia física y liberar igual reserva una sola vez. Un pedido cancelado no se despacha. Tras despacho, una devolución exige un movimiento explícito por mercadería efectivamente recibida; un reembolso no repone stock automáticamente. El dueño confirma pago y despacho aceptado antes de despachar. Política definitiva: OQ-2.

## 5. Calidad transversal y datos

**NFR-1. Uso móvil:** catálogo, carro y panel funcionan a 360 px de ancho sin desplazamiento horizontal de la página; campos tienen etiquetas, errores legibles y acciones operables con teclado. Verificar con una carga de producto y un pedido completos.

**NFR-2. Integridad:** precio, autorización, reserva y movimientos se validan fuera del navegador. Las pruebas de concurrencia y reintento de FR-8, FR-12 y FR-13 deben conservar disponible no negativo y ausencia de duplicados.

**NFR-3. Privacidad:** el catálogo público no expone direcciones, contactos o credenciales; el acceso a pedidos exige autorización. Antes de ventas reales se acuerdan datos requeridos, retención, recuperación, respaldos y pruebas de restauración. No se almacenarán datos de tarjetas en la demo.

## 6. Validación y límites de éxito

No hay métricas comerciales aprobadas. Propuestas de prueba: observar carga y edición móvil por un dueño sin intervención, pedido al detalle y mayorista con cálculo correcto, comprensión de despacho pendiente, cancelación repetida y disputa simultánea por la última unidad. Registrar resultados y problemas; aún no fijar conversión, ingresos o ahorro de tiempo como compromisos.

Como contrapesos, registrar consultas que siguen requiriendo mensajes, diferencias físico/publicado, errores de compatibilidad y reservas pendientes que inmovilizan stock. Acordar volumen de datos y objetivos de rendimiento después de conocer al comercio.

## 7. open_questions y condiciones para avanzar

Mauro/OxmanIT es responsable de coordinar estas decisiones con el comercio; no se han delegado obligaciones al comercio sin su acuerdo.

- **OQ-1:** elegir comercio piloto, catálogo autorizado y validar problema y disposición de pago; revisar antes de prometer resultados o contratar servicios.
- **OQ-2:** confirmar A6, estados, cancelaciones, plazo de revisión y eventual vencimiento; bloquea operar pedidos reales.
- **OQ-3:** definir cobro, cotización, aceptación, cobertura y retiro; bloquea checkout operativo con dinero.
- **OQ-4:** validar tratamiento de impuestos, documentos comerciales y condiciones de compra antes de ventas reales.
- **OQ-5:** definir acceso de OxmanIT, recuperación de cuentas, privacidad, retención y respaldo antes de cargar datos reales.
- **OQ-6:** confirmar A2–A5, mínimos, presentaciones, archivos y reglas de compatibilidad antes de cerrar UX y pruebas.
- **OQ-7:** acordar presupuesto, alojamiento, dominio, soporte y precio del servicio antes de contratar; arquitectura y proveedores siguen abiertos.

## 8. Índice de supuestos

A1: reducción de consultas como hipótesis (§2). A2: unidades simples en demo (§3). A3: tramos por línea (§4). A4: CLP y cantidades enteras (§4). A5: formatos y límite de fotos (§4). A6: reserva sin vencimiento automático y estados propuestos (§4). Ninguno sustituye validación del piloto; mantener este PRD en borrador hasta revisión humana.
