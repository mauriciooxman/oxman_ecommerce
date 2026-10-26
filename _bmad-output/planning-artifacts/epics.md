---
status: final
stepsCompleted: [step-01-validate-prerequisites, step-02-design-epics, step-03-create-stories, step-04-final-validation]
checkpoint: completado
inputDocuments:
  - ../implementation-artifacts/spec-catalogo-ficha-carro-local.md
  - prds/prd-oxman_ecommerce-2026-10-16/prd.md
  - ux-designs/ux-oxman_ecommerce-2026-10-16/DESIGN.md
  - ux-designs/ux-oxman_ecommerce-2026-10-16/EXPERIENCE.md
  - architecture/architecture-oxman_ecommerce-2026-10-16/ARCHITECTURE-SPINE.md
---

# Oxman Ecommerce — desglose de épicas

## Overview

Mauro confirmó insumos y extracción mediante C. Mauro aprobó estructura de cuatro épicas mediante C. BMAD paso3: Mauro autorizó explícitamente «Sí, revisemos todas en un lote», reemplazando checkpoints por historia/épica por una revisión conjunta final. Mauro revisó el lote de14historias y seleccionó C para validación final; ello aprueba el documento, no la implementación futura. La especificación aprobada gobierna la entrega local. El PRD conserva condición de borrador y solo alimenta trabajo futuro. No se han aprobado implementación de pedidos, reservas, panel ni operaciones reales. Las épicas siguientes son propuesta revisable; aún no hay historias ni aprobación de implementación futura.

## Requirements Inventory

### Functional Requirements

- FR-1: Catálogo público con identidad, categorías, búsqueda nombre/SKU y solo productos publicados; vacío recuperable. Entrega local.
- FR-2: Ficha con descripción, ilustración, precio, disponibilidad y compatibilidad explícitamente ficticia; agotado no se agrega. Entrega local.
- FR-3: Tramo único para todas las unidades del SKU sin mezclar artículos, CLP entero, límites 5/6/23/24; reducir recupera tramo. Entrega local según spec aprobada.
- FR-4: Carro invitado editable; consolidar SKU, recalcular, quitar, vacío y rechazo sin perder último carro válido. Entrega local.
- FR-5: Revalidación servidor de publicación, cantidad, stock y precio; precio cliente ignorado y cambios informados. Entrega local para carro; envío de pedido futuro.
- FR-6: Pedido invitado con contacto/dirección según modalidad y referencia persistente. Futuro pendiente de definición y aprobación.
- FR-7: Despacho transparente, cotización pendiente sin envío gratis implícito ni total definitivo; aceptación antes de cobro/despacho. Local solo comunica pendiente; modalidad/cotización/aceptación futuras.
- FR-8: Copia inmutable del pedido, idempotencia y consistencia transaccional con reserva. Futuro.
- FR-9: Administración autenticada/autorizada por tienda y revocación efectiva. Futuro; política de acceso/recuperación abierta.
- FR-10: Pertenencia de recursos por tienda; carro rechaza SKU ajeno. Local valida productos por tienda fijada en servidor; aislamiento privado productivo futuro.
- FR-11: Gestión de SKU único por tienda, atributos, fotos, precios, umbrales únicos y publicación; archivos autorizados y validados. Futuro; local solo esquema/seed ficticio.
- FR-12: Movimientos auditables de inventario y ventas presenciales sin invadir reservas ni duplicar descuentos. Futuro.
- FR-13: Reserva/cancelación concurrente sin disponible negativo ni liberación duplicada. Futuro: A6 no aprobado; carro local no reserva.
- FR-14: Estados de pedido, pago/despacho separado, cancelación/entrega/devolución idempotentes. Futuro sujeto a OQ-2.

### NonFunctional Requirements

