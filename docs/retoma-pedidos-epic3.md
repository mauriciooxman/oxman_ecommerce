# Nota de retoma — Épica 3: pedidos sin pagos reales

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
