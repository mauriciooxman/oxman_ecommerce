# Probar la demo local

Actualización2026-10-25: cierre técnico registrado en [cierre-demo-catalogo-carro.md](cierre-demo-catalogo-carro.md), con27pruebas/200aserciones y concurrencia real comprobada. La inspección360px/escritorio/teclado ya se realizó por emulación; zoom real200% y lector de pantalla siguen sin verificar. Las menciones inferiores a21pruebas y navegador no conectado son evidencia histórica del2026-10-16.

Demo ficticia de catálogo, ficha y carro; no registra pedidos, reservas, pagos ni datos personales. Código en `app/`; MySQL dedicado en `.local/mysql/data`. No usa el hosting ni el sitio anterior. Verificado 2026-10-16 con PHP8.3.16, Composer2.6.5 y MySQL8.0.30 instalados en este equipo; no fija versiones productivas.

## Abrir la instalación ya preparada

En PowerShell, desde la raíz del proyecto:

```powershell
$demoRoot = (Get-Location).Path
$env:PHPRC = Join-Path $demoRoot '.local/php.ini'
$demoData = Join-Path $demoRoot '.local/mysql/data'
```

Si MySQL de la demo no está funcionando, comprobar primero el puerto y el proceso. No arrancar Laragon ni servicios anteriores para esta demo:

```powershell
Get-NetTCPConnection -LocalPort 3307 -State Listen -ErrorAction SilentlyContinue
Get-CimInstance Win32_Process -Filter "name='mysqld.exe'" | Select-Object ProcessId,CommandLine
```

La instancia correcta tiene datadir dentro de este proyecto, puerto3307 y bind127.0.0.1. Si el puerto pertenece a otro proceso/instancia, detener este procedimiento y elegir un puerto aislado junto con la configuración/guardia de tests; no conectarse a esa base.

Si no hay instancia en3307 y existe el datadir preparado:

```powershell
Start-Process -FilePath 'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqld.exe' -ArgumentList '--no-defaults','--basedir=C:/laragon/bin/mysql/mysql-8.0.30-winx64',"--datadir=$demoData",'--port=3307','--bind-address=127.0.0.1','--mysqlx=OFF','--log-error=demo-error.log','--pid-file=demo.pid' -WindowStyle Hidden
```

Abrir el servidor de la demo en la terminal actual (Ctrl+C lo detiene; MySQL conserva los datos):

```powershell
Set-Location app
php artisan serve --host=127.0.0.1 --port=8000
```