- NFR-1: Uso móvil a 360 px sin desborde; labels/errores y teclado. En local: catálogo, ficha y carro; panel/pedido futuros. Inspección renderizada pendiente por navegador no disponible.
- NFR-2: Integridad fuera del navegador; futura concurrencia/reintentos de pedidos y movimientos sin disponible negativo ni duplicados. Local valida cantidades/precios/stock y conserva carro válido.
- NFR-3: Privacidad: público sin contactos/credenciales; pedidos autorizados y retención/respaldo/restauración antes de datos reales. Local ficticio sin recolección de contactos ni tarjetas.

### Additional Requirements

- AD-1: Tienda resuelta en servidor; consultas de producto por tienda, publicación y SKU; no store_id cliente.
- AD-2: PricingService dueño del cálculo CLP entero, tramo por producto y sugerencia solo alcanzable.
- AD-3: CartService dueño de sesión por tienda/producto; revalidar y avisar; no reservar ni modificar stock.
- AD-4: MySQL local exclusivo loopback 3307, datadir propio, bases demo/test y usuario limitado; guardia previa a DDL de pruebas; no tocar servicios/DB/hosting anteriores.
- AD-5: Blade/HTML con CSRF, escape y errores españoles; CSS/SVG estáticos; sesiones/caché archivo y cola sync sin procesos residentes extra.
- Starter para primera historia: skeleton oficial Laravel 13.0.0; lock framework13.37.0, PHP8.3.16 y MySQL8.0.30 comprobados localmente. Hosting y versiones productivas pendientes.
- Revalidación local: precio cambiado recalcula con aviso; línea oculta/stock insuficiente se quita con aviso y permite volver al catálogo.
- Antes de operación real: resolver autenticación/autorización, aislamiento privado, reservas/concurrencia, pagos/despacho, impuestos, contenido autorizado, piloto, alojamiento mantenido y restauración.

### UX Design Requirements

- UX-DR1 Encabezado: identidad ficticia del comercio visible y nombre enlazado al catálogo.
- UX-DR2 Aviso: demo permanente, estados/errores/cambios sin temporizador, error asociado a cantidad.
- UX-DR3 Navegacion: catálogo/carro/regreso reales, teclado y orden DOM.
- UX-DR4 BusquedaFiltros: nombre/SKU y categoría conjunta; conservar valores al buscar y limpiar recupera lista.
- UX-DR5 TarjetaProducto: ilustración grande, nombre/SKU, precio detalle CLP y menor umbral mayorista explícito; agotado consultable.
- UX-DR6 ImagenProducto: ilustración SVG etiquetada ficticia con alternativo y dimensiones fluidas; no inventar fotos autorizadas.
- UX-DR7 Disponibilidad: unidades o Sin stock textual; agotado impide agregar.
- UX-DR8 TablaTramos: cabeceras/caption y precio por cantidad; fila aplicada también textual, sin mezclar SKU.
- UX-DR9 Cantidad: entero positivo, etiqueta/ayuda/error, validar servidor y mantener carro válido ante rechazo.
- UX-DR10 Accion: enlace navega/botón muta, Agregar/Actualizar/Quitar explícitos, CSRF, motivo de deshabilitación y objetivo táctil ≥44 px.
- UX-DR11 LineaCarro: una línea por SKU, editar reemplaza y agregar suma; precio/tramo/subtotal transparentes.
- UX-DR12 SugerenciaTramo: siguiente umbral alcanzable; 6 cables muestra faltan18 para24 a$2.000; omitir si stock no alcanza.
- UX-DR13 ResumenCarro: subtotal vigente, despacho por cotizar y total final pendiente; sin checkout ni promesa de reserva.
- UX-DR14 Pie: OxmanIT discreto, sin administración en demo pública.
- UX-DR15 Tokens/responsive: claro/azul/oscuro, sistema16px, radios12px, rejilla1/2/3 columnas y ficha/resumen apilados en móvil; valores exactos supuestos de demo. Contraste objetivo4,5:1 texto y3:1 controles/foco, zoom200%, h1/landmarks/skip/labels/tablas.
- UX-DR16 Estados: resultados/carro vacío con regreso, oculto/ajeno404, error cantidad, precio cambiado, indisponibilidad informada, sesión/CSRF vencido y sin promesa offline.

