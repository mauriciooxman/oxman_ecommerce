---
title: 'Panel privado del dueño: catálogo local'
type: 'feature'
created: '2026-10-25'
status: 'in-review'
baseline_commit: 'NO_VCS'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/implementation-artifacts/panel-dueno/criterios-pruebas.md'
---

<frozen-after-approval reason="human-owned intent">

## Intent

Administrar el catálogo existente desde un panel privado: sesión, productos/descripciones/categorías/fotos, precios/tramos, publicación/ocultación/archivo y stock auditado. Reutilizar Laravel/Blade/MySQL y reflejar cambios en tienda y carro.

## Boundaries & Constraints

**Siempre:** datos ficticios; permisos y pertenencia comprobados en servidor en cada lectura/escritura; historial con cantidad,motivo,fecha,usuario; precio CLP entero por tramo para todas las unidades del SKU; conservar catálogo/carros y regresiones.

**Nunca:** registro público de administradores, panel por túnel, datos reales, pedidos,reservas,pagos,despacho,hosting/DNS; reconstruir la aplicación o borrar historial.

## Decisiones de Mauro

- Una foto principal por producto: JPEG,PNG o WebP, hasta5MiB.
- Categorías administrables por tienda; renombrar se refleja en sus productos.
- Archivo restaurable: vuelve a oculto y exige publicar explícitamente.
- Ambos modos de stock: suma/resta y conteo físico final; guardan diferencia,antes/después,motivo obligatorio,fecha y usuario. Sin negativos ni duplicados por reintentos.
- Crear/restablecer cuentas por consola local de Mauro. Sin registro,invitaciones,recuperación web ni contraseñas en documentos/registros. Panel exclusivamente local.

## I/O & Edge-Case Matrix

| Estado/entrada | Resultado exigido |
| --- | --- |
| Invitado/credencial inválida/logout | Sin datos privados; acceso válido inicia sesión; salir la invalida |
| Usuario de otra tienda/ID manipulado | Rechazar sin leer ni alterar catálogo,fotos,historial ajenos |
| Producto/precios/tramos inválidos | Errores identificados; sin guardado parcial |
| Foto inválida/fallo al guardar | Conservar foto y producto anteriores |
| Oculto/archivado/restaurado | Fuera de catálogo/ficha/agregado; restaurar a oculto; aviso en carro |
| Ajuste inválido,concurrente o repetido | Sin negativos/pérdidas/duplicados; stock e historial atómicos |

</frozen-after-approval>

## Code Map

`app/`: Store::products, Product::tiers, PricingService::quote y CartService::snapshot ya existen. StorefrontController fija tienda demo; no sirve como autorización privada. User/auth existen sin relación a tienda ni login. Fotos: un nombre SVG en products.image, sin upload. No hay archivo ni movimientos. Detalle y rutas propuestas en el anexo; UX/arquitectura anteriores cubren solo tienda pública.

## Tasks & Acceptance

- [x] `app/database/migrations/`, `app/app/Models/`: asociar usuarios/categorías a tienda, foto principal, archivo y movimientos auditados sin perder datos.
- [x] `app/routes/web.php`, `app/app/Http/{Controllers/Owner,Requests}/`, `app/app/Policies/`, `app/routes/console.php`: sesión, provisión aprobada y autorización por tienda.
- [x] `app/app/Services/`, `app/resources/views/owner/`, `app/config/filesystems.php`: catálogo, precios, fotos y ajustes transaccionales; formularios privados accesibles. Implementación y asociaciones DOM verificadas; recorrido C8 pendiente.
- [x] `app/app/Http/Controllers/StorefrontController.php`, `app/app/Services/CartService.php`, `app/resources/views/`: reflejar edición y archivo conservando contratos públicos.
- [ ] `app/tests/`, `docs/`: ejecutar matriz y criterios C1–C9 del anexo, actualizar acceso/evidencia y límites del panel local.