Abrir [http://127.0.0.1:8000](http://127.0.0.1:8000). No hace falta npm, Vite, cola, Redis ni cron. Un servidor ya abierto ocupa8000: reutilizarlo o detener únicamente su proceso antes de iniciar otro.

## Instalación nueva en este mismo equipo

No repetir inicialización sobre `.local/mysql/data` existente. Los comandos siguientes solo son para crear una instancia nueva dedicada; no apuntarlos a directorios de otras bases.

1. Instalar dependencias con `composer install` en `app/`, usando el lock incluido. No usar `composer create-project` otra vez.
2. Preparar configuración PHP local sin modificar `php.ini` global. XML ya está incorporado; la instalación global contiene una carga redundante `extension=xml` que genera advertencia. Desde raíz:

```powershell
$demoRoot = (Get-Location).Path
New-Item -ItemType Directory -Force (Join-Path $demoRoot '.local') | Out-Null
$demoIni = Join-Path $demoRoot '.local/php.ini'
$ini = [IO.File]::ReadAllText('C:/laragon/bin/php/php-8.3.16-Win32-vs16-x64/php.ini')
$ini = [regex]::Replace($ini, '(?m)^extension\s*=\s*xml\s*\r?$', '; XML incorporado: carga redundante omitida solo en copia local.')
[IO.File]::WriteAllText($demoIni, $ini, (New-Object Text.UTF8Encoding($false)))
$env:PHPRC = $demoIni
php -r "echo 'XML='.(int)extension_loaded('xml');"
```

Resultado esperado XML=1 sin advertencia. Esto solo selecciona una copia local para este proceso y sus hijos; al abrir otra terminal se debe establecer PHPRC nuevamente.

3. Verificar puerto3307 libre como arriba. Solo si NO existe datadir:

```powershell
$demoData = Join-Path $demoRoot '.local/mysql/data'
if (Test-Path -LiteralPath $demoData) { throw 'El datadir ya existe: no reinicializar.' }
New-Item -ItemType Directory -Force $demoData | Out-Null
& 'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqld.exe' --no-defaults --initialize-insecure --basedir=C:/laragon/bin/mysql/mysql-8.0.30-winx64 "--datadir=$demoData" --console
if ($LASTEXITCODE -ne 0) { throw 'Inicialización fallida: revisar salida antes de seguir.' }
```

Arrancar con Start-Process de la sección anterior. Inicialización crea root vacío SOLO en esta instancia; configurar inmediatamente bases/usuario y clave root de demostración:

```powershell
mysql --protocol=TCP --host=127.0.0.1 --port=3307 --user=root --execute="CREATE DATABASE oxman_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE oxman_demo_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER 'oxman_demo'@'localhost' IDENTIFIED BY 'demo-local-only'; GRANT ALL ON oxman_demo.* TO 'oxman_demo'@'localhost'; GRANT ALL ON oxman_demo_test.* TO 'oxman_demo'@'localhost'; ALTER USER 'root'@'localhost' IDENTIFIED BY 'demo-local-root-only';"
```

Son credenciales públicas de muestra para loopback ficticio, nunca para servicios reales. Usuario de aplicación solo tiene permisos en las dos bases demo.

4. En `app/`, copiar `.env.example` a `.env` únicamente si no existe. Revisar DB_CONNECTION=mysql,127.0.0.1,3307,oxman_demo y usuario demo. No reutilizar credenciales/bases de otra aplicación.

```powershell
Set-Location (Join-Path $demoRoot 'app')
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Seeder vuelve a poner valores ficticios originales; no usarlo para mantener un catálogo real. No ejecutar migrate:fresh en la base demo: ese comando solo lo usa el entorno de tests protegido.

## Recorrido manual

1. Buscar «Cable» o «DEMO-C1», filtrar Cables, abrir Cable USB-C1metro. Cinco productos publicados; ocultoDEMO-H1 no aparece y su ficha responde404. Cable2metros aparece Sin stock.
2. Agregar5 unidades del cable1metro: $3.000c/u, subtotal$15.000. Agregar1más consolida a6: $2.500c/u, $15.000; faltan18 para24 a$2.000c/u.
3. Actualizar a23: $57.500. Actualizar a24: $48.000. Bajar a5 recupera$3.000c/u. Otros productos no suman para descuento del cable.
4. Probar0,negativo,fracción,texto o41: rechazo y carro válido conservado. El navegador puede bloquear entradas; las mismas reglas también se prueban enviando HTTP al servidor.
5. Recargar mantiene carro; ventana privada nueva queda vacía. Quitar última línea informa vacío y permite volver. Subtotal nunca incluye envío ni constituye total definitivo.
6. Detener/reiniciar PHP mantiene catálogo MySQL y sesión de archivo dentro de su vigencia. Expirar/borrar cookies o limpiar sesiones sí elimina continuidad de ese navegador.
7. Revisar a360px, escritorio, Tab/Shift+Tab/Enter y zoom200%: etiquetas, foco, errores y tabla visibles sin desplazamiento horizontal. Esta inspección renderizada no pudo ejecutarse aquí por navegador no conectado; queda para revisión humana.

## Verificaciones automatizadas

Desde `app/`, con MySQL exclusivo activo y PHPRC local:

```powershell
composer validate --strict
composer check-platform-reqs
php artisan test
php artisan route:list
php artisan view:cache
```

21 pruebas y135 aserciones pasaron. Tests usan oxman_demo_test; guardia previa a RefreshDatabase verifica driver,host,puerto,base y ausencia de URL/socket alternativos antes de DDL. Una prueba altera la configuración a base_ajena y demuestra que el método de DDL nunca se invoca. No cambian oxman_demo.

Cubren matriz5/6/23/24, reducir, consolidar/no sumar SKU, entradas inválidas, oculto/agotado/ajeno, precio manipulado, cambios de precio/stock/publicación, siguiente tramo alcanzable, vacíos y sesiones por tienda.

Prueba HTTP real ejecutada: ficha200, POST con CSRF agrega6 e ignora unit_price=1 (subtotal$15.000), recarga conserva carro, cliente sin cookie vacío, POST sin token devuelve419, CSS200. Reinicio PHP comprobado:6productos/7tramos permanecen MySQL y catálogoHTTP200. No equivale a pruebas de reservas, pedidos ni aislamiento privado productivo.

## Límites pendientes

Inspección visual renderizada360px/escritorio y lector de pantalla; selección piloto/marca/fotos; permisos y autenticación; aislamiento privado productivo; reservas/concurrencia/idempotencia de pedidos; pago/despacho/impuestos; hosting, versiones mantenidas y respaldo/restauración. Ningún dato real ni contratación autorizada por esta demo.