### FR Coverage Map

| FR | Épica | Cobertura |
| --- | --- | --- |
| FR-1 | 1 | Catálogo/búsqueda/categorías/publicación |
| FR-2 | 1 | Ficha/compatibilidad/disponibilidad |
| FR-3 | 2 | Tramos por SKU y límites |
| FR-4 | 2 | Carro editable y sesión |
| FR-5 | 2 y 3 | Carro revalida local; pedido revalida antes de confirmar |
| FR-6 | 3 | Pedido invitado persistente |
| FR-7 | 2 y 3 | Aviso local y modalidad/cotización posterior |
| FR-8 | 3 | Snapshot, idempotencia y transacción |
| FR-9 | 4 | Acceso privado y autorizaciones |
| FR-10 | 1–4 | Scope público local; aislamiento productivo en 3–4 |
| FR-11 | 4 | Gestión de catálogo/fotos/tramos |
| FR-12 | 3 y 4 | Reglas de movimientos y operación presencial privada |
| FR-13 | 3 | Reserva/cancelación concurrente |
| FR-14 | 3 y 4 | Máquina de estados y operación privada |

## Epic List

### Épica 1: Encontrar el accesorio y revisar si sirve

El comprador consulta catálogo/ficha con búsqueda/categoría, compatibilidad y disponibilidad ficticias. Entrega un catálogo navegable sin requerir carro ni funcionalidades futuras.

**FR cubiertos:** FR-1, FR-2 y base pública FR-10.
**Dependencias:** ninguna épica anterior. Primera historia integra starter oficial y persistencia con esta experiencia, no una capa técnica aislada.
**Calidad/UX:** NFR-1/NFR-3 local, AD-1/4/5, UX-DR1–8/14–16.
**Estado:** experiencia implementada para demo local; revisión y visual pendiente.

### Épica 2: Armar y corregir un carro con precio por cantidad claro

El comprador agrega, consolida, edita/quita y conserva carro por sesión; comprende el tramo y despacho pendiente. Funciona hasta revisión del carro sin depender de pedidos/admin.

**FR cubiertos:** FR-3, FR-4, FR-5 en carro, FR-7 comunicación y FR-10 en carro.
**Dependencias:** épica1 como catálogo fuente. Aunque comparte modelos/vistas, se separa porque validar comprensión de precios/carrito puede cambiar checkout y limita riesgo monetario posterior.
**Calidad/UX:** NFR-1/2 local, AD-1–5, UX-DR2/3/8–16.
**Estado:** implementada solo demo local; no reserva ni confirma ventas.

### Épica 3: Registrar un pedido de demostración sin comprometer inventario dos veces

El comprador puede revisar y registrar pedido ficticio persistente con referencia, estado y despacho pendiente; confirmaciones/reintentos/concurrencia mantienen inventario consistente. Reglas de movimiento/reserva/cancelación implementadas y probadas en servidor; no depende de un panel futuro para confirmar el recorrido invitado.

**FR cubiertos:** FR-5 en pedido, FR-6, FR-7 completo según política acordada, FR-8, FR-10 en pedidos, reglas servidor FR-12/13/14.
**Dependencias:** épicas1–2; antes de implementar hay que acordar A6/OQ-2 y alcance cobro/despacho, datos privados y autorización. Ningún endpoint privado se expone esperando la épica4.
**Calidad:** NFR-2/3, transacciones/bloqueos/idempotencia, aislamiento probado y revalidación de precio/stock. Arquitectura/UX requieren actualización propia.
**Estado:** futura propuesta; no implementada ni autorizada como operación real.

