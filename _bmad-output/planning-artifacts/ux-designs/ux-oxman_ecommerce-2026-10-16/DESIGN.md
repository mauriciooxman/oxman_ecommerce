---
name: Oxman Ecommerce
description: Contrato visual de catálogo, ficha y carro de la demo local ficticia.
status: final
scope: demo-local-catalogo-ficha-carro
created: 2026-10-16
updated: 2026-10-16
sources:
  - ../../../implementation-artifacts/spec-catalogo-ficha-carro-local.md
  - .memlog.md
  - .working/direccion-inicial.md
colors:
  background: '#F6F8FC'
  surface: '#FFFFFF'
  ink: '#172033'
  muted: '#526078'
  primary: '#1D4ED8'
  on-primary: '#FFFFFF'
  tint: '#EAF0FE'
  border: '#D8DFEB'
  control-border: '#526078'
  disabled: '#E8EDF5'
typography:
  body:
    fontFamily: system-ui, sans-serif
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.5'
  title:
    fontFamily: system-ui, sans-serif
    fontSize: 30px
    fontWeight: '700'
    lineHeight: '1.2'
  heading:
    fontFamily: system-ui, sans-serif
    fontSize: 24px
    fontWeight: '700'
    lineHeight: '1.25'
  price:
    fontFamily: system-ui, sans-serif
    fontSize: 25px
    fontWeight: '750'
    lineHeight: '1.3'
  helper:
    fontFamily: system-ui, sans-serif
    fontSize: 14px
    fontWeight: '400'
    lineHeight: '1.5'
rounded:
  sm: 8px
  md: 12px
  full: 9999px
spacing:
  '1': 4px
  '2': 8px
  '3': 12px
  '4': 16px
  '5': 24px
  '6': 32px
  gutter: 16px
  content-max: 1120px
components:
  Encabezado:
    background: '{colors.surface}'
  Aviso:
    foreground: '{colors.ink}'
  Navegacion:
    foreground: '{colors.primary}'
  BusquedaFiltros:
    border: '{colors.control-border}'
  TarjetaProducto:
    background: '{colors.surface}'
    radius: '{rounded.md}'
  ImagenProducto:
    radius: '{rounded.md}'
  Disponibilidad:
    foreground: '{colors.muted}'
  TablaTramos:
    active-background: '{colors.tint}'
  Cantidad:
    border: '{colors.control-border}'
  Accion:
    background: '{colors.primary}'
    foreground: '{colors.on-primary}'
    radius: '{rounded.md}'
  LineaCarro:
    background: '{colors.surface}'
  SugerenciaTramo:
    background: '{colors.tint}'
    foreground: '{colors.primary}'
  ResumenCarro:
    background: '{colors.surface}'
  Pie:
    foreground: '{colors.muted}'
---

## Brand & Style

Tienda clara, ordenada, con producto protagonista, identidad del comercio y OxmanIT discreto en el pie. Estas prioridades proceden de Mauro. **[ASSUMPTION]** Los valores exactos del frontmatter, nombre «Tecnología Demo», logo textual «TD», rejilla y distribución de escritorio fijan una base implementable para la demo; no constituyen aprobación de identidad comercial definitiva.

`status: final` cierra este contrato solo para catálogo, ficha y carro local, según la especificación aprobada en sources. DESIGN.md y EXPERIENCE.md prevalecen sobre maquetas en decisiones de su competencia.

Referencia histórica: [dirección inicial](mockups/direccion-inicial.html), vistas 01–03 y «Sin stock». Panel, pedidos y promesas de cotización futura de esa maqueta quedan fuera de esta entrega. Es estática y no acredita funcionamiento ni inspección visual renderizada.

## Colors

{colors.background} es fondo; {colors.surface}, superficie; {colors.ink}, texto principal. {colors.primary} identifica acciones/enlaces; {colors.tint}, tramo aplicado y explicación. {colors.muted} sirve a texto secundario legible. {colors.border} separa superficies; los límites de controles usan {colors.control-border}.

Objetivos: texto normal 4,5:1; texto grande y límites esenciales de controles/foco 3:1. Combinaciones previstas: ink/surface, muted/surface, on-primary/primary y primary/tint. Comprobar en implementación; no se declara certificación. Foco: contorno de 3 px en ink con separación de 3 px para distinguirse sobre el botón azul. Error siempre textual, sin depender del color.

