# Revisión de tecnología y defaults

Veredicto: apto para demo local; sin hallazgos bloqueantes ni cambios necesarios al stack del spine.

## Evidencia comprobada

- composer.json requiere PHP ^8.3, Laravel ^13 y PHPUnit ^12.5.12. composer.lock e installed.json coinciden en Laravel v13.37.0 y PHPUnit 12.5.40. Ejecución local con .local/php.ini: artisan --version y phpunit --version confirman esas versiones.
- php -v confirma 8.3.16; composer --version, 2.6.5; mysql --version, cliente 8.0.30. La versión del servidor dedicado se conserva como evidencia del autor: esta revisión no volvió a consultar el servidor. La versión del skeleton 13.0.0 procede del registro de bootstrap, no de composer.lock.
- La [documentación oficial de deployment de Laravel 13](https://laravel.com/docs/13.x/deployment), consultada nuevamente el 2026-10-16, exige PHP >=8.3 y raíz pública public/index.php. Ambas decisiones concuerdan con el spine. Se reabrieron también las referencias oficiales de [sesiones](https://laravel.com/docs/13.x/session) y [CSRF](https://laravel.com/docs/13.x/csrf).
- Los fallbacks del skeleton siguen existiendo en config (SQLite/database), pero .env.example selecciona explícitamente MySQL/file/file/sync y correo log. phpunit.xml selecciona MySQL de pruebas separado y drivers array para sesión/caché/correo: sustituciones apropiadas para pruebas, sin contradicción con la persistencia de la demo.
- composer dev solo arranca artisan serve en loopback; no introduce cola residente ni Vite. Los scripts setup ya no exigen build Node. post-create-project-cmd conserva el histórico touch de SQLite del skeleton; no es la ruta de ejecución de esta aplicación ya creada y no altera la conexión MySQL seleccionada.
- Las versiones se presentan como instaladas localmente, sin afirmar que sean las últimas ni que estén recomendadas para producción. La compatibilidad del hosting y una versión MySQL mantenida están diferidas expresamente; no se añaden como requisitos de esta entrega.

## Observación operativa no bloqueante

PHP sin -c reproduce la advertencia por carga redundante de xml. Con .local/php.ini los comandos de versión de Laravel/PHPUnit finalizan sin dicha advertencia. Mantener ese archivo y su uso explícito en los pasos locales de arranque/pruebas, tal como registra el memlog; no cambiar configuración global.

No se ejecutaron instalación, actualización, migraciones, reinicios de servicios ni cambios de configuración.