### Épica 4: Mantener catálogo y atender pedidos desde un panel privado

El dueño autorizado gestiona productos/fotos/tramos, existencias y ventas presenciales; prepara pedidos y registra transiciones con historial. El valor completo usa los pedidos de épica3 y reglas consistentes, sin servicios futuros obligatorios.

**FR cubiertos:** FR-9, FR-10 privado, FR-11, operación UI FR-12/14 y cancelación FR-13.
**Dependencias:** épicas1–3. Definir recuperación de acceso/facultades OxmanIT, contenido autorizado, políticas de archivos y permisos antes de implementar; verificar hosting/respaldos antes de publicar o usar datos reales.
**Calidad:** NFR-1 panel móvil, NFR-2 inventario, NFR-3 privacidad/retención/restauración; no mezclar identidades/datos entre tiendas.
**Estado:** futura propuesta. No incluye elegir pagos/transportistas ni aprobar supuestos comerciales.

## Checkpoint del paso 2

Cuatro épicas por resultado de usuario, con dependencias solo hacia entregas anteriores. Los requisitos transversales se aplican en cada entrega; los futuros no se declaran implementados por existir esta lista.

¿Apruebas esta estructura para crear historias? [A] Elicitación avanzada · [P] Party Mode · [C] Continuar.

## Epic 1: Encontrar el accesorio y revisar si sirve

Meta: catálogo navegable y persistente, sin depender del carro. FR-1/2/10 público; NFR-1/3 local.

### Story 1.1: Configurar el proyecto desde starter oficial y explorar accesorios

Como comprador de demostración,
quiero abrir catálogo y ficha con compatibilidad/disponibilidad,
para evaluar un accesorio antes de elegir cantidad.

**Acceptance Criteria:**

**Given** instalación local exclusiva con skeleton oficial Laravel13/Blade y MySQL dedicado,
**When** migra/siembra y abre catálogo/ficha,
**Then** conserva seis productos/siete tramos tras reiniciar PHP y muestra cinco publicados, nombre/SKU/ilustración etiquetada/precio/primer tramo/compatibilidad,
**And** oculto/ajeno/inexistente devuelve404; agotado sigue consultable e impide agregar; no usa otra DB ni datos reales.

**Given** móvil360px o escritorio y teclado,
**When** navega catálogo/ficha,
**Then** h1/regiones/skip/labels/alternativos/foco/regreso son utilizables sin desborde,
**And** aplica DESIGN/EXPERIENCE y documenta cualquier inspección no ejecutada. FR-1/2/10; AD-1/4/5; UX-DR1/3/5–8/14–16.

### Story 1.2: Encontrar productos por nombre, SKU y categoría

Como comprador,
quiero buscar y filtrar el catálogo,
para encontrar el accesorio sin recorrer toda la lista.

**Acceptance Criteria:**

**Given** catálogo de1.1,
**When** busca Cable oDEMO-C1 y aplica categoría,
**Then** combina búsqueda/categoría solo entre publicados y conserva valores en resultados,
**And** sin coincidencias muestra mensaje, limpiar filtros y regreso; entradas inválidas muestran error español sin revelar ocultos. FR-1; UX-DR2/4/16.

## Epic 2: Armar y corregir un carro con precio por cantidad claro

Meta: carro local completo sin pedido ni reserva. FR-3/4/5 local/7 comunicación/10 carro; NFR-1/2.

### Story 2.1: Agregar unidades con el tramo correcto

Como comprador,
quiero agregar una cantidad y ver su precio unitario/subtotal,
para comprender la venta por unidad y por mayor.

**Acceptance Criteria:**

**Given** catálogo deépica1 y cable con stock40,precio3000,tramos6=2500 y24=2000,
**When** agrega5/6/23/24,
**Then** servidor calcula respectivamente15000/15000/57500/48000 con precio3000/2500/2500/2000 para todas las unidades del SKU,
**And** agregar mismo SKU consolida, otros SKU no suman; ignora precio/store_id cliente y no modifica stock. FR-3/4/10; AD-1/2/3; UX-DR8–11.

