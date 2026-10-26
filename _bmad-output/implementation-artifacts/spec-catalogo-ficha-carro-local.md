---
title: 'Catálogo, ficha y carro local'
type: 'feature'
created: '2026-10-16'
status: 'done'
baseline_commit: 'NO_VCS'
route: 'dispatch'
review_loop_iteration: 0
context: []
---

<frozen-after-approval reason="human-owned intent">

## Intent

**Problema:** la maqueta no permite probar selección de accesorios ni precios por cantidad.

**Propuesta:** Laravel 13, MySQL y Blade locales: catálogo ficticio persistente, ficha y carro invitado. Documentar diseño/arquitectura de esta entrega y ordenar historias posteriores sin cerrar sus decisiones pendientes.

## Boundaries & Constraints

**Siempre:** español, CLP y cantidades enteras; tramo para todas las unidades del mismo SKU. Servidor valida publicación, cantidad, stock y precio; formularios CSRF. Carro por sesión/tienda, sin reservar existencias. Diseño claro/azul, marca del comercio, imágenes ilustradas y datos ficticios explícitos.

**Nunca:** modificar hosting, sitio anterior, bases ajenas, políticas o ACL; cobros, pedidos, administración o datos reales. Compatibilidad SolucionHost, reservas y permisos productivos permanecen pendientes.

## I/O & Edge-Case Matrix

| Estado/entrada | Resultado requerido |
| --- | --- |
| Cable: 5/6/23/24 unidades | $15.000/$15.000/$57.500/$48.000; precio unitario $3.000/$2.500/$2.500/$2.000 |
| Reducir cantidad | Recuperar tramo correspondiente |
| Agregar mismo SKU | Consolidar; otros SKU no suman para descuentos |
| Cero/negativo/fracción/texto/exceso | Rechazar, explicar y conservar carro válido |
| Producto oculto/agotado | Oculto:404; ambos impiden agregar |
| Precio manipulado | Ignorar importe cliente; calcular desde DB |
| Catálogo cambia | Revalidar carro e informar cambio/indisponibilidad |
| Seis cables, stock suficiente | Faltan 18 para tramo $2.000; omitir sugerencias inalcanzables |
| Búsqueda/carro vacíos | Mensaje y regreso; despacho pendiente, sin total definitivo |

</frozen-after-approval>

## Code Map

Baseline histórico: no existía aplicación ni Git. Implementación actual en `app/`: skeleton oficial Laravel13.0.0, framework13.37.0, PHP8.3.16 y MySQL8.0.30 dedicado127.0.0.1:3307, datadir `.local/mysql/data`. XML incorporado comprobado; copia `.local/php.ini` omite carga redundante sin cambiar global. Composer descargó dependencias oficiales y generó lock; no se afirma instalación offline.

Fuentes bajo `_bmad-output/planning-artifacts/`: `prds/prd-oxman_ecommerce-2026-10-16/prd.md` (FR-1–4 y bases FR-5/10); `ux-designs/ux-oxman_ecommerce-2026-10-16/.working/direccion-inicial.html`; `architecture/architecture-oxman_ecommerce-2026-10-16/requisitos-hosting.md`. Conservar borradores y memlogs.

## Tasks & Acceptance

**Ejecución, por dependencia:**

