# Oxman Ecommerce

Producto de OxmanIT para ofrecer tiendas online a comercios de Meiggs, Santiago, con ventas al detalle y al por mayor, y pedidos para despacho a regiones.

## Estado

Actualización2026-10-25: panel local del dueño implementado sobre la demo existente, con sesión, productos/categorías/fotos/precios, archivo y stock auditado. Tras los parches de revisión BMAD:53pruebas/980aserciones y34comprobaciones HTTP/concurrencia/router aprobadas. [Acceso, guía, evidencia y pendientes del panel](docs/panel-dueno-local.md). Mauro confirmó creación de su cuenta y prueba de producto/foto. El cierre de aceptación y recorrido visual completo siguen pendientes. Repositorio inicial preparado por autorización de Mauro; datos, fotos cargadas y secretos locales excluidos. Los párrafos inferiores conservan el registro histórico de la demo pública; pedidos siguen fuera de alcance.

Inicio del proyecto: 16 de octubre de 2026. BMAD Method **6.12.4** instalado con el módulo BMM y 29 skills para Codex; comunicación y documentos en español. La instalación verificó la disponibilidad de `uv`.

Etapa actual: demo local implementada de catálogo, ficha y carro invitado con precios por cantidad. Laravel13/Blade y MySQL exclusivo, seis accesorios ficticios; sin pedidos, panel, pagos, reservas ni datos reales. No está publicada en hosting. UX/arquitectura se cerraron solo para esta entrega; planificación del piloto sigue en borrador.

## Empezar por aquí

- [Definición del producto](./_bmad-output/planning-artifacts/briefs/brief-oxman_ecommerce-2026-10-16/brief.md).
- [Requisitos del piloto](./_bmad-output/planning-artifacts/prds/prd-oxman_ecommerce-2026-10-16/prd.md).
- [Primera dirección visual: maqueta estática](./_bmad-output/planning-artifacts/ux-designs/ux-oxman_ecommerce-2026-10-16/.working/direccion-inicial.html).
- [Supuestos y cobertura de la maqueta](./_bmad-output/planning-artifacts/ux-designs/ux-oxman_ecommerce-2026-10-16/.working/direccion-inicial.md).
- [Arquitectura: alternativas para conversar](./_bmad-output/planning-artifacts/architecture/architecture-oxman_ecommerce-2026-10-16/decisiones-para-conversar.md).
- [Laravel/MySQL/Blade: comprobaciones de SolucionHost](./_bmad-output/planning-artifacts/architecture/architecture-oxman_ecommerce-2026-10-16/requisitos-hosting.md).
- [Estado y siguientes entregas](./docs/estado-del-proyecto.md).
- [Contexto de la conversación](./docs/intake.md).

## Decisiones confirmadas con Mauro

- Marca: **Oxman Ecommerce**. Identificador del proyecto: `oxman_ecommerce`.
- Aplicación web para celular y computador, con catálogo, carro de compras y panel privado para el dueño.
- La misma tienda puede vender al detalle, por mayor o en ambas modalidades.
- Descuentos por cantidad configurables por producto y visibles sin crear una cuenta.
- Un inventario compartido entre modalidades, descontado en unidades base.
- Productos, descripciones, fotografías, precios, existencias y pedidos administrables por el dueño.
- Base reutilizable de OxmanIT; cada comercio conserva su identidad y sus datos separados.
- Desarrollo guiado por BMAD.
- Primera demo: tecnología y accesorios de celular.

## Propuesta para el piloto (a validar)

Una sola tienda de demostración, con datos ficticios y un recorrido completo de compra. Primero se validan catálogo, precios por cantidad, pedido e inventario. Los pagos y transportistas se seleccionan antes de habilitar operaciones reales.

No se ha elegido un comercio piloto ni se ha validado disposición de pago. Las expectativas de ventas son hipótesis comerciales.

## Organización

- `_bmad/`: método y configuración instalados.
- `_bmad-output/`: documentos de análisis, planificación e implementación.
- `docs/`: contexto del negocio y registro de decisiones.

Código de demo en `app/`. [Abrir y probar la demo local](docs/probar-demo-local.md). El ejemplo incluye únicamente credenciales públicas para MySQL ficticio en loopback; `.env`, datos locales y vendor quedan excluidos. No se guardan credenciales reales ni datos de clientes.


## Demo implementada y verificación

Catálogo/búsqueda/categorías, ficha, tramos y carro editable por sesión/tienda; servidor valida cantidad/publicación/stock/precio y formularios CSRF. El carro no reserva ni descuenta existencias. Código en `app/`; CSS/SVG estáticos sin Node ni workers residentes.

21 pruebas/135 aserciones MySQL pasaron; Composer validate/platform, rutas y caché Blade correctos. HTTP real comprobó CSRF419 sin token, cálculo servidor, recarga y sesiones separadas. Reiniciar PHP conservó6productos/7tramos MySQL. Inspección visual360px/escritorio pendiente: navegador de herramientas no disponible.

[Guía local](docs/probar-demo-local.md) · [Épicas e historias](./_bmad-output/planning-artifacts/epics.md). Épicas3–4 son trabajo futuro documental; no funcionalidad existente.
