# Demo compartida temporalmente

Sesión del 2026-10-25, America/Santiago. Tarea operativa, sin iniciar un flujo BMAD nuevo.

URL activa: https://permanent-maritime-okay-implemented.trycloudflare.com

Es pública para quien conozca el enlace. Quick Tunnel no tiene continuidad garantizada; al detener/recrear cloudflared se pierde esta URL y se obtiene otra. No se usaron cuenta, dominios, DNS, SolucionHost ni el sitio anterior.

## Procesos de esta sesión

| Proceso | PID observado | Función |
| --- | --- | --- |
| cloudflared | 29860 | Quick Tunnel hacia http://127.0.0.1:8003, métricas solo en loopback20245 |
| PHP | 7012 | Servidor temporal de exposición, loopback8003 |
| MySQL demo | 22984 (lanzador8520) | Instancia existente, loopback3307, datadir `.local/mysql/data` |

El equipo debe seguir encendido, conectado y sin suspenderse. PHP8000 y los servidores temporales8001/8002 de la revisión anterior no intervienen en este túnel.

## Configuración aplicada

- Reutiliza la instalación, las dependencias, la clave de aplicación y la base `oxman_demo`; seis productos ficticios y siete tramos comprobados, sin migrar ni sembrar de nuevo.
- Solo el proceso PHP8003 hereda `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `LOG_LEVEL=warning` y PHPRC local. No se editaron `.env`, configuración global ni credenciales.
- PHP8003 omite errores visibles y cabecera de versión PHP. Router temporal [demo-share-router.php](../tools/demo-share-router.php) permite las pantallas públicas y los assets CSS/SVG/favicon; rechaza el resto. Considera HTTPS únicamente en este servidor dedicado al túnel. No usarlo como servidor HTTP general.
- No hay symlinks en public. Archivos privados, logs y código permanecen fuera de la raíz pública. Robots y cabecera X-Robots-Tag solicitan no indexar; esto no constituye autenticación.
- Se corrigió un error de compilación Blade que había quedado durante la revisión interrumpida: directivas anidadas del resumen de errores separadas en líneas. No se reconstruyó la aplicación.

## Verificación ejecutada

- Configuración efectiva de depuración desactivada, entorno production, MySQL exclusivo y cookies seguras comprobada antes del túnel.
- Catálogo, cinco recursos públicos distintos, ficha y carro funcionan con certificado HTTPS válido. Navegador confirmó imágenes cargadas, CSS aplicado y ausencia de enlaces HTTP.
- HTTP real por túnel: agregar5, actualizar6/23/24 da $15.000/$15.000/$57.500/$48.000; recarga conserva carro; error de cantidad identifica solo la línea enviada; sin CSRF devuelve419; quitar deja carro vacío.
- Cookie de sesión Secure/HttpOnly; 16 rutas privadas/de desarrollo bloqueadas (404, o400 de Cloudflare para recorrido de directorios), sin credenciales ni trazas en las respuestas.
- Evidencia temporal en `.local/check-public-demo.py`, logs de procesos y `.local/demo-publica.png`; no exponer ni publicar `.local/`.

La suite de la revisión anterior, tras reparar Blade, obtuvo26 pruebas aprobadas y1 fallida (194 aserciones): el caso de errores de cantidad no conserva el mensaje en el arnés de múltiples peticiones. El recorrido equivalente con cookies reales sí pasó por HTTPS. Ese fue el estado al terminar la tarea operativa. Actualización posterior del2026-10-25: arnés corregido conservando cookies;27pruebas/200aserciones y cierre técnico BMAD en [cierre-demo-catalogo-carro.md](cierre-demo-catalogo-carro.md). Mauro informó que probó la demo; no se le atribuyen casos exhaustivos no indicados. No se avanzó a pedidos/inventario.

## Detener al terminar

En PowerShell, para cortar inmediatamente el enlace público:

```powershell
Stop-Process -Id 29860
```

Después, detener el servidor de exposición:

```powershell
Stop-Process -Id 7012
```

MySQL puede seguir activo para la demo local. Si también se desea detenerlo, cerrar primero cualquier otra prueba/servidor de esta demo y usar cierre limpio de la instancia dedicada:

```powershell
& 'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqladmin.exe' --protocol=TCP --host=127.0.0.1 --port=3307 --user=root --password shutdown
```

La clave de ejemplo local se documenta en [probar-demo-local.md](probar-demo-local.md); no es una clave productiva. No detener servicios MySQL ajenos. Los PID anteriores corresponden solo a esta sesión: si se reinician procesos, comprobar identidad/ruta/puerto antes de usar un PID antiguo.

Referencia: [Cloudflare Quick Tunnels](https://developers.cloudflare.com/tunnel/get-started/quick-tunnels/).
