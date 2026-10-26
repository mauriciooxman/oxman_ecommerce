# Arranque de BMAD en Windows

## Generador validado con solicitud acotada de acceso

El 2026-10-16, tras autorización específica de Mauro, el chat de implementación solicitó `require_escalated` para la preparación en línea y el comando oficial del generador bmad-build. Terminó con salida 0 y se leyó el workflow devuelto. Ver [salida y avance del flujo](validacion-generador-bmad-2026-10-16.md). Esto valida esa ejecución con acceso adicional; no resuelve el lanzamiento dentro del contexto restringido ni concede permisos persistentes a otros comandos. Los estados anteriores se conservan como historial.

## Diagnóstico mínimo confirmado en el chat de implementación

El 2026-10-16 se probó únicamente el ejecutable físico con `--version`. Bajo PowerShell 5.1 e identidad `DESKTOP-P30RRUH\CodexSandboxOffline` devolvió «Acceso denegado». El mismo comando mediante el mecanismo `require_escalated` del ejecutor respondió `uv 0.12.23` con salida 0. La diferencia por contexto de ejecución está comprobada; el control concreto que deniega acceso sigue sin identificar. Ver [evidencia y solicitud mínima](diagnostico-uv-permisos-2026-10-16.md).

No se repitió el generador BMAD ni se modificaron protecciones. La comprobación de versión con acceso adicional no equivale a verificar el generador o sus procesos hijos. El estado anterior y los procedimientos siguientes se conservan como historial; no son una reparación validada del arranque completo en este chat.

## Estado actual: bloqueo pendiente en el chat de implementación

El chat de implementación ejecutó la preparación en línea y recibió `uv.exe: Acceso denegado`; registro en `diagnostico-uv-inline-2026-10-16.md`. **Los procedimientos de abajo NO están validados en ese entorno y no deben repetirse como si fueran una reparación confirmada.** En el chat de diagnóstico, el ejecutable físico sigue respondiendo a `--version`. La diferencia entre entornos está comprobada; su causa exacta no.

Siguiente diagnóstico mínimo en el chat afectado: identificar su ejecutor, versión de PowerShell, identidad, directorio y ruta física de uv; probar únicamente `uv --version` con la ruta física. Si se deniega el acceso, usar el mecanismo admitido de solicitud de permisos de ese chat para el acceso mínimo requerido antes de reintentar. No copiar binarios, cambiar ACL, políticas ni configuración de sandbox para sortear el bloqueo. No modificar el SKILL.md ni ejecutar sus fuentes directamente. Este chat de diagnóstico no puede conceder permisos al otro chat.

La lectura del historial detallado del otro chat no estuvo disponible (`thread/items/list is not supported yet`); no se afirma haber inspeccionado su configuración completa. La documentación oficial explica que los comandos y sus procesos hijos están sujetos al perfil de permisos: [Windows sandbox](https://learn.chatgpt.com/docs/windows/windows-sandbox). Atribuir este caso al sandbox sigue siendo una hipótesis, no un diagnóstico confirmado.

## Resultado comprobado

El usuario informó que otro chat recibía «No hay ninguna aplicación asociada con el archivo especificado para esta operación» al iniciar `uv.exe`. En este chat no se reprodujo ese mensaje: `uv --version` respondió correctamente a través de la ruta WinGet/Links.

Sí se reprodujo un segundo problema al ejecutar el generador: `uv` falló al consultar Python porque no pudo persistir un archivo temporal en el directorio asignado por el entorno de ejecución (acceso denegado, error 5).

Se encontró el ejecutable real de uv en el paquete instalado por WinGet. La combinación de ese directorio primero en PATH, Python instalado explícito y temporales dentro del proyecto permitió ejecutar el generador oficial `render_skill.py` de bmad-build con código de salida 0. Este resultado verifica el arranque local; no prueba una reparación global de Windows ni una ejecución desde el otro chat.

## Uso por Codex

La carga del helper `.ps1` falló en el otro chat porque su proceso no permite ejecutar archivos de script. Por tanto, **la ruta preferida ahora es preparar el entorno con comandos en línea**, sin dot-source, sin `Invoke-Expression` y sin cambiar políticas. El helper se conserva como referencia; no se necesita para continuar.

Ejecutar todo el bloque siguiente como el texto de una única llamada a la herramienta de terminal de PowerShell, no guardarlo como `.ps1`. Las variables de una llamada pueden no conservarse en la siguiente. Las rutas de uv y Python corresponden a instalaciones comprobadas en este equipo; si cambian, detenerse e identificar la instalación nueva.

Ejemplo para bmad-build, sin cambiar el directorio actual:

```powershell
$ErrorActionPreference = 'Stop'
$bmadProjectRoot = 'C:\Users\mauro\Documents\Codex\2026-10-16\mi\outputs\oxman_ecommerce'
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
uv run --no-cache "C:/Users/mauro/Documents/Codex/2026-10-16/mi/outputs/oxman_ecommerce/_bmad/scripts/render_skill.py" --project-root "C:/Users/mauro/Documents/Codex/2026-10-16/mi/outputs/oxman_ecommerce" --skill "C:/Users/mauro/Documents/Codex/2026-10-16/mi/outputs/oxman_ecommerce/.agents/skills/bmad-build"
if ($LASTEXITCODE -ne 0) { throw ('Generador BMAD fallo: ' + $LASTEXITCODE) }
```

La preparación en línea no ejecuta una fuente del workflow. El comando del generador conserva sus argumentos. Tras un resultado correcto, leer y seguir el `workflow.md` que indique esa ejecución. Si falla, conservar el error y detener ese flujo según SKILL.md; no ejecutar una fuente de workflow ni sustituir uv por Python directamente. `$ErrorActionPreference = 'Stop'` evita ejecutar uv si la preparación produce un error.

Esta variante terminó con código 0 en este chat, con PowerShell 7.6.5 y política efectiva RemoteSigned. No se probó desde el otro chat ni se afirma haber cambiado su política. Microsoft documenta que Restricted permite comandos individuales aunque impide archivos de script; véase [políticas de ejecución](https://learn.microsoft.com/en-us/powershell/module/microsoft.powershell.core/about/about_execution_policies).

## Alcance del ajuste

- Cambia variables del proceso actual, sin registro de Windows ni PATH global.
- Prioriza el ejecutable real del paquete WinGet cuando está disponible.
- Usa `.local/bmad/temp` para TEMP, TMP y TMPDIR, y `.local/bmad/cache` para UV_CACHE_DIR.
- Usa el Python y el ejecutable real de uv comprobados en este equipo, y desactiva descargas automáticas de intérpretes en esa invocación.
- `.local/` queda excluido del control de versiones.

No se modificaron los SKILL.md, las fuentes del workflow, PHP, Composer, MySQL ni el hosting. No se inició la implementación de la tienda desde este chat.

Referencia: [variables de entorno oficiales de uv](https://docs.astral.sh/uv/reference/environment/). La validez de esta solución se comprobó ejecutando uv y el generador local, no solo mediante la documentación.
