# Panel del dueño: criterios y pruebas

2026-10-25. Anexo del borrador [spec-panel-dueno-catalogo-local.md](../spec-panel-dueno-catalogo-local.md). Las cinco decisiones de Mauro están registradas en el bloque de intención de la especificación: foto principal JPEG/PNG/WebP hasta5MiB; categorías administrables; archivo restaurable a oculto; ambos modos de stock; cuentas solo por consola local sin contraseñas en documentación/registros. No quedan preguntas abiertas. Las demás reglas son propuestas técnicas para aprobar junto con la especificación en checkpoint1. No representan código implementado ni pruebas ejecutadas.

## Base y decisiones de diseño propuestas

Se reaprovechan `app/app/Models/{Store,Product,PriceTier,User}.php`, migraciones de tienda/productos/tramos y usuarios, `config/auth.php` (guard web por sesión), `PricingService::quote`, `CartService::snapshot`, vistas/CSS/CSRF y la guardia de base de pruebas. No incorporar un starter ni reemplazar la tienda. Sin proveedores externos, Node, colas ni servicios de correo para esta entrega.

El panel vive bajo `/dueno`, incluidos ingreso y salida; vistas bajo `app/resources/views/owner/`; controladores `app/app/Http/Controllers/Owner/`, FormRequests para campos aceptados, policies para productos/movimientos/fotos/categorías y servicios separados para guardar catálogo/fotos/ajustes. La tienda privada se deriva del usuario persistido; el `store_id` enviado por cliente se rechaza. No reutilizar la tienda hardcodeada pública como control de acceso. Usuarios con rol dueño y tienda activa acceden solo a esa tienda; sin rol o tienda válida se deniega. Las cuentas de dos tiendas de prueba permiten demostrar aislamiento; no hay interfaz de gestión de colaboradores/OxmanIT ni privilegio global implícito.

Extensiones previstas: migración de asociación usuario–tienda/rol; `archived_at` en products; modelo/tabla `StockMovement` con tienda,producto,usuario,fecha del servidor,modo (variación/conteo),cantidad ingresada,variación firmada,stock anterior/posterior,motivo e identidad única de operación. Products.stock queda como existencia vigente, no editable directamente por mass assignment del formulario de producto. Agregar tabla/modelo `Category` por tienda y relación de producto, migrando los textos actuales sin mezclar comercios. Una referencia a imagen principal por producto; no crear galería ni ProductImage ordenado. Nuevos modelos en `app/app/Models/`, migraciones incrementales en `app/database/migrations/`.

No inventar movimientos anteriores: los productos existentes conservan su stock, y el historial documenta ajustes desde la activación. Productos nuevos nacen ocultos y con stock0; la carga inicial de unidades se registra como ajuste autorizado. No eliminar físicamente productos, usuarios ni movimientos referenciados durante esta entrega. Una corrección de stock crea otro movimiento, nunca edita el pasado. Las migraciones preservan IDs, SKU, precios, publicación, ilustraciones, tramos y existencias actuales; no repetir seeder para migrar ni usar migrate:fresh contra oxman_demo.

Los contratos visuales/arquitectónicos existentes están finalizados solo para la demo pública. Al implementar, documentar la ampliación local de autorización, transacciones y UX del panel con su propia evidencia; no atribuir aprobación del panel a aquellos contratos. La nueva instrucción de Mauro prioriza este subconjunto de la épica4 antes de pedidos: no hereda dependencias sobre reservas o cancelaciones de la épica3 y no implementa4.5.

## C1 — Sesión privada

**Dado** un dueño creado mediante consola local de Mauro, **cuando** entra con credenciales válidas, **entonces** autentica por email/contraseña hasheada, regenera ID de sesión y muestra solo su tienda. No hay registro público, cuenta implícita al iniciar ni credenciales en HTML/logs/documentos. Para credenciales inválidas, mensaje genérico sin distinguir email existente; límite propuesto5intentos/minuto por email normalizado e IP, con prueba de bloqueo/recuperación del límite.

