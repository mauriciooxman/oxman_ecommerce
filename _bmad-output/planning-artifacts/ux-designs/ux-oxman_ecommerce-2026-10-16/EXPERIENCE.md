---
name: Oxman Ecommerce
description: Comportamiento de catálogo, ficha y carro local invitado.
status: final
scope: demo-local-catalogo-ficha-carro
created: 2026-10-16
updated: 2026-10-16
sources:
  - ../../../implementation-artifacts/spec-catalogo-ficha-carro-local.md
  - .memlog.md
  - .working/direccion-inicial.md
visual-contract: DESIGN.md
---

## Foundation

Web adaptable para celular/computador, en español y CLP, sin cuenta. DESIGN.md define identidad y tokens. Sin biblioteca de UI: controles HTML nativos y formularios renderizados por servidor. Catálogo ficticio persistente; carro de sesión por tienda, sin continuidad garantizada entre dispositivos.

El cierre final se limita a «Catálogo, ficha y carro local», especificación aprobada. No incluye panel, pedidos, cobros, reserva ni descuento de inventario. Tokens exactos y distribución son **[ASSUMPTION]** de demo. Personas nombradas abajo son escenarios ilustrativos, no entrevistas.

## Information Architecture

| Superficie | Acceso | Necesidad y recorrido |
| --- | --- | --- |
| Catálogo | Inicio y enlace global Catálogo | Buscar nombre/SKU y categoría; productos publicados, incluso agotados. Flujo 1. |
| Ficha de producto | Nombre/imagen de TarjetaProducto o LineaCarro | Compatibilidad, disponibilidad y precios por cantidad. Flujos 1–2. |
| Carro | Enlace global Carro; agregar desde ficha | Consolidar SKU, editar/quitar y entender cambios/precios. Flujos 2–4. |

Catálogo/carro accesibles desde toda superficie; ficha permite regresar. Los filtros se conservan en el formulario de resultados; Volver al catálogo restablece la lista. **[ASSUMPTION]** GET para buscar/filtrar, POST para mutar, sin requerir JavaScript. No hay checkout ni acción que sugiera completar una compra.

Cobertura visual: [maqueta histórica](mockups/direccion-inicial.html), catálogo, ficha, carro y agotado. Búsqueda/filtros funcionales, vacíos, errores, revalidación y escritorio se construyen desde estas tablas (spine-only). El panel histórico queda fuera de IA.

## Voice and Tone

| Contexto | Texto/regla |
| --- | --- |
| Demo | «Comercio ficticio. Productos, precios, compatibilidad y existencias de demostración». Visible sin abrir ayuda. |
| Sin resultados | «No encontramos productos con esos filtros». Acción «Limpiar filtros». |
| Agregar | «Producto agregado al carro», cantidad consolidada y enlace «Ver carro». |
| Cantidad inválida | «Ingresa una cantidad entera de al menos 1 unidad» o «Disponibles: N unidades». |
| Cambio | «Revisamos tu carro: cambió el precio de [producto]» o «[producto] ya no está disponible». |
| Vacío | «Tu carro está vacío». «Volver al catálogo». |
| Límite | «Este carro es una demostración y no reserva existencias». «Despacho pendiente de cotización». «Total final pendiente». |

Sin prometer compra, despacho, ahorro total ni disponibilidad real. No pedir datos personales.

## Component Patterns

