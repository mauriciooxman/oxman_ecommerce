# Nota de retoma — Épica 3: pedidos sin pagos reales

Generada: 2026-10-26. Para usar al inicio de una sesión nueva.

## Estado al cerrar esta sesión

| Entrega | Spec | Estado |
|---|---|---|
| Panel del dueño (catálogo) | [`spec-panel-dueno-catalogo-local.md`](../_bmad-output/implementation-artifacts/spec-panel-dueno-catalogo-local.md) | `in-review` — C8 pendiente de recorrido manual de Mauro |
| 3A Checkout invitado | [`spec-3a-checkout-invitado.md`](../_bmad-output/implementation-artifacts/spec-3a-checkout-invitado.md) | `ready-for-dev` — aprobada por Mauro 2026-10-26 |
| 3B Gestión del dueño | [`spec-3b-gestion-pedidos-dueno.md`](../_bmad-output/implementation-artifacts/spec-3b-gestion-pedidos-dueno.md) | `ready-for-dev` — aprobada por Mauro 2026-10-26 |

**Contexto de la épica compilado:** [`epic-3-context.md`](../_bmad-output/implementation-artifacts/epic-3-context.md)

## C8 del panel (pendiente — no marcar completado)

El recorrido visual 360 px / escritorio / teclado no se ha ejecutado. Mauro lo hará manualmente con el [checklist](checklist-c8-panel.md) y reportará los resultados. Solo entonces se actualiza `spec-panel-dueno-catalogo-local.md` a `done`. No cerrar como excepción aceptada sin esa confirmación.

## Dependencias entre 3A y 3B

3B **depende de 3A**. Implementar en este orden:

1. **3A primero:** crea la migración de `orders`/`order_lines`/`order_events`, columna `stock_reservado` en `products`, modelos `Order`/`OrderLine`/`OrderEvent`, `OrderService` (métodos `confirmar` y `aceptarCotizacion`), `CheckoutController`, vistas de checkout y seguimiento, rutas públicas y pruebas de concurrencia.
2. **3B después:** amplía `OrderService` con los métodos de gestión (`cancelar`, `cotizarDespacho`, `confirmarPago`, `pasarAPreparacion`, `registrarEntrega`), agrega `Owner\OrderController`, vistas del panel de pedidos y rutas privadas bajo `/dueno`.

No implementar 3B sin que la migración y los modelos de 3A existan en la base de datos.

## Entorno necesario (sin cambios respecto al panel)

Ver [probar-demo-local.md](probar-demo-local.md) para el arranque completo. Resumen:

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
mysqldump --host=127.0.0.1 --port=3307 -u oxman_user -p oxman_demo > .local/backups/antes-pedidos-3a.sql
```

La guardia de base protegida en `CheckoutTest` y `OrderManagementTest` verificará destino antes de RefreshDatabase, igual que en las pruebas existentes.

## Validación vigente antes de 3A

Las 53 pruebas / 980 aserciones del panel más las 27 públicas / 200 aserciones del carro deben pasar sin modificaciones antes de comenzar 3A. Ejecutar:

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

El generador imprimirá la ruta de `workflow.md`. Leerlo y seguirlo. El workflow detectará `spec-3a-checkout-invitado.md` como `ready-for-dev` y pasará directamente al paso de implementación (step-03).

> [!NOTE]
> Al iniciar la sesión nueva, leer primero `docs/estado-del-proyecto.md`, este archivo y `epic-3-context.md`. No repetir el descubrimiento de la conversación anterior.

## Exclusiones vigentes (no implementar en estas specs)

Pasarelas de pago · transportistas · facturación · correo automático · vencimiento automático de reservas · devoluciones · publicación por túnel · modificación del hosting.