**Given** cero,negativo,fracción,texto,array,exceso u oculto/agotado/ajeno,
**When** intenta agregar,
**Then** servidor rechaza explicando y conserva último carro válido,
**And** formulario de retorno renderiza sin500, controles etiquetados y CSRF inválido no muta. UX-DR2/9/10/16.

### Story 2.2: Corregir cantidades, quitar y conservar sesión

Como comprador,
quiero actualizar/quitar líneas y volver al carro,
para corregir mi selección conservando los importes correctos.

**Acceptance Criteria:**

**Given** carro de2.1,
**When** actualiza6a5, agrega1más o quita línea,
**Then** actualizar reemplaza, agregar suma, tramo se recalcula y quitar última línea muestra vacío con regreso,
**And** cantidades inválidas no borran carro; recarga conserva sesión, otra sesión queda independiente y SKU de otra tienda no se admite. FR-3/4/10; UX-DR3/9–11/16.

### Story 2.3: Comprender cambios, siguiente tramo y costo pendiente

Como comprador,
quiero ver un carro revisado y las condiciones del siguiente tramo,
para entender el importe vigente y los límites de la demo.

**Acceptance Criteria:**

**Given** carro y catálogo modificable de muestra,
**When** cambian precio/publicación/stock y abre carro,
**Then** recalcula desde DB e informa precio cambiado o línea retirada por indisponibilidad; conserva las otras líneas,
**And** seis cables muestran faltan18para24a2000 solo si stock alcanza; no sugiere tramos imposibles.

**Given** carro con líneas válidas,
**When** revisa resumen,
**Then** ve subtotal,despacho pendiente,total final pendiente y no reserva,
**And** no hay checkout/pedido/cobro ni contactos solicitados. FR-5/7 local; UX-DR2/12/13/15/16.

## Epic 3: Registrar un pedido de demostración sin comprometer inventario dos veces

Meta futura: pedido/inventario consistentes. Depende1–2. No implementada; requiere acordar políticaA6/OQ-2 y alcance despacho/contactos/privacidad antes de comenzar. Estos criterios describen propiedades a preservar, no aprueban la política propuesta.

### Story 3.1: Confirmar un pedido invitado con referencia única y reserva consistente

Como comprador de prueba,
quiero revisar datos mínimos y registrar un pedido ficticio,
para obtener una referencia persistente con disponibilidad confirmada.

**Acceptance Criteria:**

**Given** política de reserva y datos mínimos acordados, catálogo/carro previos,
**When** confirma o reintenta el mismo envío,
**Then** servidor revalida producto/tienda/precio/cantidad/stock y registra una sola referencia con snapshot de líneas y condición de despacho en transacción con reserva,
**And** faltantes/cambio desde revisión exigen corregir/revisar; fallo no deja pedido/reserva parcial; dos intentos por última unidad producen una sola reserva y disponible no negativo. FR-5/6/8/10/13; NFR-2/3.

**Given** pedido registrado,
**When** edita después el catálogo o intenta leerlo desde otra tienda/sesión no autorizada,
**Then** snapshot no cambia y datos privados no son accesibles,
**And** política de acceso a referencia/contactos queda definida antes de exponerla; ningún panel futuro es requisito para este recorrido seguro.

### Story 3.2: Cancelar sin liberar unidades dos veces

Como operador autorizado de demostración,
quiero cancelar un pedido según política acordada,
para liberar compromiso sin alterar existencia física indebidamente.

**Acceptance Criteria:**

**Given** pedido/reserva de3.1 y transición de cancelación aprobada,
**When** cancela/repite/compite con otra transición,
**Then** una sola liberación se registra atómicamente, sin incrementar físico ni duplicar movimiento,
**And** estado no cancelable rechaza sin cambios; autorización y pertenencia se validan en servidor. FR-8/10/13/14; NFR-2. Se prueba mediante servicio/comando interno restringido hasta tener panel; no endpoint privado abierto.