**Dada** una sesión, **cuando** se cierra por POST con CSRF, **entonces** se invalida y regenera token; una petición posterior o copia de la cookie anterior no accede al panel. Expiración usa vigencia de sesión configurada (actual120min). Sin recordar acceso entre dispositivos.

**Dada** la consola local, **cuando** Mauro crea o restablece una cuenta, **entonces** se pide la contraseña interactivamente sin eco ni argumentos de línea de comandos y se guarda solo su hash; no se imprime, documenta, registra ni persiste texto claro. El restablecimiento revoca las sesiones anteriores de esa cuenta (por ejemplo mediante una versión de credencial comprobada en cada petición privada). Operación limitada a entorno/base ficticios verificados, con tienda/rol explícitos y errores sin volcar entrada sensible. Sin rutas de registro, invitaciones ni recuperación web. Contraseñas/token no deben quedar en flash/old input ni logs de excepciones. No sembrar contraseñas fijas ni publicarlas por el túnel.

**Pruebas:** invitado en cada GET y mutación, login bueno/malo/rate limit, fijación de sesión, logout y sesión expirada, CSRF ausente/incorrecto/valido con middleware activo. Comandos de alta/restablecimiento: hash correcto, acceso posterior, contraseña anterior rechazada y sesiones previas revocadas; salida/logs/documentos sin contraseña ni secreto; ausencia de rutas de registro/invitaciones/recuperación web. No usar solamente assert de existencia de campos; conservar cookies reales al verificar redirecciones.

## C2 — Autorización y aislamiento

**Dados** dueños A/B y tiendas A/B con productos incluso del mismo SKU, **cuando** A consulta/lista/crea/edita/publica/archiva, cambia fotos o ajusta/lee stock, **entonces** todas las consultas parten de su tienda y las mutaciones comprueban permiso antes de leer o escribir recursos. IDs ajenos/inexistentes responden404; usuarios autenticados sin rol/tienda válida reciben403; invitado va al ingreso sin contenido privado. Ninguna selección manipulada de tienda/producto/categoría/foto/movimiento permite operar B.

**Pruebas:** matriz de endpoints privados con dos usuarios/tiendas y controles antes/después de datos/archivos ajenos; usuario sin rol y sin tienda; IDs anidados cruzados y store_id/usuario/fecha manipulados. No basta esconder botones ni probar una sola ruta. Validar pertenencia también al servir originales o imágenes de productos no públicos.

## C3 — Crear y editar catálogo/categorías

**Dado** el dueño autenticado, **cuando** guarda datos válidos, **entonces** persisten nombre,SKU,descripción,categoría y compatibilidad existente sin perder fotos/tramos/stock; SKU único dentro de tienda (también entre archivados), permitido en otra tienda. Descripción es texto escapado, no HTML ejecutable. Propuesta de límites: SKU1–64 caracteres ASCII alfanuméricos/guion/guion bajo; nombre1–160, categoría1–60, descripción1–5000 y compatibilidad hasta2000. No cambiar los SKU ficticios vigentes. Categoría se selecciona por ID de la lista administrable de esa tienda; nombres no vacíos y únicos por tienda, tras normalizar espacios, admiten el mismo nombre en otra tienda. No se incluye eliminación de categorías.

**Dada** una categoría con productos, **cuando** su dueño la renombra, **entonces** el nombre se refleja en todos sus productos, incluidos ocultos/archivados del panel, y en listado,filtros y fichas públicas correspondientes. Mantener relación estable por ID; no editar copias de texto independientes ni renombrar categorías homónimas de otra tienda.

**Dado** un SKU duplicado, datos excesivos, script o identificador ajeno, **cuando** intenta guardar, **entonces** se explican errores junto al campo y se conserva la versión válida completa. No aceptar stock directo,store_id,usuario ni campos fuera de lista. Guardados del mismo producto desde una versión vieja se rechazan como conflicto sin pisar el cambio más reciente; precio/tramos/publicación se guardan coherentemente en una transacción.