## Typography

Lectura en {typography.body.fontFamily} a {typography.body.fontSize}; título en {typography.title.fontSize}, subsecciones en {typography.heading.fontSize}, precios en {typography.price.fontSize}, ayudas en {typography.helper.fontSize}. No reducir etiquetas ni errores para encajar. CLP con miles separados por punto y sin decimales. Los nombres y SKU se distribuyen en varias líneas cuando sea necesario; no truncar compatibilidad ni condiciones de precio.

## Layout & Spacing

Máximo {spacing.content-max}, margen lateral {spacing.gutter}, ritmo {spacing.2}, {spacing.4}, {spacing.5} y {spacing.6}. A 360 px no debe existir desplazamiento horizontal. **[ASSUMPTION]** Catálogo: una columna bajo 600 px, dos entre 600–899 px, tres desde 900 px. Ficha y carro apilados bajo 900 px; desde 900 px, imagen/datos y líneas/resumen pueden ocupar dos columnas. Conservar orden DOM de lectura.

Búsqueda/categoría/botón se apilan en móvil; navegación envuelve. Tabla flexible de dos columnas sin ancho mínimo fijo. Imagen de ancho adaptable y proporción 480:320; mostrarla completa y conservar visibles los conectores relevantes.

## Elevation & Depth

Jerarquía por espacio, título y borde; superficies planas. Carcasa de teléfono y sombra históricas son encuadre de maqueta, no elementos de la tienda.

## Shapes

{rounded.md} para superficies, imágenes, campos y acciones; {rounded.sm} para avisos de tramo; {rounded.full} reservado a filtros. Controles de cantidad con forma y etiqueta reconocibles.

## Components

Cada nombre tiene pareja conductual exacta en EXPERIENCE.md.

| Componente | Contrato visual |
| --- | --- |
| Encabezado | Nombre y TD provisional; «Comercio ficticio». Logo limitado para no desplazar navegación. |
| Aviso | Texto sobre superficie blanca, encabezado textual de demo/éxito/error/cambio; error junto al campo además del resumen. |
| Navegacion | Enlaces subrayados a catálogo/carro y regreso; destino actual con texto o peso además del azul. |
| BusquedaFiltros | Etiquetas visibles encima de campos; borde control-border y botón de aplicar visible. |
| TarjetaProducto | Imagen, nombre enlazado, SKU, precio detalle CLP y umbral mayorista; superficie blanca y borde. Sin precio tachado inventado. |
| ImagenProducto | SVG local y pie «Ilustración de producto ficticio»; reemplazo textual si falta recurso. |
| Disponibilidad | «Disponibles: N unidades» o «Sin stock»; nunca color aislado. |
| TablaTramos | Cantidad/precio unitario; fila aplicada en tint y texto «Aplicado». Cifras a la derecha. |
| Cantidad | Etiqueta permanente, campo legible y error debajo; objetivo táctil mínimo 44 × 44 px. |
| Accion | Primaria azul/blanco, secundaria con borde azul; alto mínimo 44 px. Deshabilitada con fondo disabled, texto muted y motivo visible. |
| LineaCarro | Nombre, cantidad editable, precio unitario, tramo, subtotal y Quitar con texto; apilada en móvil. |
| SugerenciaTramo | Bloque tint con unidades faltantes y próximo precio unitario; sin animación ni urgencia. |
| ResumenCarro | Subtotal destacado; «Despacho pendiente de cotización» y «Total final pendiente» separados. |
| Pie | «Tecnología de OxmanIT», tamaño helper y peso discreto. |

## Do's and Don'ts

| Hacer | Evitar |
| --- | --- |
| Etiquetar datos e ilustraciones como ficticios | Certificaciones, ventas, reseñas o urgencia inventadas |
| Explicar precio unitario y condición del tramo | Precio mayorista presentado como precio para una unidad |
| Controles accesibles por teclado y tacto | Acciones exclusivas de hover o gestos |
| Producto y legibilidad | Banners, carruseles automáticos y animación ornamental |

Identidad final, fotos autorizadas y modo oscuro permanecen abiertos; no bloquean esta demo.

