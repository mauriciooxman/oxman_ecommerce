# Estado de Oxman Ecommerce

Actualizado: 2026-10-26, America/Santiago.

## Sesión más reciente

2026-10-26: Épica 3 (pedidos sin pagos reales) especificada y aprobada. Dos specs en `ready-for-dev`: [3A checkout invitado](../_bmad-output/implementation-artifacts/spec-3a-checkout-invitado.md) y [3B gestión del dueño](../_bmad-output/implementation-artifacts/spec-3b-gestion-pedidos-dueno.md). Contexto de épica compilado en [`epic-3-context.md`](../_bmad-output/implementation-artifacts/epic-3-context.md). [Nota de retoma](retoma-pedidos-epic3.md) con dependencias, entorno, validación vigente y siguiente comando BMAD. [Aceptación manual C8](retoma-pedidos-epic3.md#aceptación-manual-c8): Mauro confirma haber completado los siete pasos y que todo funcionó. El checklist antes enlazado no existe en el repositorio; no se reconstruye ni se atribuye una ejecución al agente. Panel implementado, cierre formal BMAD pendiente; 3A/3B aprobadas y aún sin implementación.

Panel privado local implementado según la especificación aprobada: autenticación y consola local, aislamiento por tienda, categorías, edición con versión, fotos privadas, publicación/archivo/restauración y stock auditado/idempotente. Migración preservó6productos/7tramos existentes. Tras tres revisores BMAD y parches:53pruebas/980aserciones,34comprobaciones HTTP real/CSRF/cookies/MySQL concurrente/router y checks Composer/rutas/Blade aprobados. [Acceso, uso y evidencia](panel-dueno-local.md). Mauro confirmó creación de su cuenta y producto con imagen; se explicó su publicación explícita. Cuenta técnica deshabilitada y material de contraseña eliminado. C8 aceptado manualmente por Mauro el 2026-10-26 según el registro enlazado; las limitaciones CUA corresponden a la evidencia histórica del agente. La spec conserva `in-review` hasta completar el cierre formal BMAD. Mauro autorizó init/commit/push a su repositorio GitHub, conservando excluidos datos y archivos privados. Los párrafos siguientes conservan el estado histórico previo a implementar el panel.

Demo existente compartida temporalmente por Cloudflare Quick Tunnel, con proceso dedicado sin depuración, MySQL ficticio existente y rutas privadas bloqueadas. [Enlace, comprobaciones y cierre](compartir-demo-temporal.md). Verificación HTTPS de catálogo/recursos/ficha/carro y descuentos5/6/23/24 aprobada. Sin hosting, dominios ni DNS modificados.

Mauro informó que probó la demo. La revisión BMAD está cerrada técnicamente: especificación `done`,27pruebas/200aserciones, Composer/rutas/Blade correctos y concurrencia HTTP real verificada. Se reparó únicamente el arnés que perdía cookies; [registro humano, evidencia y límites](cierre-demo-catalogo-carro.md). No se afirman zoom200%, lector de pantalla ni comprobaciones productivas. Los apartados históricos siguientes conservan el estado anterior y no sustituyen esta actualización.

Siguiente entrega solicitada: [panel privado del dueño para catálogo](../_bmad-output/implementation-artifacts/spec-panel-dueno-catalogo-local.md), borrador BMAD paso2 con [criterios y pruebas por riesgo](../_bmad-output/implementation-artifacts/panel-dueno/criterios-pruebas.md). Mauro resolvió las cinco decisiones: una foto JPEG/PNG/WebP≤5MiB, categorías por tienda con renombrado propagado, archivo restaurable a oculto, stock por variación y conteo físico con auditoría/idempotencia, cuentas por consola sin secretos en documentación/logs. Sin preguntas abiertas; pendiente checkpoint1 de aprobación. Solo planificación: panel exclusivamente local, sin implementar ni exponer por túnel. Sin pedidos/reservas/pagos/despacho.

## Ya realizado

- Creación del proyecto local `oxman_ecommerce`.
- Instalación de BMAD Method 6.12.4, módulo BMM y herramientas para Codex.
- Configuración del nombre del proyecto, usuario Mauro e idioma español.
- Registro de decisiones de la conversación y del rubro elegido para la demo.
- Primer borrador de definición del producto y requisitos del piloto; pendientes de revisión con Mauro.
- Referencias oficiales de plataformas existentes para evitar anunciar como exclusivas funciones comunes.
- Inicio del descubrimiento guiado de UX y arquitectura con Mauro, único desarrollador. Dirección visual solicitada: fondo claro, texto oscuro, azul para acciones, fotografías grandes y navegación sencilla; identidad configurable por comercio y OxmanIT discreto en el pie.
- Dirección tecnológica indicada por Mauro: PHP con Laravel, MySQL y Blade, tienda y administración en una aplicación para hosting compartido, procurando evitar servicios permanentes adicionales. Proveedor existente: SolucionHost; compatibilidad del plan aún no comprobada. Desarrollo y pruebas locales pueden avanzar. [Lista de comprobaciones](../_bmad-output/planning-artifacts/architecture/architecture-oxman_ecommerce-2026-10-16/requisitos-hosting.md).

Se implementó y probó una demo local de catálogo/ficha/carro. No hay pedidos, administración, pagos ni reservas implementadas. Los registros de instalación y las revisiones documentales se distinguen de las pruebas funcionales descritas abajo.

## Entregas propuestas

| Entrega | Resultado que se podrá revisar | Estado |
| --- | --- | --- |
| Definición y requisitos | Alcance, reglas comerciales, casos de aceptación y decisiones abiertas | Borradores preparados |
| Diseño y arquitectura | Pantallas de catálogo, producto, carro y administración; modelo de datos, permisos e inventario | Descubrimiento guiado iniciado; dirección visual exploratoria y alternativas de arquitectura en revisión |
| Primera compra de demostración | Catálogo y carro con descuentos por cantidad, pedido guardado y stock consistente, con datos ficticios | Pendiente |
| Administración del dueño | Acceso privado, fotos, productos, precios, movimientos y pedidos | Pendiente |
| Piloto real | Comercio seleccionado, carga autorizada, cobro y despacho definidos, pruebas y operación acompañada | Pendiente |

Los títulos expresan objetivos de entrega, no sprints aprobados ni fechas comprometidas. La descomposición en historias se hará con requisitos y arquitectura disponibles, conforme al flujo BMAD.

## Próximo trabajo

El generador oficial bmad-build ya se ejecutó con salida 0 mediante una solicitud acotada de acceso adicional, sin cambiar protecciones. Se siguieron sus pasos 1 y 2 y se preparó la [especificación de catálogo, ficha y carro local](../_bmad-output/implementation-artifacts/spec-catalogo-ficha-carro-local.md), en `in-progress`. Mauro aprobó la especificación y se implementó en `app/`; el flujo bmad-build está en implementación/verificación previa a revisión formal. [Registro de ejecución](validacion-generador-bmad-2026-10-16.md).

Revisar la [primera dirección visual](../_bmad-output/planning-artifacts/ux-designs/ux-oxman_ecommerce-2026-10-16/.working/direccion-inicial.md) y continuar la [arquitectura propuesta Laravel/MySQL/Blade](../_bmad-output/planning-artifacts/architecture/architecture-oxman_ecommerce-2026-10-16/decisiones-para-conversar.md). Los contratos DESIGN.md, EXPERIENCE.md y ARCHITECTURE-SPINE.md están finalizados solo para la entrega local; tokens/marca demo siguen como supuestos. Versiones y permisos productivos/hosting permanecen pendientes. Las respuestas de Mauro están preservadas en los nuevos memlogs.

Resolver las reglas operativas que influyen en el diseño (cobro, despacho y reserva de inventario), diseñar la experiencia y elegir la arquitectura. El borrador propone opciones para avanzar; ninguna contratación ni gasto está autorizado por esos documentos.

La primera demo usará tecnología y accesorios de celular con datos ficticios. El catálogo debe indicar compatibilidad, mostrar claramente cuándo aplica el precio mayorista y explicar si el envío está pendiente de cotización.

## Registro de las decisiones iniciales

El brief conserva nombre, audiencia, venta mixta, funciones principales, rubro elegido y uso de BMAD. Su addendum conserva el ejemplo de descuentos, reglas de datos, referencias y detalles operativos para el diseño. Los memlogs de BMAD registran decisiones y revisiones; los mensajes de instalación se consideran evidencia del entorno, no requisitos del producto. Los documentos siguen en borrador para que Mauro pueda corregir las propuestas.

## Preguntas para el comercio piloto

1. ¿Cómo recibe y cobra hoy los pedidos desde regiones?
2. ¿Descuenta stock por unidad, paquete, caja, modelo o color?
3. ¿Quién registrará las ventas presenciales y mantendrá productos y fotografías?
4. ¿Cómo calcula los descuentos y el costo del despacho?
5. ¿Qué trabajo espera ahorrar y qué pagaría por el servicio?

Estas preguntas sirven para validar el piloto; todavía no se han realizado entrevistas.


## Entrega local actual

- Implementado: Laravel13/Blade, MySQL dedicado127.0.0.1:3307 con datadir `.local/mysql/data`; seis productos ficticios y siete tramos. Catálogo/filtros/ficha y carro servidor por sesión/tienda, consolidación/edición/revalidación y despacho pendiente sin total definitivo.
- Verificado:21pruebas/135aserciones MySQL; validación/platform Composer,rutas,caché Blade; HTTP real con CSRF válido y419 sin token, precio manipulado ignorado,sesiones separadas y persistencia después de reiniciar PHP. Guardia de tests aborta antes de DDL si destino difiere de la base exclusiva.
- Guía comprobada: [probar-demo-local.md](probar-demo-local.md). Copia PHP ini local omite carga XML redundante; configuración global intacta.
- Pendiente: inspección renderizada360px/escritorio/teclado, porque no hay navegador conectado. Revisión formal bmad-build y aceptación final de la entrega a cargo del flujo padre.
- Planificación: cuatro épicas/catorce historias revisadas en lote por autorización explícita de Mauro; épicas1–2 corresponden a demo actual,3–4 a pedido/inventario/admin futuros. Ver [epics.md](../_bmad-output/planning-artifacts/epics.md) con flujo documental finalizado; no aprobación de implementación futura.

Las entregas iniciales de la tabla mantienen alcance histórico: «Primera compra» sigue pendiente porque esta demo termina en carro. Reservas,autenticación/aislamiento privados,pagos,despacho,impuestos,piloto real y compatibilidad SolucionHost no quedan resueltos por los tests locales.
