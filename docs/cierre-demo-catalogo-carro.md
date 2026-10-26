# Cierre técnico de la primera demo

2026-10-25, America/Santiago. Alcance: catálogo, búsqueda/filtros, ficha y carro local ficticio. Sin pedidos, reservas, pagos, despacho operativo ni administración.

## Prueba de Mauro

Mauro informó «Ya probé la primera demo» y pidió preparar la siguiente entrega. Se registra la prueba humana realizada; no indicó dispositivos, casos ejecutados ni resultados detallados. No se atribuye a esa declaración una comprobación exhaustiva de los criterios ni de funciones fuera de alcance.

## Revisión y cierre BMAD

Se retoma el bmad-build ya generado, paso4, sin repetir descubrimiento ni reconstrucción. Las tres capas de revisión y sus14 hallazgos están preservadas en la especificación. Se resolvieron los grupos de corrección (BH1–5, BH8, EC1–3, VG1–3 y UI1). BH6/BH7 mantienen sus decisiones de rechazo documentadas; no hay trabajo diferido surgido de esa revisión.

El único fallo pendiente era del arnés: MakesHttpRequests no conserva automáticamente las cookies de respuesta. Se preserva explícitamente el ID de sesión en la prueba de errores de dos líneas. Conserva las aserciones de error por producto, una sola línea inválida y carro intacto. No se alteró la implementación para hacerla pasar. La sintaxis Blade quedó reparada durante la tarea del túnel y está incluida en las comprobaciones actuales.

Verificación final ejecutada con PHPRC local y base exclusiva `oxman_demo_test` protegida antes de DDL:

| Comprobación | Resultado |
| --- | --- |
| Composer validate --strict / check-platform-reqs | Ambos salida0 |
| php artisan test --compact | 27 pruebas,200 aserciones; salida0 |
| php artisan route:list / view:cache | Ambos salida0 |
| Dos procesos PHP8001/8002, una sesión real | 16 altas concurrentes conservadas; una línea16unidades,$40.000 |

La matriz original se conserva: límites5/6/23/24 (unitarios3000/2500/2500/2000; subtotales15000/15000/57500/48000), reducir/consolidar/no sumar SKU, entradas inválidas y carro intacto, oculto/agotado, precio manipulado, cambios de catálogo, sugerencia alcanzable y estados vacíos. Sus pruebas se ejecutaron en la suite anterior. La cobertura nueva añade filtros independientes/cero, errores por línea,40+1 y CSRF activo POST/PATCH/DELETE.

Evidencia vigente reutilizada: HTTP real local, persistencia después de reiniciar PHP y clientes separados del2026-10-16; inspección Chrome a360px y escritorio, Tab/Shift+Tab/Enter y foco visible; recorrido HTTPS de catálogo, CSS/imágenes, ficha y carro5/6/23/24, errores por línea, persistencia, CSRF419 y16 rutas privadas bloqueadas registrado en [compartir-demo-temporal.md](compartir-demo-temporal.md). La inspección móvil fue emulación de viewport, no un teléfono físico. No se repitieron esas comprobaciones sin cambios funcionales que lo justificaran.

Evidencia de esta ejecución: `.local/cierre-tests.log`, `.local/cierre-composer-validate.log`, `.local/cierre-composer-platform.log`, `.local/cierre-rutas.log`, `.local/cierre-blade.log`; arnés de concurrencia `.local/verify-session-concurrency.py`. Fuentes de mejor esfuerzo en diff temporal, porque no hay repositorio Git; no existe commit.

## Límites no verificados

No se verificaron zoom real200% (los atajos intentados no cambiaron el zoom), lector de pantalla, dispositivos físicos, matriz de navegadores, hosting, operación productiva, respaldos/restauración ni seguridad del futuro panel. No se describen como aprobados ni se convierten en condiciones cumplidas por la prueba de Mauro. No bloquean el cierre técnico del alcance local aprobado; siguen como cobertura futura explícita.

El paso5 de bmad-build marca la especificación `done`: cierre técnico de esta entrega. No certifica producción ni sustituye decisiones pendientes del producto. La siguiente entrega se prepara como especificación independiente y requiere su propio checkpoint de aprobación antes de implementar.
