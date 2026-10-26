# Revisión adversarial de divergencia

Veredicto: sin divergencias críticas o altas; un hueco menor de contrato interno que conviene cerrar al finalizar.

## ADV-1 — Baja: forma de resultados entre servicios y vistas implícita

Dos unidades descendientes pueden respetar literalmente AD-1/2/3/5 y aun así producir una integración incompatible: una implementa PricingService con `next_tier` y `minimum_quantity`, manteniendo el cálculo correcto; otra escribe el carro esperando `next` y `minimum`. Ambas conservan precio entero, mayor umbral, sugerencia alcanzable, sesión por tienda y validación servidor. El spine fija las semánticas pero no identifica las formas ya existentes de `quote` y `snapshot` como contrato compartido.

Evidencia actual: PricingService::quote devuelve `unit_price`, `subtotal`, `minimum`, `next`; `next` es null o `{remaining, quantity, unit_price}`. CartService::snapshot devuelve `{lines, notices, subtotal}` y cada línea contiene `{product, quantity}` más el quote. cart.blade.php consume directamente esas claves. Un cambio unilateral rompe el renderizado aun conservando cada regla comercial.

**Triage: autofix documental sugerido.** Añadir una convención breve que reconozca esas formas y los métodos de servicios existentes como contratos internos, con cambios coordinados de productores/consumidores. No requiere nuevos DTO, capa API ni reescritura de código. La implementación actual ya concuerda consigo misma; no es un fallo funcional encontrado.

## Intentos sin hallazgo

- Identidad: AD-1 y las consultas por relación impiden que controlador y carro interpreten store_id del cliente como autorización. La excepción de quitar ocultos es explícita y coincide con código.
- Mutación: AD-3 asigna un dueño único de sesión; sumar frente a reemplazar y eliminar por indisponibilidad están definidos y reconciliados con UX. No queda una elección independiente entre ajustar silenciosamente stock y quitar línea.
- Precios: AD-2 exige el mayor umbral alcanzado para todas las unidades del mismo SKU; una implementación que mezcle SKU, use importe cliente o elija un umbral diferente incumple la regla, por lo que no constituye contraejemplo válido.
- Persistencia: AD-4 y Structural Seed distinguen base de demo, base destructible de pruebas, sesión archivo y raíz pública. No aparecen dos destinos locales igualmente autorizados que puedan confundirse.

Alcance exclusivamente catálogo/ficha/precios/carro de demo. No se presentan concurrencia, pedidos, reservas, panel o servicios productivos diferidos como faltantes actuales.