- [x] `app/composer.json`, `app/composer.lock`, `app/.env.example`: inicializar Laravel oficial y configuración local sin workers residentes.
- [x] `_bmad-output/planning-artifacts/ux-designs/ux-oxman_ecommerce-2026-10-16/`: ejecutar BMAD UX y completar DESIGN.md/EXPERIENCE.md para esta entrega.
- [x] `_bmad-output/planning-artifacts/architecture/architecture-oxman_ecommerce-2026-10-16/ARCHITECTURE-SPINE.md`: ejecutar BMAD arquitectura, fijar fronteras locales y diferir operación real.
- [x] `_bmad-output/planning-artifacts/epics.md`: descomposición BMAD; catálogo/ficha, precios/carro, pedido/inventario y administración, con dependencias.
- [x] `app/database/migrations/`, `app/database/seeders/DatabaseSeeder.php`, `app/app/Models/`: tienda, productos, tramos, SKU único por tienda; seis accesorios, incluyendo oculto/agotado.
- [x] `app/app/Services/PricingService.php`, `app/app/Services/CartService.php`, `app/app/Http/Controllers/`, `app/routes/web.php`: búsqueda/filtros, ficha y agregar/editar/quitar con validación servidor.
- [x] `app/resources/views/`, `app/public/css/`, `app/public/images/`: Blade móvil, etiquetas, errores y explicación del precio.
- [x] `app/tests/Unit/PricingTest.php`, `app/tests/Feature/StorefrontTest.php`: matriz, sesiones independientes y rechazo de SKU ajeno.
- [x] `docs/probar-demo-local.md`, `README.md`, `docs/estado-del-proyecto.md`: pasos comprobados para abrir/probar; distinguir implementado y pendiente.

**Aceptación:**

- Dado catálogo sembrado, cuando reinicia la aplicación, entonces conserva productos/tramos en MySQL.
- Dada sesión, cuando recarga, entonces conserva el carro y recalcula precios en servidor.
- Dada pantalla de 360 px, cuando navega con teclado, entonces controles etiquetados y sin desborde horizontal.
- Dadas instrucciones, cuando Mauro sigue los pasos, entonces abre la demo y verifica límites 5/6/23/24 sin datos reales.

## Implementation Notes

- 2026-10-25: Mauro informa que probó la primera demo. Registro humano y cierre técnico, con límites no comprobados, en [cierre-demo-catalogo-carro.md](../../docs/cierre-demo-catalogo-carro.md). No se infieren casos humanos ni verificación productiva.

- Mauro aprobó la especificación y ordenó implementar; confirma MySQL local exclusivo, sin reutilizar bases ajenas. El 2026-10-16 se verificó ausencia de Git: baseline NO_VCS. Hosting y sitio anterior fuera de alcance. Los agentes deben leer AGENTS.md y conservar la preparación de uv documentada para comandos BMAD; no sustituir uv por Python para sortear su lanzador.

## Spec Change Log

## Review Triage Log

Revisión formal 2026-10-25: tres capas ejecutadas, sin omisiones. Baseline NO_VCS; diff de mejor esfuerzo desde vacío de fuentes propias/configuración/pruebas (sin vendor, datos ni secretos). Correcciones localizadas, sin reconstruir la demo.

| ID | Hallazgo | Veredicto y evidencia | Ruta |
| --- | --- | --- | --- |
| BH1 | Escrituras de sesión concurrentes | medium: StartSession guarda la sesión completa y session.block estaba ausente; dos peticiones pueden guardar versiones distintas. Activar bloqueo nativo con archivos locales. | patch |
| BH2 | Buscar cero omite filtro | medium: la condición PHP convierte '0' a falso en index; comparar con cadena vacía. | patch |
| BH3 | Error de cantidad aparece en todas las líneas | medium: cada input usa el mismo error quantity sin distinguir SKU. Asociar bolsa de errores al producto. | patch |
| BH4 | Catálogo anuncia tramo inalcanzable | low: DEMO-C2 muestra desde seis con stock cero. Omitir anuncio cuando el umbral excede existencias. | patch |
| BH5 | Ficha marca precio detalle como aplicado | low: la fila estática dice Aplicado incluso conservando otra cantidad tras un error. Identificarla como precio de referencia. | patch |
| BH6 | Seeder conserva tramos retirados de su fuente | low: el seeder actual es repetible; el caso exige editar después el arreglo de tramos. No se necesita para revisar la demo existente; sincronizar borrados añade comportamiento innecesario ahora. | rechazado, caso infrecuente y corrección no directa |
| BH7 | Test de sesiones no crea dos visitantes | low: limitación real del test con withSession; la evidencia HTTP previa sí comprobó clientes con/sin cookies. Se conserva esa evidencia; no hay defecto de aislamiento demostrado. | rechazado, ampliar arnés no necesario con evidencia vigente |
| BH8 | Test CSRF solo busca campos | medium: Laravel omite CSRF en testing; la evidencia HTTP previa es válida pero una exclusión futura no fallaría en suite. Agregar cobertura con middleware activo. | patch, grupo VG2 |
| EC1 | Cero en búsqueda/categoría omite filtro | medium: ambas condiciones usan truthiness; incluye categoría '0' además de BH2. | patch, grupo BH2 |
| EC2 | Carrera al guardar carro | medium: mismo defecto comprobado en StartSession y CartService que BH1. | patch, grupo BH1 |
| EC3 | Error repetido en líneas válidas | medium: mismo uso compartido de quantity que BH3. | patch, grupo BH3 |
| VG1 | Categoría no probada independientemente | medium: evidencia del revisor; buscar Cable ya excluye cargadores y enmascara un filtro roto. | patch |
| VG2 | Falta rechazo CSRF automatizado | medium: evidencia del revisor; campos HTML no garantizan rechazo ni conservación del carro. | patch |
| VG3 | Falta desbordamiento por acumulación | medium: evidencia del revisor; 40+1 no se ejecuta en la suite y podría convertirse en error 500 si se altera el guard. | patch |
| UI1 | Texto singular incorrecto | low: navegador mostró 1 productos encontrados y Te faltan 1 unidades. Corrección directa. | patch |