**Pruebas:** creación/edición/relectura, límites, unicidad por tienda, asignación maliciosa, texto escapado y conflicto entre dos formularios abiertos. Crear/renombrar categoría y verificar varios productos, catálogo/filtros/ficha; categoría ajena rechazada sin cambios; duplicado por tienda rechazado y homónimo en otra admitido. Preservar categorías y asignaciones actuales en migración.

## C4 — Precio detalle y tramos

**Dado** un producto, **cuando** se configuran precios válidos, **entonces** el precio detalle y mayorista son enteros CLP positivos (propuesta máximo1.000.000.000), umbrales enteros únicos desde2, ordenados y precio estrictamente menor al tramo anterior; cero tramos permite solo detalle. El umbral puede exceder stock, pero la tienda no sugiere compras inalcanzables. Se reemplaza el conjunto de tramos de forma transaccional, eliminando del conjunto activo los retirados; no reutilizar el seeder como editor.

**Dado** un precio negativo/fraccionario/no numérico, umbral repetido o escalado incoherente, **cuando** se guarda, **entonces** no cambia ningún precio/tramo. Reutilizar el cálculo del mayor umbral sobre todas las unidades del mismo SKU, sin sumar artículos distintos.

**Pruebas:** edición/agregado/retiro de tramos y rollback completo; conservar límites5/6/23/24 y reducción; comprobar cada importe unitario y subtotal calculado en servidor, ignorando importes cliente. Carros abiertos muestran aviso y nuevo precio tras una edición.

## C5 — Fotografías

**Dado** un producto de la tienda, **cuando** sube/reemplaza una foto principal válida, **entonces** queda una sola imagen principal asociada a ese producto y se muestra en la tienda si está publicado. Decisión de Mauro: JPEG/PNG/WebP hasta5MiB (5×1024×1024bytes), sin galería. Propuesta técnica adicional: dimensiones máximas4096×4096 antes de decodificar, nombre generado y recodificación a formato permitido sin metadatos. No aceptar SVG subidos, ejecutables, doble extensión, rutas o archivos remotos. Las ilustraciones SVG existentes siguen permitidas como assets internos conocidos. No confundir extensión/MIME del cliente con contenido real.

**Dado** archivo inválido, recurso ajeno, fallo de disco o transacción, **cuando** se intenta reemplazar, **entonces** no se pierde la foto anterior ni se publica un archivo parcial; limpiar temporal fallido. Propuesta: archivos en disco local privado y entrega por controlador que resuelve pertenencia/estado; únicamente fotos de producto publicado sirven sin sesión. No exponer `/storage` completo, originales ajenos, rutas físicas ni EXIF. No incorporar controles ni almacenamiento de galería.

**Pruebas:** contenido real válido de cada formato, límite de5MiB y rechazo de5MiB+1byte, archivo no-imagen con extensión falsa, SVG/script/traversal, excesos de dimensiones y fallo de almacenamiento. Probar que reemplazo deja una sola principal, conserva la anterior al fallar y respeta aislamiento de referencia/archivo. GD local comprobado con soporte JPEG/PNG/WebP; su disponibilidad no equivale a un upload implementado ni probado.

## C6 — Publicar, ocultar, archivar

**Dado** un producto válido, **cuando** publica u oculta, **entonces** cambia su visibilidad pública sin perder contenido/tramos/historial. Oculto es editable por dueño y no aparece en catálogo/categorías derivadas ni ficha/agregado público. Archivo es una acción explícita separada; conserva datos e historial, queda fuera del listado normal del panel (filtro Archivados) y no admite editar precio/stock/fotos ni publicar mientras esté archivado. Archivar fuerza estado no publicado; restaurar elimina la marca de archivo pero mantiene oculto y exige una acción explícita posterior para publicar, aunque estuviera publicado antes de archivar.