| Componente | Reglas de comportamiento |
| --- | --- |
| Encabezado | Identifica comercio ficticio; nombre enlazado al catálogo. Sin autenticación ni panel. |
| Aviso | Resultado de mutación y errores/cambios legibles; no desaparece por temporizador. Resumen de errores y mensaje asociado a la cantidad. |
| Navegacion | Enlaces reales; aria-current cuando aplica. Si muestra contador del carro, nombrarlo «unidades». |
| BusquedaFiltros | Busca por nombre/SKU y filtra una categoría; Aplicar conserva ambos valores. Limpiar restablece catálogo. Sin coincidencias conserva controles/valores. |
| TarjetaProducto | El nombre y Ver producto abren ficha; sin enlace envolvente con controles anidados. Precio detalle y menor umbral mayorista; agotado sigue consultable. |
| ImagenProducto | Alternativo describe accesorio/conectores; fallo no elimina nombre ni acceso al producto. |
| Disponibilidad | Stock ficticio vigente al renderizar; agotado impide agregar. No garantiza la siguiente petición. |
| TablaTramos | Caption/cabeceras semánticas; «Aplicado» identifica cálculo confirmado. Un precio unitario para todas las unidades del SKU. |
| Cantidad | Entero positivo en unidades base; label «Cantidad de [producto]». Ficha suma a lo existente; Actualizar del carro reemplaza cantidad. No determina precio/stock. |
| Accion | Enlace para navegar, botón para enviar; Agregar/Actualizar explícitos. Quitar separado, sin usar cero. Rechazo conserva carro válido. |
| LineaCarro | Una línea por SKU/tienda. Reagregar valida suma y consolida; otros SKU no suman para tramo. Quitar elimina solo esa línea. |
| SugerenciaTramo | Siguiente umbral superior alcanzable con stock: faltan umbral − cantidad. Informativa, sin agregar automáticamente. Omitir si último tramo o stock insuficiente. |
| ResumenCarro | Suma líneas válidas vigentes; indisponibilidad se informa explícitamente. Nunca presentar subtotal como total final a pagar. |
| Pie | Atribución discreta sin acciones administrativas. |

## State Patterns

| Superficie/estado | Tratamiento y recuperación |
| --- | --- |
| Catálogo / carga inicial | HTML de servidor, indicador nativo de navegación; sin skeleton artificial. Error de servicio y Reintentar cuando pueda renderizarse aviso. |
| Catálogo / vacío | «No hay productos disponibles en esta demo». Mantener navegación; nunca revelar ocultos. |
| Catálogo / sin resultados | Consulta/categoría, mensaje y Limpiar filtros; no confundir con stock cero. |
| Ficha / con stock | Compatibilidad, disponibilidad y tabla ficticias; cantidad inicial 1. Cálculo confirmado tras respuesta del servidor. |
| Ficha / agotada | «Sin stock»; Agregar deshabilitado con motivo; regreso al catálogo disponible. |
| Ficha / oculta, ajena o inexistente | 404 genérico y regreso; sin revelar datos de SKU. |
| Ficha y carro / cantidad inválida | Rechazar cero, negativo, fracción, texto y exceso. Ficha conserva valor enviado para corregir; carro conserva cantidad válida y error asociado. |
| Carro / carga y recarga | Revalidar tienda, publicación, precio y cantidad contra DB. Conservar sesión y recalcular en servidor. |
| Carro / vacío | Mensaje y Volver al catálogo; sin sugerencias ni resumen engañoso. |
| Carro / precio cambiado | Precio vigente, nuevo subtotal y aviso; ignorar importes del navegador. |
| Carro / stock insuficiente o retirado | Quitar la línea inválida y avisar la indisponibilidad explícitamente; conservar las demás líneas. Volver al catálogo permite elegir una cantidad disponible. Nunca ajustar cantidades silenciosamente ni mostrar datos nuevos del oculto. |
| Todas / foco | Contorno de DESIGN.md; tabulación sigue DOM. Error reconocible por texto además del color. |
| Todas / offline o servidor caído | Sin funcionamiento offline ni sincronización prometida. Si es posible: «No pudimos conectar. Reintenta cuando tengas conexión». Sin éxito de mutación antes de respuesta. |
| Mutación / sesión vencida o CSRF inválido | «La sesión venció. Recarga la página y revisa tu carro». No ejecutar sin validación ni prometer recuperar sesión expirada. |
| Todas / permisos | Sin superficies privadas. Producto ajeno/oculto: 404; no inventar login o recuperación. |

## Interaction Primitives

Ratón, tacto y teclado comparten acciones. Tab/Shift+Tab recorre enlaces, búsqueda, categoría, cantidad y botones. Enter envía formulario; Espacio activa botón; selección de categoría nativa. Sin atajos de una letra, gestos obligatorios, acciones hover ni modales.

Mutaciones con servidor y CSRF. Importes del cliente ignorados. Volver del navegador no confirma el precio: próxima lectura revalida. Fallar no duplica ni borra líneas válidas. No agregar por cambiar el valor de un campo sin enviar.

## Accessibility Floor

Objetivo WCAG 2.2 AA por comprobar al implementar. Contraste/foco: {colors.ink}, {colors.surface}, {colors.primary}; lectura {typography.body.fontSize}; margen {spacing.gutter}. Objetivos táctiles mínimos 44 × 44 px.

