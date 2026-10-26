# Historia propuesta 1.1 — Explorar accesorios de demostración persistentes

Estado: propuesta del paso 3; pendiente revisión/aprobación, aún no agregada a epics.md.

### Story 1.1: Abrir el catálogo ficticio y revisar un accesorio

Como comprador de demostración,
quiero abrir el catálogo y la ficha de un accesorio con precios, disponibilidad y compatibilidad claros,
para evaluar si el artículo se ajusta a lo que busco.

**Acceptance Criteria:**

**Given** una instalación local nueva con PHP compatible, skeleton oficial Laravel13/Blade y MySQL exclusivo de la demo,
**When** se migra/siembra y abre el catálogo,
**Then** aparecen cinco accesorios publicados de los seis sembrados, incluyendo agotado, con categoría, SKU, ilustración etiquetada, precio detalle y primer tramo,
**And** reiniciar PHP conserva seis productos/siete tramos en MySQL; tienda/modelos/tramos se crean solo para sostener este recorrido y no se usan bases ajenas.

**Given** un accesorio publicado,
**When** abre su ficha o intenta acceder a oculto/ajeno/inexistente,
**Then** el publicado muestra descripción, compatibilidad de ejemplo, disponibilidad y tabla completa; los otros responden404 sin revelar datos,
**And** el agotado se consulta pero no admite agregar cantidades positivas; identidad y contenido ficticios siempre visibles.

**Given** pantalla móvil360px o escritorio y teclado,
**When** navega catálogo/ficha,
**Then** hay h1/regiones/labels/alternativos/foco y regreso operables sin desborde,
**And** se aplica el contrato DESIGN/EXPERIENCE demo sin Node/worker residente; si el navegador no está disponible, se registra inspección visual pendiente.

Cobertura: FR-1 catálogo/publicación, FR-2, FR-10 público; NFR-1/3 local; AD-1/4/5; UX-DR1/3/5–8/14–16.

Revisión solicitada: ¿captura correctamente el requisito, cabe en una sesión y sus criterios son completos/verificables?
