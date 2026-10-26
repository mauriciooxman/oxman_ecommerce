# Panel del dueño — catálogo local

Implementación del 2026-10-25, exclusivamente con datos ficticios. Ingreso: [http://127.0.0.1:8000/dueno/ingresar](http://127.0.0.1:8000/dueno/ingresar). El panel exige conexión y host de loopback; el router temporal sigue rechazando `/dueno` antes de Laravel. No se reactivó el túnel ni se modificó su lista permitida.

## Acceso de Mauro

Con MySQL dedicado en3307 y el servidor local preparados según [la guía de demo](probar-demo-local.md), ejecutar desde `app/`:

```powershell
$env:PHPRC = 'C:/Users/mauro/Desktop/OXMANIT/oxman_ecommerce/.local/php.ini'
php artisan owner:stores
php artisan owner:account correo-del-dueno@example.test --store=1 --role=owner
```

`owner:stores` muestra ID, nombre e identificador para elegir la tienda; la demo existente conserva tienda1. Reemplaza el correo y el ID por los correspondientes a tu cuenta ficticia. El comando pide dos veces la contraseña sin eco: mínimo12caracteres y máximo72bytes para bcrypt. No enviarla por chat, argumentos, archivos ni registros. Mauro confirmó que creó su cuenta mediante la consola; no hay credenciales predefinidas del panel. Para restablecer:

```powershell
php artisan owner:account correo-del-dueno@example.test --store=1 --role=owner --reset
```

Se exige consola interactiva, entorno local y destino MySQL ficticio permitido. El restablecimiento revoca las sesiones anteriores. No hay registro ni recuperación web.

La sesión dura la vigencia configurada (120minutos por defecto). «Salir» invalida la sesión; restablecer contraseña incrementa una versión comprobada en cada petición privada. El catálogo público no enlaza al panel.

## Uso

Prueba de Mauro,2026-10-25: confirmó creación de su cuenta y de un producto con imagen. La lectura de la demo comprobó `DEMO-VISUAL`, foto existente, oculto y stock0; catálogo HTTP sin ese SKU. Es el estado inicial previsto, no un fallo de carga. Se indicó publicar explícitamente y registrar stock para probar el carro; todavía no se afirma que Mauro haya ejecutado esas dos acciones ni aceptado la entrega.

Crear categorías antes del producto. El renombrado se refleja en productos publicados, ocultos y archivados de esa tienda. No se eliminan categorías.

Los productos nuevos quedan ocultos y con stock0. Nombre, SKU, descripción, compatibilidad, categoría, precio y tramos se guardan conjuntamente. Los tramos deben subir en cantidad y bajar en precio; ambos campos vacíos retiran un tramo. Los formularios agregan renglones disponibles hasta20. El stock se modifica solo mediante ajustes auditados. Una versión antigua del producto devuelve conflicto409 sin sobrescribir cambios recientes.

La foto principal acepta JPEG/PNG/WebP de hasta5MiB y4096×4096px. Se comprueba contenido y se recodifica a WebP sin EXIF en disco privado; solo una principal por producto. Los SVG internos anteriores se conservan. La fotografía pública usa la ficha con `?foto=1`, que vuelve a comprobar publicación/archivo; la vista privada exige dueño y pertenencia. No se creó enlace público de storage.

Publicar y ocultar cambian visibilidad. Archivar requiere confirmación y conserva información e historial; el filtro «Archivados» permite consultarlo. Restaurar deja oculto y requiere publicar explícitamente. Archivados no admiten edición, fotos ni nuevos ajustes. El carro recalcula precios y retira artículos indisponibles al recargar.

«Stock e historial» admite variación firmada o conteo físico final. Ambos exigen motivo, versión e identidad de operación; repetir una petición idéntica conserva un único efecto, incluso después de avanzar la versión. Cambiar datos de una identidad usada devuelve409. Cada movimiento guarda usuario, fecha, modo, cantidad, diferencia y antes/después; las fechas se muestran en America/Santiago. Un conteo sin diferencia también registra la verificación.

## Migración y resguardo

Antes de migrar se comprobó el respaldo local de15.112bytes `.local/backups/antes-panel-20261025.sql`. `tools/verify-owner-migration.php` aplicó migraciones incrementales y comparó todas las columnas anteriores y timestamps:6productos/7tramos íntegros,6categorías asignadas y0movimientos inventados. No se repitió seeder ni se ejecutó `migrate:fresh` sobre la demo.

La migración se niega a un rollback que destruya auditoría. Revertir exige detener escrituras, respaldar el estado posterior y revisar una restauración; la copia anterior no contiene ajustes posteriores. No se ejecutó restauración ni se afirma haberla validado.

## Evidencia ejecutada

Con PHPRC local y MySQL exclusivo: tras los parches de revisión, suite53pruebas/980aserciones aprobada (26del panel/780aserciones y27públicas/200aserciones). `composer validate --strict`, `composer check-platform-reqs`, `php artisan route:list --except-vendor` (21rutas) y `php artisan view:cache`: salida0. El arnés de34comprobaciones se repitió después de los parches y aprobó.

Revisión BMAD con tres revisores independientes y clasificación individual en la especificación. Parches verificados: enlaces de otra tienda, carrera de categorías1062, borrador de categoría, ocho orientaciones EXIF y WebP real sin metadatos, límite de72bytes al ingresar, asociación accesible de errores, leyendas de fotos, borrador seguro en409, apertura de foto antes de transmitir y ocultación mediante endpoint. Sin trabajo diferido; dos observaciones de baja incidencia rechazadas con evidencia en el registro de triage. C8 visual no se da por aprobado.

| Criterio | Evidencia ejecutada |
| --- | --- |
| C1 Sesión | `OwnerPanelTest`: ingreso bueno/malo, regeneración, límite5/minuto y recuperación, salida, roles/cuentas por consola, hash y reset. Arnés HTTP: cookies auténticas, CSRF ausente/incorrecto/valido, cookie previa revocada, expiración de archivo y versión de credencial. |
| C2 Aislamiento | Matriz de cada endpoint con dos tiendas/usuarios; IDs ajenos404, rol/tienda inválidos403, campos manipulados, fotos e historial propios. |
| C3 Catálogo/categorías | Crear/releer/editar, SKU por tienda, longitudes, texto escapado, categorías/renombrado/filtros y conflicto de versión. |
| C4 Precios/tramos | Validación y rollback, retiro de tramos, precio nuevo en carro; suite pública conserva5/6/23/24 y reducción. |
| C5 Fotos | Archivos reales JPEG/PNG/WebP, recodificación/reemplazo,5MiB exactos y +1byte, contenido falso/SVG/doble extensión/dimensiones, fallo de disco y rollback posterior a guardar foto. |
| C6 Estados | Publicado→archivado→restaurado oculto→publicado, confirmación, edición/stock bloqueados, carro/ficha/foto pública revalidados. |
| C7 Stock | Variación/conteo0/sin diferencia, auditoría e idempotencia de ambos modos, inválidos/obsolescencia y carreras reales descritas abajo. |
| C8 Interfaz/exposición | HTTP del ingreso200 y DOM de catálogo, categorías y formulario comprobados por navegador. Router: GET/POST404 en prefijo privado y variante codificada. Recorrido visual completo pendiente. |
| C9 Migración/regresión | Comparación incremental de demo y suite pública intacta; fixtures de alto riesgo únicamente en base protegida. |

`tools/verify-owner-runtime.php` ejecutó34comprobaciones: servidor de prueba local8005, MySQL127.0.0.1:3307/oxman_demo_test, cookies y CSRF reales. Dos procesos PHP/conexiones MySQL compitieron por bajas4 desde stock5: una200 y otra409, stock1 y un movimiento para esa carrera. Un conteo obsoleto esperó el bloqueo de una variación nueva y recibió409; stock3 e historial concordante. Router local8006 rechazó GET/POST privados antes de Laravel. Los servidores de prueba se detienen y sus fixtures se limpian en `finally`, solo en la base protegida; los registros temporales quedan bajo `.local/owner-runtime-*`, sin contraseñas.

Para repetir, ejecutar secuencialmente desde la raíz, sin PHPUnit simultáneo sobre la misma base:

```powershell
$env:PHPRC = 'C:/Users/mauro/Desktop/OXMANIT/oxman_ecommerce/.local/php.ini'
php tools/verify-owner-runtime.php
```

El arnés genera una contraseña solo en memoria y nunca la imprime. Requiere puertos8005/8006 libres, curl/GD/PHP y la base de pruebas ya migrada. No prepara ni usa la cuenta personal de Mauro.

## Pendientes reales y límites

El recorrido CUA comprobó ingreso y DOM de catálogo/categorías/formulario; creó la categoría ficticia «Prueba visual». La carga automatizada desde el navegador falló por permiso de la extensión; el fallback nativo terminó la sesión al no identificar la URL, y volvió a ocurrir tras la confirmación de Mauro. El flujo padre no afirma haber guardado el formulario DEMO-VISUAL ni registrado stock mediante esa interfaz. Mauro sí informó que creó su cuenta y producto con foto, y la lectura local confirmó el archivo guardado y su estado oculto inicial. Falta completar recorrido renderizado360px/escritorio/Tab/Shift+Tab/Enter y errores; la prueba humana de foto no sustituye ese recorrido. No se afirman teléfono físico, zoom200% ni lector de pantalla. Las pruebas de foto y formularios del servidor sí se ejecutaron.

El flujo padre deshabilitó la cuenta técnica de inspección (`role=null`, versión de credencial incrementada) y eliminó el material cifrado de su contraseña; conservó el registro ficticio y cualquier auditoría. La revisión/aceptación BMAD final corresponde al flujo padre. Pedidos, reservas, pagos, despacho, datos reales y hosting permanecen fuera de esta entrega.