Un h1 por superficie, regiones header/nav/main/footer, «Saltar al contenido», labels visibles, botones nombrados por acción/producto, errores con aria-describedby y aria-invalid. Tabla con caption/cabeceras. Si hay actualización dinámica, éxito como estado y error como alerta; con navegación completa, título y aviso al inicio del contenido. Tramo aplicado también textual, además de {colors.tint}.

Sin trampas de foco; zoom al 200 % conserva contenido/controles. A 360 px sin desborde horizontal. Sin movimiento necesario; respetar prefers-reduced-motion si se añade transición. Ilustraciones con alternativo; iconos decorativos ocultos al lector.

## Responsive & Platform

Cortes de DESIGN.md: móvil apila controles/ficha/resumen; escritorio amplía rejilla y permite dos columnas. Conservar orden de lectura. No ocultar tabla, errores, Quitar ni condiciones en móvil. Verificar 360 px/escritorio, teclado y zoom. Sin promesa de aplicación nativa, PWA u offline.

## Key Flows

### Flujo 1 — Encontrar y revisar un accesorio (Camila, compradora de ejemplo)

1. Camila entra al Catálogo y reconoce comercio ficticio.
2. Busca nombre/SKU, elige categoría y pulsa Buscar.
3. Abre ficha y revisa compatibilidad/disponibilidad.
4. **Clímax:** entiende precio detalle y condiciones mayoristas sin cuenta.

Fallo: sin coincidencias puede Limpiar filtros; oculto recibe 404 con regreso. Cubre búsqueda/filtros, ficha y «Producto oculto/agotado» de la matriz fuente.

### Flujo 2 — Probar precios por cantidad (Diego, comprador de ejemplo)

1. Diego abre cable ficticio con stock suficiente.
2. Agrega 5 unidades: $3.000 c/u, subtotal $15.000.
3. Cambia a 6: $2.500 c/u, subtotal $15.000. Faltan 18 para 24 a $2.000 c/u.
4. Cambia a 23: $2.500 c/u, $57.500; luego 24: $2.000 c/u, $48.000.
5. **Clímax:** reconoce precio unitario único para todas las unidades del SKU; reducir cantidad recupera tramo correspondiente.

Fallo: stock menor que 24 omite sugerencia a ese tramo; exceso rechaza conservando carro válido. Cubre «Cable: 5/6/23/24 unidades», «Reducir cantidad» y «Seis cables, stock suficiente». No prometer ahorro total por aumentar unidades.

### Flujo 3 — Ajustar y conservar el carro (Camila, mismo navegador)

1. Camila agrega dos veces el mismo SKU; servidor valida suma y consolida.
2. Agrega otro SKU, que no modifica tramo del cable.
3. Prueba cero/negativo/fracción/texto/exceso: error y carro previo intacto. Precio manipulado se ignora.
4. Recarga y conserva carro de sesión con precios recalculados.
5. **Clímax:** Actualizar/Quitar permiten corregir; quitar última línea lleva al vacío y regreso al catálogo.

Fallo: SKU ajeno/oculto o agotado impide agregar; sesión vencida pide recargar/revisar. Cubre «Agregar mismo SKU», «Cero/negativo/fracción/texto/exceso», «Precio manipulado» y persistencia de sesión.

### Flujo 4 — Comprender cambios y límites (Diego, regreso al carro)

1. Diego vuelve tras cambiar precio/publicación/stock del catálogo ficticio.
2. Servidor revalida; aviso explica qué cambió sin ajustar silenciosamente cantidades.
3. El aviso informa que se quitó la línea no disponible; puede volver al catálogo y agregar una cantidad válida.
4. **Clímax:** ve subtotal vigente, despacho pendiente, total final pendiente y ausencia de reserva.

Fallo: sin conexión no hay éxito ni reserva prometida; reintentar. Cubre «Catálogo cambia» y «Búsqueda/carro vacíos» (despacho sin total definitivo). Sin continuación a pedido/pago.

## Preguntas futuras

Piloto: identidad/fotos autorizadas, unidades/presentaciones, reservas, impuestos, cobro, despacho, autenticación, separación productiva de tiendas y panel móvil. Permanecen abiertos; este contrato no los aprueba ni describe como implementados.