Resolución final2026-10-25: todos los grupos patch aplicados y verificados; sin diferidos nuevos. El fallo del arnés de errores se resolvió conservando cookies/ID de sesión, sin relajar expectativas. Suite27/200, Composer/rutas/Blade salida0; concurrencia HTTP real16altas/una sesión/dos procesos sin pérdida. BH6/BH7 conservan rechazo y evidencia. Véase el informe de cierre; zoom200% y lector de pantalla no comprobados.

## Design Notes

Aplicación en `app/`; CSS estático sin Node residente. MySQL dedicado local; no iniciar servicios existentes ni resetear bases ajenas. Solicitar acceso puntual si hace falta; registrar limitaciones. Versiones finales del hosting siguen abiertas. Referencia técnica consultada: https://laravel.com/docs/13.x/deployment.

## Verification

En `app/`: `composer validate --strict`, `composer check-platform-reqs`, `php artisan test`, `php artisan route:list`, `php artisan view:cache`; exigir éxito. Integración con MySQL dedicado. Inspección visual a 360 px/escritorio si hay navegador; comprobar HTTP, formularios y persistencia, informando lo no ejecutado.


### Evidencia de implementación (2026-10-16)

- Código app con migración/modelos tienda-productos-tramos, seed6accesorios/7tramos, servicios servidor y Blade/CSS/SVG locales. Carro no reserva, no pedido/panel/pago.
- MySQL dedicado demo/test, usuario limitado; guardia previa a RefreshDatabase rechaza destino distinto antes de DDL y tiene test.
- 21pruebas/135aserciones pasaron en MySQL; `composer validate --strict`, `composer check-platform-reqs`, `php artisan route:list` y `php artisan view:cache` exitosos. HTTP real: token válido agrega6cables=$15.000 ignorando precio cliente; sin token419, sesión separada vacía,recarga persistente, array rechazado retorna ficha200 con error.
- Reiniciar PHP conservó6productos/7tramos MySQL y catálogoHTTP200. Sin cambios a hosting,sitio anterior,servicios existentes,políticas ni ACL.
- UX/arquitectura cerradas para demo; arquitectura lint0hallazgos y revisiones rubric/tecnología/adversarial. Épicas4/historias14 aprobadas como documento,3–4futuras; revisión por lote autorizada y registrada en memlog.
- Guía [probar-demo-local.md](../../docs/probar-demo-local.md). Inspección visual renderizada360px/escritorio no ejecutada: CUA sin navegadores y abrir Chrome devuelve Browser is not available. Revisión formal build/auditoría final pendiente en agente padre.