**Dado** carro previo con producto archivado/oculto, **cuando** se vuelve a leer o agregar, **entonces** se revalida y avisa indisponibilidad sin reservar ni descontar stock. La edición de precio/stock/publicación no puede usar copia vieja para deshacer archivo concurrente.

**Pruebas:** estados/transiciones permitidas y rechazadas, acceso por URL directa, carrito previo y fotografía pública del producto oculto/archivado; ninguna ruta borra el historial. Probar publicado→archivado→restaurado oculto→publicación explícita y conservación íntegra de datos/movimientos, además de restauración ajena denegada.

## C7 — Ajuste e historial de stock

**Dado** dueño/producto no archivado y stock40, **cuando** resta3 o registra conteo físico final37, **entonces** en ambos modos stock37 y un único movimiento guarda modo,cantidad ingresada,delta−3,antes40,después37,motivo obligatorio,fecha del servidor y usuario autenticado. Sumar3 deja43 con delta+3; conteo físico0 deja0 con delta−40. Propuesta máximo de stock1.000.000unidades y motivo1–500 caracteres. Ajuste por variación0, fracciones, conteo negativo, resultado negativo, producto archivado o IDs/actor/fecha manipulados se rechazan sin alterar stock/historial. Regla técnica propuesta: conteo igual a existencias sí registra verificación física con diferencia0 y motivo, sujeto a la misma idempotencia.

**Dada** una petición repetida o dos ajustes simultáneos, **cuando** se procesan, **entonces** transacción y bloqueo de producto mantienen stock e historial inseparables. Cada formulario lleva identidad de operación que se conserva en reintentos; ambos modos comparten la garantía de un único efecto/movimiento. Bajo el bloqueo se consulta primero una operación ya registrada y después se comprueba la versión del producto: un reintento idéntico devuelve el resultado original aunque la versión haya avanzado. Reutilizar identidad con modo,cantidad,motivo o producto diferentes devuelve conflicto. Desde stock5, dos bajas4 simultáneas permiten una sola y dejan stock1,un movimiento. Un conteo basado en versión antigua se rechaza, no pisa ajustes recientes. Los lectores ajenos no ven el historial y no hay endpoints para editar/borrar movimientos.

**Pruebas:** suma/resta/conteo positivo/conteo0/conteo sin diferencia y rollback, campos completos de auditoría,motivo vacío,límites/fracciones/negativos; reintento idéntico después de éxito en cada modo sin duplicar, misma identidad/payload distinto rechazado y operación nueva con identidad distinta. Dos procesos/conexiones reales sobre MySQL exclusivo: bajas competidoras sin stock suficiente y conteo obsoleto frente a variación nueva. Verificar antes/después/suma de deltas y pertenencia; no sustituir prueba de concurrencia por dos llamadas secuenciales. Historial con fecha local presentada America/Santiago y dato persistido consistente; datos no inventan ventas pasadas.

## C8 — Interfaz privada y exclusión del túnel

**Dado** ingreso válido, **cuando** navega a360px y escritorio con Tab/Shift+Tab/Enter, **entonces** puede editar el catálogo, entender errores y consultar movimientos con etiquetas/foco visibles y sin desborde; publicar/ocultar/archivar muestran resultado claro y archivo confirma antes de aplicar. Texto ficticio y pertenencia de tienda visibles en panel.

**Dado** el túnel temporal público, **cuando** solicita cualquier ruta `/dueno` (incluido ingreso/logout), **entonces** recibe404 del router de exposición antes de Laravel; no mostrar enlaces al panel desde el catálogo público compartido. No modificar la lista permitida de `tools/demo-share-router.php`, puertos/DNS ni desplegar el panel. Probar bloqueo del prefijo en el router local con métodos GET/POST, sin necesidad de abrir/reactivar túnel. Operar el panel solo por loopback local; los parámetros production/debug=false/cookies seguras del servidor compartido no deben copiarse sin adaptación al acceso HTTP local.

