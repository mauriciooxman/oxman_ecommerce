# Nota de retoma — Épica 3: pedidos sin pagos reales

## Alertas automáticas del panel — 2026-11-02

Consulta cada 20 s con cursor por ID y datos mínimos, aviso destacado, contador y título de pestaña. Sonido solo tras clic explícito, prueba/silencio, coordinación localStorage/Web Locks y una señal por lote entre pestañas. Sin cambiar revisión ni estados desde el aviso; expiración detiene consultas y errores aplican backoff. Sin migración, BMAD, dependencias, hosting, commit ni push. [Uso y límites reales](avisos-pedidos-local.md#alertas-automáticas-del-panel).

Evidencia nueva: **41 pruebas Laravel / 343 aserciones**, **9 pruebas JavaScript simuladas**, sintaxis/ruta/Blade correctos. Revisión visual, sonora y varias pestañas en navegador real pendientes. Requiere panel abierto y conexión; segundo plano/suspensión/cierre del navegador pueden impedir el aviso.

## Avisos al dueño — 2026-11-02

Contador por tienda y marca Nuevo; primera apertura autorizada guarda `reviewed_at` sin cambiar estados ni inventario. Correo único por pedido, registrado en la transacción y capturado después del commit exterior con Laravel Mail/log privado. Destinatario por configuración de tienda; referencia, pago real y panel protegido, sin contacto ni token del comprador. Fallos conservan la compra y se registran con ID/código. Sin SMTP real, BMAD ni procesos adicionales. [Captura, configuración, prueba y límites de reintento](avisos-pedidos-local.md).

Respaldo nuevo restaurado/comparado antes de migrar; siete migraciones aplicadas en demo, datos previos íntegros (7 productos/9 tramos). Evidencia nueva: **65 pruebas focalizadas/691 aserciones**, **96 checks HTTP/MySQL** (43 de 3A + 48 de 3B + 5 de avisos), Blade y sintaxis. No se repitió la suite ajena al cambio; cifras inferiores son históricas. Revisión visual/aceptación manual pendientes. Sin hosting, publicación, commit ni push.

## Actualización 3B — 2026-11-02

3B implementada sobre los cambios de 3A conservados sin commit. Un solo agente; sin BMAD, workflows adicionales ni Playwright. Listado/filtro/antigüedad, detalle histórico/comprador, cotización, pago manual, preparación, salida y cancelación disponibles en `/dueno/pedidos`. Reutiliza seguimiento privado de 3A; despacho exige aceptación del comprador, retiro cero se acepta directamente.

Todas las transiciones releen y bloquean el pedido de la tienda autenticada. Entrega/cancelación bloquean productos por id: entrega descuenta físico y reserva, crea auditoría de stock e incrementa versión; cancelación solo libera reserva. Reintentos de cierre devuelven 409, transiciones inválidas 422, pedidos ajenos 404. Cancelar conserva pago/despacho registrados y exige motivo; no hay devoluciones. Se corrigió el resumen compartido para conservar la cotización al registrar despacho y se impide aceptar cotización tras cancelar.

**Evidencia nueva 3B:** **111 pruebas / 1.587 aserciones** (incluye regresión de 3A); **91 checks HTTP/MySQL** de pedidos (43 de 3A + 48 de 3B: doble cancelación, doble entrega, cierre competidor, dos pedidos sobre un producto y aceptación contra cancelación); **34 checks del panel** repetidos. Permisos, aislamiento, CSRF, validaciones, estados, precios históricos y rollback multiartículo comprobados. Rutas con middleware owner, Composer/plataforma, sintaxis y Blade correctos. No confundir con las cifras históricas inferiores.

No requiere migración nueva: consulta de solo lectura confirmó las seis migraciones y tablas de 3A en demo; no se modificaron sus datos ni se repitió respaldo/migración. Tests y arneses ejecutados secuencialmente en `oxman_demo_test`. Sin hosting, publicación, commit ni push. **Pendientes:** revisión visual actual a 360 px/escritorio/teclado y aceptación manual; no se hizo nueva inspección visual ni revisión BMAD. [Recorrido completo para probar](pedidos-local.md).

## Estado vigente — 2026-11-02

Implementación retomada por instrucción de Mauro **sin workflows ni agentes BMAD y sin Playwright**. Los apartados inferiores conservan evidencia histórica; sus instrucciones de continuar BMAD no son el siguiente paso autorizado de esta sesión.

- Git inicial limpio, HEAD `d2b8061`; se conservó la migración existente `2026_10_27_000100_create_orders.php`. Sin commit ni push. Spec 3A aprobada como referencia; 3B no iniciada.
- Guardia corregida: únicamente MySQL `127.0.0.1:3307`, bases `oxman_demo` / `oxman_demo_test`, sin URL/socket alternativos. Prueba acepta ambas y rechaza base ajena, host, puerto, driver, URL y socket antes de `up`/`down`.
- Antes de tocar la demo: cinco migraciones aplicadas, tabla orders ausente; datadir local comprobado por SELECT. Respaldo histórico `antes-pedidos-3a.sql` conservado. Nuevo `.local/backups/antes-pedidos-3a-20261102.sql` restaurado en **pruebas** y comparado por conteos/hash de todas las filas de todas las tablas; manifiesto SHA-256 junto al dump. No se imprimieron datos privados.
- Migración aplicada después de las pruebas: ahora seis migraciones; tablas de pedidos y columna de reservas presentes. Comparación posterior preservó íntegramente todos los datos previos (7 productos / 9 tramos incluidos). Sin seed ni fresh en demo.
- 3A implementada: revisión invitada, validación de contacto/entrega, pedido/líneas/evento/reserva en transacción, precios recalculados, referencia y enlace privado, aceptación idempotente de cotización. Confirmación por referencia exige la sesión emisora; seguimiento exige token y tienda correcta y omite dirección/teléfono/correo. Respuestas privadas sin caché ni referer. Snapshot e historial protegidos contra edición por modelos.
- Disponible público = físico − reservado (catálogo/ficha/carro/precios). StockService impide tanto conteo como variación por debajo de reservas bajo bloqueo. Checkout serializa por fila de tienda, después bloquea productos por id; decisión conservadora para esta demo local, sin servicios adicionales.

**Evidencia nueva de esta sesión:** suite base tras guardia **54 pruebas / 994 aserciones** (incluye las 53 previas); suite final **80 pruebas / 1.345 aserciones**. `verify-orders-runtime.php`: **43 comprobaciones** HTTP real/CSRF/cookies/privacidad y conexiones MySQL independientes: última unidad, misma clave, reservas contra ajustes/conteos, ganando checkout y ganando ajuste/conteo. `verify-owner-runtime.php`: **34 comprobaciones** existentes repetidas y aprobadas. Composer estricto/plataforma, rutas y caché Blade correctos. Estos resultados reemplazan la incertidumbre del diagnóstico de octubre; no son revisiones BMAD.

**Pendiente real:** revisión visual del nuevo checkout a 360 px/escritorio, teclado y aceptación manual de Mauro. Se intentó Computer Use; terminó al no poder verificar la URL de Chrome con suficiente confianza. No se capturó ni verificó visualmente el checkout. Sin cierre/revisión BMAD ejecutados. No hay bloqueo técnico conocido en las pruebas ejecutadas.

**Probar ahora:** [guía del checkout](probar-checkout-3a.md). Servidor local iniciado en `http://127.0.0.1:8000`; si no está activo en la siguiente sesión, usar el arranque documentado. Las reservas de demo no vencen automáticamente; cada confirmación real en demo persiste. No ejecutar `--prepare`/`--apply` otra vez sobre el respaldo ya usado; la utilidad aborta ante respaldo desactualizado. Para inspeccionar: `php tools/verify-orders-migration.php` sin opciones.

---

Generada: 2026-10-26. Para usar al inicio de una sesión nueva.

## Estado al cerrar esta sesión

| Entrega | Spec | Estado |
|---|---|---|
| Panel del dueño (catálogo) | [`spec-panel-dueno-catalogo-local.md`](../_bmad-output/implementation-artifacts/spec-panel-dueno-catalogo-local.md) | `in-review` — implementado; C8 aceptado manualmente; cierre formal BMAD pendiente |
| 3A Checkout invitado | [`spec-3a-checkout-invitado.md`](../_bmad-output/implementation-artifacts/spec-3a-checkout-invitado.md) | `ready-for-dev` — aprobada por Mauro 2026-10-26 |
| 3B Gestión del dueño | [`spec-3b-gestion-pedidos-dueno.md`](../_bmad-output/implementation-artifacts/spec-3b-gestion-pedidos-dueno.md) | `ready-for-dev` — aprobada por Mauro 2026-10-26 |

**Contexto de la épica compilado:** [`epic-3-context.md`](../_bmad-output/implementation-artifacts/epic-3-context.md)

## Aceptación manual C8

2026-10-26: Mauro confirma en este chat: «completé los siete pasos y todo funcionó». C8 aceptado por prueba manual del usuario; no es una nueva ejecución automatizada ni una comprobación visual del agente. El checklist antes enlazado no existe en el repositorio; se conserva esta declaración sin reconstruir sus pasos ni atribuir resultados individuales no informados.

El panel está implementado; la spec permanece `in-review` hasta completar el cierre formal BMAD (paso 5). Esta aceptación resuelve C8, no ejecuta ni da por cumplidos otros checkpoints. Los textos anteriores que lo indican pendiente son históricos.

## Estado real al retomar

Diagnóstico de esta sesión: Git limpio antes de esta actualización documental, sin conflictos ni implementación parcial de pedidos. Últimos commits: `086366d` (planificación de Épica 3) y `049fe60` (demo y panel). Ninguno de los 19 archivos nuevos previstos para 3A/3B existe; tampoco hay rutas de pedidos ni columna `stock_reservado` en el código/migraciones. Ambas specs siguen aprobadas y `ready-for-dev`, con implementación sin empezar. No repetir descubrimiento ni aprobación de alcance.

Comprobaciones ejecutadas en el diagnóstico: sintaxis de 44 archivos PHP correcta, 5 pruebas unitarias/10 aserciones aprobadas, Composer validate estricto y requisitos de plataforma correctos. No se ejecutaron pruebas funcionales, migraciones, caché Blade ni arneses HTTP; no se comprobó el estado de migraciones en MySQL. Esta actualización solo modifica documentación.

## Dependencias entre 3A y 3B

3B **depende de 3A**. Implementar en este orden:

1. **3A primero:** crea la migración de `orders`/`order_lines`/`order_events`, columna `stock_reservado` en `products`, modelos `Order`/`OrderLine`/`OrderEvent`, `OrderService` (métodos `confirmar` y `aceptarCotizacion`), `CheckoutController`, vistas de checkout y seguimiento, rutas públicas y pruebas de concurrencia.
2. **3B después:** amplía `OrderService` con los métodos de gestión (`cancelar`, `cotizarDespacho`, `confirmarPago`, `pasarAPreparacion`, `registrarEntrega`), agrega `Owner\OrderController`, vistas del panel de pedidos y rutas privadas bajo `/dueno`.

No implementar 3B sin que la migración de 3A esté aplicada y sus modelos implementados.

**Integración necesaria en 3A:** adaptar `app/app/Services/StockService.php` a las reservas. Actualmente valida solo stock físico entre 0 y 1.000.000; tanto variaciones como conteos deben impedir que el stock físico quede por debajo de `stock_reservado`, bajo el mismo bloqueo/transacción. Añadir pruebas de ajuste/conteo con reservas y concurrencia; no introducir reservas sin proteger los ajustes existentes.

## Entorno necesario (sin cambios respecto al panel)

Ver [probar-demo-local.md](probar-demo-local.md) para el arranque completo. PHP 8.3.16, Laravel 13/Blade y MySQL dedicado en `127.0.0.1:3307`; datadir `.local/mysql/data`. PHPUnit fuerza la base exclusiva `oxman_demo_test` y usuario `oxman_demo`; `GuardsDemoDatabase` aborta antes de RefreshDatabase si cambia el destino permitido. No usar bases de otros proyectos ni ejecutar PHPUnit y el arnés simultáneamente. Disponibilidad actual del servidor/MySQL no comprobada en el diagnóstico. Resumen:

```powershell
# MySQL dedicado debe estar corriendo en 3307 con datadir .local/mysql/data
$env:PHPRC = 'C:/Users/mauro/Desktop/OXMANIT/oxman_ecommerce/.local/php.ini'
cd C:\Users\mauro\Desktop\OXMANIT\oxman_ecommerce\app
php artisan serve          # servidor en 8000
# En otra terminal:
php artisan test --compact  # 53 pruebas / 980 aserciones deben seguir verdes antes de implementar
```

Antes de ejecutar las migraciones de 3A, hacer respaldo:

```powershell
# Desde la raíz del proyecto (ajustar ruta de mysqldump si es necesario)
mysqldump --host=127.0.0.1 --port=3307 -u oxman_demo -p oxman_demo > .local/backups/antes-pedidos-3a.sql
```

La guardia de base protegida en `CheckoutTest` y `OrderManagementTest` verificará destino antes de RefreshDatabase, igual que en las pruebas existentes.

## Validación vigente antes de 3A

Evidencia histórica total: 53 pruebas / 980 aserciones (26 del panel / 780 y 27 públicas / 200), más 34 checks HTTP/concurrencia. No sumar las 27 públicas otra vez. Antes de comenzar 3A, confirmar el destino protegido y ejecutar la suite completa; ese checkpoint sigue pendiente en esta sesión. Los comandos siguientes son para la retoma, no se ejecutaron en esta actualización:

```powershell
$env:PHPRC = 'C:/Users/mauro/Desktop/OXMANIT/oxman_ecommerce/.local/php.ini'
cd C:\Users\mauro\Desktop\OXMANIT\oxman_ecommerce\app
composer validate --strict
composer check-platform-reqs
php artisan test --compact
php artisan route:list --except-vendor
php artisan view:cache
```

Si alguna prueba falla antes de tocar código nuevo, detener y diagnosticar antes de implementar 3A.

## Siguiente comando BMAD para retomar

Leer primero `docs/solucion-uv-windows.md` (preparación de PATH/temporales). Luego ejecutar en una sola llamada a PowerShell:

```powershell
$ErrorActionPreference = 'Stop'
$bmadProjectRoot = 'C:\Users\mauro\Desktop\OXMANIT\oxman_ecommerce'
$bmadUvDirectory = 'C:\Users\mauro\AppData\Local\Microsoft\WinGet\Packages\astral-sh.uv_Microsoft.Winget.Source_8wekyb3d8bbwe'
if (-not (Test-Path -LiteralPath (Join-Path $bmadUvDirectory 'uv.exe'))) { throw 'No se encontro el ejecutable real de uv' }
if (-not (Test-Path -LiteralPath 'C:\Python312\python.exe')) { throw 'No se encontro Python' }
$bmadTemporaryDirectory = Join-Path $bmadProjectRoot '.local\bmad\temp'
$bmadCacheDirectory = Join-Path $bmadProjectRoot '.local\bmad\cache'
New-Item -ItemType Directory -Force -Path $bmadTemporaryDirectory,$bmadCacheDirectory | Out-Null
$env:PATH = $bmadUvDirectory + ';' + $env:PATH
$env:TEMP = $bmadTemporaryDirectory
$env:TMP = $bmadTemporaryDirectory
$env:TMPDIR = $bmadTemporaryDirectory
$env:UV_CACHE_DIR = $bmadCacheDirectory
$env:UV_PYTHON = 'C:\Python312\python.exe'
$env:UV_PYTHON_DOWNLOADS = 'never'
uv run --no-cache "C:/Users/mauro/Desktop/OXMANIT/oxman_ecommerce/_bmad/scripts/render_skill.py" --project-root "C:/Users/mauro/Desktop/OXMANIT/oxman_ecommerce" --skill "C:/Users/mauro/Desktop/OXMANIT/oxman_ecommerce/.agents/skills/bmad-build"
if ($LASTEXITCODE -ne 0) { throw ('Generador BMAD fallo: ' + $LASTEXITCODE) }
```

Siguiente paso exacto: retomar `bmad-build` para registrar el cierre formal del panel con esta aceptación C8; después continuar con `spec-3a-checkout-invitado.md`, ya aprobada y `ready-for-dev`. Leer el `workflow.md` generado y conservar sus checkpoints: verificar la suite base protegida, respaldar la demo antes de migrar y seguir la implementación/revisión/aceptación requeridas. No saltar automáticamente al paso 3 ni repetir el descubrimiento aprobado. Si uv devuelve acceso denegado, detenerse y seguir el diagnóstico mínimo/permisos de `docs/solucion-uv-windows.md`; no repetir el generador ni sustituirlo.

> [!NOTE]
> Al iniciar la sesión nueva, leer primero `docs/estado-del-proyecto.md`, este archivo y `epic-3-context.md`. No repetir el descubrimiento de la conversación anterior.

## Exclusiones vigentes (no implementar en estas specs)

Pasarelas de pago · transportistas · facturación · correo automático · vencimiento automático de reservas · devoluciones · publicación por túnel · modificación del hosting.