**Criterios:**
- Dado un dueño autorizado, cuando administra su catálogo, entonces C1–C8 del anexo pasan sin cambiar datos ajenos.
- Dada la tienda existente, cuando se completa el panel, entonces C9 y la suite pública siguen aprobados.

## Implementation Notes

- Reanudación2026-10-25: tres revisiones terminadas y patches aplicados por el mismo implementador. Verificación padre después de parches:53pruebas/980aserciones,34checks HTTP/concurrencia/router, Composer validate/platform,21rutas y caché Blade aprobados. C8 sigue pendiente por interrupción CUA de identificación de URL; no se marca done ni aceptación humana. Mauro confirmó alta de su cuenta y producto con foto, y autorizó init/commit/push del estado actual. Fuentes congeladas; cuenta técnica de inspección nuevamente deshabilitada, material cifrado eliminado. No se modificaron su cuenta/producto.

- Implementación técnica entregada:43pruebas/581aserciones y34checks HTTP/CSRF/concurrencia/router aprobados, migración incremental comparada sin pérdida6productos/7tramos. C8 es parcial: navegador comprobó login y DOM, pero Computer Use terminó por identificación insuficiente de URL al intentar selector nativo. Se solicitó a Mauro retomar la comprobación; no se afirma360px/teclado/foto visual ni cierre total. Se revisa el código con esta limitación explícita; el paso5 queda condicionado a resolver/registrar esta aceptación pendiente. Guía: [panel-dueno-local.md](../../docs/panel-dueno-local.md).

- 2026-10-25: Mauro aprobó y autorizó continuar. Baseline de fuentes en `.local/panel-baseline-20261025/` (47 archivos; sin secretos/vendor/datos), sin Git. MySQL dedicado existente comprobado; respaldo previo `.local/backups/antes-panel-20261025.sql` creado por mysqldump, restauración no ejecutada. Túnel cloudflared y servidor compartido8003 detenidos; servidor local8000 y MySQL3307 siguen disponibles. PHPRC local absoluto: `C:/Users/mauro/Desktop/OXMANIT/oxman_ecommerce/.local/php.ini`. No repetir inicialización ni semillas sobre demo.

## Spec Change Log

## Review Triage Log

Revisión inicial: tres revisores independientes, todos recibidos antes de clasificar. Evidencia vigente: diff124.544bytes; lectura de controladores, servicios, vistas, migración, pruebas y guardias de tienda.