### Story 3.3: Cotizar despacho y registrar aceptación explícita

Como comprador de prueba,
quiero conocer y aceptar el despacho cotizado,
para conocer el total antes de cobro o entrega.

**Acceptance Criteria:**

**Given** modalidades/cobertura/canal de aceptación acordados y pedido3.1,
**When** costo sigue desconocido o se registra una cotización,
**Then** desconocido muestra subtotal y total pendiente sin envío gratis; cotización muestra su costo y total para aceptación expresa,
**And** no se cobra ni despacha sin aceptación requerida; cambios posteriores invalidan aceptación anterior según política definida. FR-7/14. No elige pasarela/transportista ni integra pagos por esta historia.

### Story 3.4: Entregar con movimientos consistentes y devolución explícita

Como operador autorizado de prueba,
quiero registrar entrega y movimientos válidos,
para conservar inventario al transitar pedidos.

**Acceptance Criteria:**

**Given** pedido3.1,reserva,políticas de estado y pago/despacho acordadas,
**When** entrega/reintenta o registra devolución efectivamente recibida,
**Then** entrega descuenta físico y libera igual reserva una sola vez; cancelado no se entrega; devolución crea movimiento explícito,
**And** reembolso por sí solo no repone stock, ajustes no ocultan discrepancias y toda operación valida autorización/tienda. FR-12/14; NFR-2. Usa reglas probadas sin depender de UI futura.

## Epic 4: Mantener catálogo y atender pedidos desde un panel privado

Meta futura: operación del dueño por tienda, móvil. Depende1–3 y decisiones de permisos/recuperación/fotos/piloto. No implementada ni autorizada como operación real.

### Story 4.1: Acceder al panel de la propia tienda

Como dueño autorizado,
quiero iniciar/cerrar y recuperar acceso según política definida,
para operar solo los datos de mi comercio.

**Acceptance Criteria:**

**Given** roles/recuperación/facultades OxmanIT acordados,
**When** inicia,cierra,revoca sesión o cambia identificadores de otra tienda,
**Then** cada lectura/mutación privada valida autenticación,autorización y pertenencia; sesión ausente/revocada falla sin revelar datos,
**And** recuperación sigue mecanismo aprobado, contactos/credenciales no aparecen en catálogo y se prueban accesos cruzados con dos tiendas. FR-9/10; NFR-3.

### Story 4.2: Publicar productos y tramos por tienda

Como dueño autenticado,
quiero crear/editar y publicar mis productos/precios,
para mantener el catálogo vigente.

**Acceptance Criteria:**

**Given** acceso4.1,
**When** guarda SKU,descripción,categoría,compatibilidad,precio y tramos,
**Then** servidor rechaza SKU repetido dentro de tienda,umbral repetido,precio negativo/cantidad inválida; admite mismo SKU en otra tienda aislada,
**And** despublicar impide consulta/agregado y cambios informan carros sin modificar snapshots de pedidos; controles móviles etiquetados/teclado. FR-1/2/3/10/11; NFR-1/2.

### Story 4.3: Actualizar fotos autorizadas sin perder la anterior al fallar

Como dueño autorizado,
quiero subir/reemplazar fotos válidas de mi producto,
para mostrarlo con contenido propio.

**Acceptance Criteria:**

**Given** productos4.2 y formatos/tamaño/almacenamiento acordados,
**When** sube foto válida,inválida o intenta recurso ajeno,
**Then** servidor valida contenido/formato/tamaño y pertenencia; fallo conserva anterior; reemplazo solo afecta ese producto,
**And** no publica ejecutables ni datos privados, alternativo/identidad configurable y permisos/escritura se prueban antes de datos reales. FR-10/11; NFR-1/3.

