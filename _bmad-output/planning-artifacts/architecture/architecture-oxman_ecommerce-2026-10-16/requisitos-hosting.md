# Comprobación de SolucionHost

2026-10-16. **Compatibilidad del plan: pendiente.** Podemos continuar diseño, desarrollo y pruebas locales; esto condiciona el despliegue y el cierre de versiones.

Mauro propone **PHP + Laravel + MySQL + Blade**, tienda y administración en una aplicación para hosting compartido, procurando evitar servicios permanentes adicionales. No se han fijado versiones ni contratado servicios.

## Evidencia pública y límites

La [página oficial de SolucionHost](https://www.solucionhost.cl/web-hosting/) anuncia cPanel, PHP, MySQL, cron y respaldos diarios. La tabla dice «PHP 5 y Posterior»: no identifica la versión actual, extensiones ni PHP CLI. No demuestra acceso SSH, Composer, raíz pública configurable ni límites de ejecución. Son prestaciones declaradas; falta identificar el plan contratado y verificar la cuenta.

Mauro señala https://tjservicioshm.cl/ como proyecto anterior allí. La herramienta web no pudo recuperar su contenido; eso no demuestra caída ni incompatibilidad. No se han inspeccionado su cuenta, archivos o base de datos.

## Lista breve para panel o soporte

Referencia inicial: **Laravel 13 como candidato**, no versión adoptada. Registrar valor, evidencia y fecha para cada punto.

| Punto | Qué comprobar en el plan | Estado actual |
| --- | --- | --- |
| PHP | Para Laravel 13, PHP ≥8.3 en web y CLI, extensiones oficiales y `pdo_mysql`. Con el proyecto real, comprobar dependencias mediante `composer check-platform-reqs --no-dev`. | PHP anunciado; versiones/extensiones pendientes |
| MySQL | Versión mantenida, InnoDB, transacciones, claves foráneas y bloqueo de filas; permisos para migraciones, cuota y límite de conexiones. | Anunciado; detalles/prueba pendientes |
| Publicación | Raíz en `public/` o disposición equivalente con código, `.env` y `vendor` fuera del espacio público; reescritura a `public/index.php`; HTTPS y renovación. | Pendiente |
| Despliegue | SSH/terminal o alternativa CLI soportada para Artisan, migraciones y cachés con PHP correcto. Composer puede ejecutarse fuera del hosting en entorno compatible. No exponer comandos mediante URL. | Pendiente |
| Fotos/archivos | Escritura en `storage` y `bootstrap/cache`; publicar solo fotos autorizadas, por enlace permitido o alternativa segura; límite de subida, disco/inodos y `post_max_size`. GD/Imagick solo si transformamos imágenes. | Pendiente |
| Recursos/cron | Memoria PHP, CPU, procesos, tiempo máximo y conexiones; ruta PHP CLI, frecuencia mínima y duración del cron. | Cron anunciado; límites pendientes |
| Sesión/correo | Sesiones persistentes y cookies seguras; para recuperación/notificaciones por correo, SMTP con TLS, cupo y prueba de entrega. | SMTP anunciado; configuración/prueba pendientes |
| Respaldo/pruebas | Retención y restauración de DB y fotos, costo si existe; subdominio/base aislados sin afectar el sitio anterior. | Respaldo diario anunciado; detalles/prueba pendientes |

Extensiones oficiales de Laravel 13: Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO, Session, Tokenizer y XML. No sustituye comprobar las dependencias adicionales elegidas. [Requisitos y despliegue](https://laravel.com/docs/13.x/deployment).

No recomendar MySQL sin mantenimiento solo porque el framework acepte esa versión; registrar la ofrecida y comprobar su ciclo de soporte antes de fijarla. [Compatibilidad de bases de datos](https://laravel.com/docs/13.x/database).

## Propuesta sin procesos permanentes adicionales

Blade renderizado por PHP en peticiones normales; compilar CSS/JS localmente y subir artefactos, sin mantener el servidor de desarrollo Vite en producción. [Vite](https://laravel.com/docs/13.x/vite).

[ASSUMPTION] Sesiones y caché en archivos o MySQL según límites comprobados. Sin dependencia inicial de Redis, WebSockets, Octane, contenedores, servidor Node ni workers residentes. Pedido y reserva se confirman en una transacción; un fallo de correo no debe duplicar el pedido ni alterar la reserva confirmada. Notificaciones pendientes de diseño.

Cron no es necesario para atender peticiones. Comprobarlo para mantenimiento y tareas acordadas: el scheduler habitual puede usar cron cada minuto, sin daemon propio. Ajustar las tareas a los límites reales y no adoptar vencimiento automático de reservas sin acordar política y comprobar ejecución fiable. [Scheduler](https://laravel.com/docs/13.x/scheduling).

## Cierre de comprobación

Obtener nombre del plan y completar la tabla desde panel/soporte, sin compartir claves. Después, en destino aislado y autorizado, comprobar runtime, dependencias, rutas/HTTPS, sesión, escritura y migraciones; probar transacción, reserva concurrente, reintentos y cancelación con datos ficticios. Probar restauración antes de datos reales. No desplegar ni probar sobre el sitio anterior o su base.

**Dictamen: no verificado.** La información pública no demuestra compatibilidad ni incompatibilidad. No se han ejecutado pruebas remotas ni enviado consultas a soporte. Las pruebas locales pueden avanzar; A6 y cobro/despacho continúan pendientes.

Consulta preparada, no enviada: «En nuestro plan [nombre exacto], ¿qué versiones PHP web/CLI, extensiones y MySQL/InnoDB tenemos? ¿Se permite document root en `public/`, reescritura, HTTPS, CLI para Artisan, enlaces para fotos y cron? Favor indicar memoria/CPU/procesos/tiempo/disco/DB, frecuencia de cron, SMTP y retención/restauración de respaldos. ¿Podemos usar un subdominio y base aislados para probar?»