**Pruebas:** recorrido renderizado real tras implementación; errores y tablas; verificar route:list sin registro público/admin de desarrollo, aislamiento del router y recursos privados. No declarar prueba de teléfono físico por cambiar viewport; no afirmar zoom/lector de pantalla si no se ejecutaron.

## C9 — Migración y regresión de la tienda

**Dada** una copia ficticia con catálogo existente, **cuando** aplica migraciones incrementales, **entonces** conserva6productos/7tramos/IDs/precios/stock/SVG y la tienda funciona sin usuario dueño hasta aprovisionar el acceso privado. Prueba exclusivamente sobre base protegida o copia autorizada dentro del proyecto; nunca migrate:fresh de la base demo ni bases ajenas. Comparar antes/después y documentar cómo revertir solo lo reversible; no prometer rollback que borre historia.

**Dado** un cambio autorizado desde panel, **cuando** comprador consulta la tienda o recarga su carro, **entonces** ve descripción/categoría/foto/precio/stock/visibilidad vigentes y revalidación coherente. No se crean pedidos, reservas ni movimientos por agregar al carro; el mismo inventario físico sirve detalle/mayorista.

**Pruebas:** migración sobre fixtures previos con datos verificables, suite pública27casos como baseline y nuevas regresiones por campo/estado; edición→tienda/carrito en sesión separada. Ejecutar Composer validate/platform, rutas y caché Blade; probar nuevas fotos y cuentas con datos ficticios exclusivamente.

## Evidencia exigida al implementar

Pruebas de alto riesgo primero: autenticación/aislamiento,transacción/stock/idempotencia,fotos. Feature/Unit cubren reglas y rollback; HTTP con cookies/CSRF reales cubre sesión y formularios; MySQL real cubre carreras; navegador cubre UX. No instalar un framework de E2E ni duplicar cada detalle visual en tests si la infraestructura existente basta. Cada fila de matriz debe apuntar a test que realmente se ejecutó; registrar totales/salidas y checks manuales con fecha. Reservas, pedidos, pagos, despacho y hosting permanecen fuera de toda afirmación de cobertura.

## Fuentes y decisiones registradas

- [Cierre anterior](../../../docs/cierre-demo-catalogo-carro.md): base ejecutada, prueba humana informada y limitaciones.
- `planning-artifacts/epics.md`, historias4.1–4.4: contexto histórico;4.5/pedidos fuera.
- PRD, FR-9–FR-12/A5/OQ-6: políticas de fotos/cuentas seguían propuestas; no asumir aceptación previa.
- `app/config/filesystems.php`: discos existentes no equivalen a subida funcional; no usar enlace público de todo storage por rutina.
- Mensaje posterior de Mauro: cinco decisiones incorporadas a la intención de la especificación. Confirmar decisiones no equivale todavía a aprobar toda la especificación; sin implementación autorizada hasta checkpoint1.

## Estado de planificación BMAD

Generador oficial bmad-build ejecutado con preparación local y salida0; workflow `c23975ebe0daadde957b`, pasos1–2. Solicitud libre que retoma la aplicación y delimita un único objetivo de panel, no una historia individual ni aprobación de la épica4 completa. Investigación de solo lectura y autoevaluación de alcance/riesgos realizadas; pruebas del panel son planificadas, no ejecutadas. Las reglas extensas quedan en este anexo, referenciado en context; el recuento de tokens previo era solo una estimación, no medición exacta.

Cinco respuestas registradas en el bloque de intención; eliminadas preguntas y variantes descartadas. Autoevaluación: tareas con rutas y dependencias, criterios Dado/Cuando/Entonces C1–C9 y pruebas de cada borde; sin vacíos conocidos de intención. CHECKPOINT1 pendiente: aprobar y continuar, aprobar y detener (ready-for-dev) o revisar especificación. Solo aprobación expresa cambia draft a ready-for-dev. No se ejecutó el paso3 para este panel; no se crearon rutas/modelos/migraciones ni se publicó ninguna interfaz privada.