### Story 4.4: Registrar entradas, ajustes y ventas presenciales

Como dueño autorizado,
quiero registrar movimientos físicos desde el celular,
para mantener stock compartido entre venta detalle y mayorista.

**Acceptance Criteria:**

**Given** acceso4.1 y reglas de reserva/movimientos deépica3,
**When** registra entrada,venta presencial o ajuste/reintento,
**Then** usuario,fecha,cantidad,motivo e identidad de operación quedan auditados y afectan existencia una sola vez,
**And** venta no invade reservas, ajuste conflictivo exige resolver pedidos sin borrar historial, otra tienda no lee ni modifica movimientos. FR-10/12/13; NFR-1/2/3.

### Story 4.5: Preparar pedidos y registrar estados con privacidad

Como dueño autorizado,
quiero revisar pedidos,cotizar y registrar transiciones permitidas,
para atenderlos sin duplicar reserva ni entrega.

**Acceptance Criteria:**

**Given** acceso4.1 y servicios probados deépica3,
**When** revisa/prepara/cancela/cotiza/entrega según política acordada,
**Then** UI conserva snapshot,separa pago/cotización/estado y solo invoca transiciones autorizadas de esa tienda,
**And** cancelado no se despacha,aceptación despacho/pago se comprueba según política, reintentos no duplican movimientos y tablero funciona móvil/teclado. FR-7/8/9/10/13/14; NFR-1/2/3.

## Revisión conjunta de historias

Excepción solicitada por Mauro: todas las historias se revisan en un lote. Se propusieron en secuencia, dependientes solo de historias/épicas anteriores. Total14historias:2+3+4+5. Esta revisión no autoriza implementar épicas3–4 ni operar con dinero/datos reales.

Cobertura UX:1.1cubre UX-DR1/3/5–8/14–16;1.2cubre2/4/16;2.1cubre8–11/16;2.2cubre3/9–11/16;2.3cubre2/12/13/15/16. Todos UX-DR1–16 cubiertos. FR-1–14 mapeados arriba y citados en historias; NFR1–3 aplican a entregas correspondientes.

¿Las14historias capturan los requisitos, tienen alcance por sesión y criterios completos/verificables? [A] Elicitación avanzada · [P] Party Mode · [C] Continuar a validación final.


## Validación final BMAD

- Cobertura FR-1–14: completa por mapa y criterios de historias; FR-5/7/10 distinguen subalcance local y futuro.
- Calidad NFR-1–3 y UX-DR1–16: asignadas a historias correspondientes; inspección visual local sigue pendiente y no se declara prueba ejecutada.
- Starter:1.1 instala/configura skeleton oficial, dependencias y solo entidades necesarias para catálogo/ficha; pedidos/movimientos no se crean de antemano.
- Historias:14, todas con actor/capacidad/valor, criterios Given/When/Then y referencias. Dependencias solo anteriores;1–2 funcionan sin3–4; reglas servidor3 preceden a UI privada4.
- Arquitectura: entrega1–2 respeta5AD locales.3–4 requieren nueva definición/actualización arquitectura/UX antes de ejecutar; no se aplica arquitectura de demo como autorización productiva.
- Solapamiento: modelos/controlador se comparten entre1–2, división justificada por validar navegación y precios antes de riesgo transaccional de pedidos.3–4 separan integridad y operación privada; sin dependencia inversa de UI.
- Formato: sin placeholders;14FR,3NFR,16UX-DR, lista de4épicas y14historias con cobertura. Revisión humana en lote autorizada expresamente.

Validación documental completada. Preparación para desarrollo:1–2 verificadas en demo;3–4 futuras y condicionadas a decisiones de política/privacidad/operación señaladas. No se anuncia que estén listas para operar datos reales.

Flujo documental completado bajo autorización explícita de continuar a validación final; sin cambio sustantivo posterior ni aprobación de implementación futura.