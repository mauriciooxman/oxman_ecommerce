# Probar checkout 3A local

Estado técnico verificado el 2026-11-02. Laravel/MySQL/Blade; solo datos ficticios. Migración ya aplicada en la demo: no repetir instalación, seed ni migrate:fresh.

Si el servidor no está activo, desde la raíz en PowerShell (MySQL local debe estar en 3307; [arranque existente](probar-demo-local.md)):

```powershell
$env:PHPRC = Join-Path (Get-Location) '.local/php.ini'
Set-Location app
php artisan serve --host=127.0.0.1 --port=8000
```

1. Abre <http://127.0.0.1:8000>, agrega un artículo con unidades disponibles y entra al carro.
2. Pulsa **Revisar pedido**. Usa nombre `Comprador Ficticio`, teléfono `000000000` y, opcionalmente, `demo@example.test`.
3. Prueba **Retiro en tienda** o **Despacho pendiente de cotización**. Despacho requiere región, comuna y dirección ficticias. Revisa precios y cantidades; confirma el pedido de demostración.
4. Comprueba la referencia y abre **Consultar seguimiento privado**. Guarda el enlace: permite acceder desde otra sesión, mientras que la referencia por sí sola no autoriza. El seguimiento no muestra teléfono, correo ni dirección.
5. Vuelve al catálogo y comprueba el disponible reducido por la reserva. El stock físico no se descuenta. Las reservas persisten y no vencen en 3A; usa cantidades pequeñas al probar la demo.
6. Para revisar validaciones, deja contacto vacío o selecciona despacho sin dirección. No debe crearse un pedido. Para revisión visual pendiente, prueba 360 px/escritorio, teclado, errores y cambio retiro/despacho.

La cotización solo se puede aceptar cuando existe un monto cotizado. El panel de 3B ya permite registrarlo: [recorrido de compra y gestión](pedidos-local.md). No hay pagos, correos ni operaciones reales.

Para repetir las verificaciones técnicas, desde `app/` con PHPRC local:

```powershell
php artisan test --compact
php ../tools/verify-orders-runtime.php
php ../tools/verify-owner-runtime.php
```

Ejecutarlas **en secuencia**, nunca simultáneamente: usan exclusivamente `oxman_demo_test`. El arnés de pedidos abre temporalmente los puertos locales 8015/8016 y elimina únicamente sus fixtures; no toca la demo. Evidencia nueva: 80 pruebas / 1.345 aserciones, 43 checks de pedidos y 34 del panel. Revisión visual pendiente por bloqueo de Computer Use al determinar la URL de Chrome; no se usó Playwright ni se ejecutó revisión BMAD.
