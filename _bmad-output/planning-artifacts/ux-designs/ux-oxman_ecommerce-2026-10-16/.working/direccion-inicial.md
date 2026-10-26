# Primera dirección visual para revisar

Fecha: 2026-10-16. Descubrimiento guiado BMAD; no finalizado.

[Abrir maqueta de cuatro pantallas](direccion-inicial.html). Es una muestra estática, con navegación por anclas; no calcula, guarda, autentica ni registra pedidos. Todos los productos, precios y existencias son ficticios. Las ilustraciones señalan el espacio de futuras fotografías autorizadas.

## Capturado de Mauro

Tienda moderna y ordenada, pensada para celular. Fondo claro, textos oscuros, azul en acciones, fotografías grandes y consistentes, buena legibilidad y navegación sencilla. Evitar saturación de banners, promociones y animaciones.

Cada comercio tendrá nombre, logo y colores propios; OxmanIT aparecerá discretamente en el pie. No existe logo definitivo ni referencia externa elegida.

| Pantalla | Contenido solicitado | Relación con PRD |
| --- | --- | --- |
| Catálogo | Foto, nombre, precio al detalle y aviso explícito de tramo mayorista | FR-1 a FR-3 |
| Producto | Compatibilidad, disponibilidad y tabla por cantidad | FR-2 y FR-3 |
| Carro | Precio aplicado y cantidad faltante para siguiente tramo | FR-3 a FR-5; explicación adicional solicitada por Mauro |
| Panel móvil | Productos, fotos, precios, existencias y pedidos con acceso sencillo | FR-9 a FR-14 |

## Elecciones provisionales [ASSUMPTION]

- Azul `#1D4ED8`, fondo `#F6F8FC`, superficie blanca, texto `#172033` y secundario `#526078`. El tono exacto del azul queda a revisión; no impone el color a los futuros comercios.
- Fuente del sistema, texto base de 16 px, radios de 12 px y controles de al menos 44 px de alto. Sin dependencia de una biblioteca de UI.
- Nombre «Tecnología Demo» y marca textual «TD» exclusivamente para mostrar el espacio de identidad del comercio.
- Tarjetas con imagen sobre el texto; la ficha prioriza foto y datos antes de cantidad y acción. El panel usa listas y acciones con texto.
- Muestra del cable: 40 unidades disponibles y 6 seleccionadas; son estados ilustrativos, no datos persistentes. Precios heredados del ejemplo ficticio del brief.
- «Faltan 18 unidades» comunica el próximo umbral, sin añadir automáticamente artículos. En la aplicación solo se sugerirán cantidades alcanzables con stock disponible y reglas confirmadas; no se prometerá un ahorro total cuando el total aumenta.

## Pendientes de experiencia

La maqueta ilustra cuatro superficies, no cierra la arquitectura de información. Faltan diseño de búsqueda y categorías, checkout, confirmación, acceso y recuperación, edición de producto y fotos, movimientos y venta presencial, lista/detalle de pedidos, cancelación, personalización de marca y estados de error. La recuperación y los permisos de OxmanIT dependen de OQ-5.

Los escenarios siguen siendo ESC-1 a ESC-3 del PRD, propuestos para validar; no hay sesiones reales ni testimonios. Confirmar A2–A6 antes de cerrar flujos: unidades y presentaciones, tramos, CLP, archivos y reserva. Cobro y despacho mantienen OQ-2/OQ-3 abiertas.

La siguiente conversación debe recoger qué conserva o cambia Mauro de esta composición. Después se completarán los contratos DESIGN.md y EXPERIENCE.md desde el memlog. Ambos conservan solo metadatos mientras continúa el descubrimiento.