| ID/origen | Veredicto | Evidencia y resolución |
| --- | --- | --- |
| B1 blind: enlace público de otra tienda | medium | La ficha privada permite cualquier tienda autorizada, pero la pública fija `tecnologia-demo`. El enlace puede mostrar otro SKU homónimo. Patch: mostrar acción pública solo para esa tienda, sin ampliar el catálogo público. |
| B2 blind: carrera de categoría | medium | La unicidad se valida antes de adquirir bloqueo y no se captura1062. Dos escrituras válidas concurrentes pueden terminar en500. Patch: convertir duplicado en validación y conservar formulario. |
| B3 blind: orientación EXIF | medium | GD decodifica píxeles sin orientación y la recodificación descarta EXIF; una foto JPEG orientada queda girada. Patch: normalizar las ocho orientaciones al recodificar y comprobar muestra orientada. |
| B4 blind: sufijo bcrypt | medium | Consola admite72bytes y login1024caracteres; bcrypt ignora bytes posteriores a72. Patch: rechazar más de72bytes antes de autenticar y comprobar ASCII/multibyte. |
| B5 blind: error de renombrado | medium | Todas las categorías usan `name`; old no se repone y el resumen apunta al formulario de creación. Patch: identificar formulario fallido, reponer solo su valor y asociar su error. |
| B6 blind: asociación accesible | low | Login/producto omiten aria-invalid y los tramos carecen de asociación al error. Corrección directa de atributos/IDs existentes; patch. |
| B7 blind: leyendas de foto | low | Las vistas públicas imprimen siempre Ilustración/Fotografía pendiente incluso con photo_path. Patch directo de texto alternativo y leyenda condicional. |
| B8 blind: conflicto pierde edición | medium |409 solo ofrece listado, sin datos enviados ni regreso al producto. Patch: mostrar copia escapada de campos recuperables y enlace a la versión actual, sin reenviar automáticamente ni guardar foto/secretos. |
| B9 blind: recorrido visual pendiente | medium | La guía reconoce C8 parcial; DOM/HTTP no acreditan renderizado ni teclado. Lo resuelve el flujo padre completando recorrido y registrando exactamente lo ejecutado. |
| B10 blind: eliminación fallida de fotos | low | delete devuelve booleano ignorado; un fallo del disco puede dejar un archivo privado sin referencia. Rechazado: fallo excepcional de limpieza en demo local, sin exposición ni corrupción del producto; el mecanismo adicional de recuperación propuesto agrega superficie y complejidad. No se afirma limpieza bajo fallo de eliminación. |
| E1 edges: carrera de categoría | medium | Mismo resultado verificado que B2; conserva fila individual y se agrupa con B2 para un patch. |
| E2 edges: archivo eliminado al transmitir | low | exists precede respuesta cuyo readStream es diferido; un reemplazo puede retirar el archivo entre ambos. Patch directo: abrir el recurso antes de construir respuesta y fallar404 si no puede abrirse, en ambas rutas. |
| E3 edges: categoría heredada larga | low | El esquema anterior permite255, la nueva tabla60; datos hipotéticos largos fallarían al migrar. La migración real ya comparó las seis categorías ficticias válidas sin pérdida. Rechazado por baja probabilidad en el catálogo local fijo y porque recuperar DDL parcial/ampliar límites requeriría lógica adicional; no se afirma migración de otras bases. |
| V1 gaps: bytes de foto no comprobados | medium | Hallazgo pre-verificado: las pruebas solo comprueban sufijo/headers; conservar bytes originales podría pasarlas. Patch: inspeccionar WebP decodificable almacenado/servido y ausencia de marcador/metadatos de origen. |
| V2 gaps: ocultar sin prueba | medium | Hallazgo pre-verificado: archivo/restauración/publicación no ejecutan action=hide. Patch: ocultar vía endpoint y comprobar estado, catálogo, ficha, foto y carro. |

Agrupación: B2+E1 comparten la carrera; todos los demás tienen causas distintas. No hay intent_gap ni bad_spec: son correcciones acotadas sobre entradas/estados ya admitidos, sin ampliar alcance. B9 lo atiende el padre; restantes supervivientes enviados al mismo implementador como patch. Ningún diferido.

Resultado: patches implementados y leídos por el padre;26tests del panel/780aserciones más27tests públicos/200aserciones aprobados. Se repitieron también los34checks de runtime y todos los comandos de Verification con salida0. B9 permanece pendiente: confirmación de URL por Mauro no resolvió la interrupción de la herramienta; la prueba humana de creación/foto se registra por separado. Por ello se conserva in-review y el paso5 no se presenta como completado.

## Design Notes

Un objetivo: catálogo administrable seguro, sin pedidos de épica3 ni historia4.5. Migraciones,archivos y ajustes: después de aprobación y copia local. Huella: modelos,policies,controllers,servicios,vistas,pruebas; conserva stack/contratos públicos. No hay Git. Anexo: reglas técnicas a aprobar junto con estas decisiones.

## Verification

Con PHPRC local y guardia de MySQL: `composer validate --strict`, `composer check-platform-reqs`, `php artisan test --compact` (53/980), `php artisan route:list --except-vendor` (21rutas), `php artisan view:cache` y `php tools/verify-owner-runtime.php` desde raíz (34checks); todos salida0 tras parches. Migración incremental comprobada antes de los parches que no la alteraron; no se repitió sobre la demo. Recorrido360px/escritorio/teclado y errores definido en el anexo sigue pendiente; no se afirma ejecutado por existencia de pruebas HTTP/DOM. El push autorizado conserva ese estado y no supone aceptación final.
