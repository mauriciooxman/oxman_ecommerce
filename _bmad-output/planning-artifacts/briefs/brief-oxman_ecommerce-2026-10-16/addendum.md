# Detalles para el diseño y la implementación

## Decisiones confirmadas

- Nombre: Oxman Ecommerce; identificador: `oxman_ecommerce`.
- Desarrollo con BMAD.
- Web para celular y computador; ventas al detalle y al por mayor.
- Catálogo, carro, base de datos y panel de administración del dueño.
- Demo de tecnología y accesorios de celular.

## Ejemplo de precios ficticio

Cable USB-C, unidad base: un cable.

| Cantidad del mismo artículo | Precio por unidad | Ejemplo de total |
| --- | ---: | ---: |
| 1–5 | $3.000 CLP | 5 × $3.000 = $15.000 |
| 6–23 | $2.500 CLP | 6 × $2.500 = $15.000 |
| 24 o más | $2.000 CLP | 24 × $2.000 = $48.000 |

Propuesta de regla: el tramo alcanzado se aplica a todas las unidades de esa línea. No son precios reales ni un margen comercial recomendado. Compatibilidad, potencia o certificaciones solo se publicarán con datos comprobados del comercio o fabricante.

## Datos iniciales propuestos

- SKU del artículo vendible, nombre, categoría y descripción.
- Fotografías y texto alternativo; contenido autorizado para su uso.
- Compatibilidad o modelo, conector y otras características pertinentes al artículo.
- Precio en CLP, tramos de cantidad, estado de publicación y existencias.
- Artículos que difieren en modelo, color o conector se identifican inequívocamente; compartir stock entre variantes exige una decisión expresa.

## Inventario y pedidos

Precio y stock se validarán en el servidor. El pedido conservará una copia de los datos de los productos y los precios aplicados a la compra. Un reintento no debe crear dos pedidos ni descontar dos veces.

La política concreta de reserva no está decidida. Debe establecer cuándo se compromete stock, si vence la reserva, cómo se registra el pago y cómo se libera al cancelar. Se cerrará y probará antes de aceptar ventas reales.

## Referencias oficiales revisadas

Consulta: 2026-10-16. Investigación acotada de funciones, sin comparación de precios o recomendación de contratación.

- [Shopify: administración de productos](https://help.shopify.com/es/manual/products).
- [Shopify: catálogos y precios B2B](https://help.shopify.com/en/manual/b2b/catalogs/index).
- [Jumpseller: configuración de productos](https://jumpseller.cl/support/product/).
- [Jumpseller: inventario](https://jumpseller.cl/support/inventory-management/).
- [Jumpseller: funciones B2B](https://jumpseller.cl/support/b2b/).

Ambas plataformas documentan catálogo, administración y funciones de venta B2B; su disponibilidad depende de las condiciones de cada plataforma. La hipótesis de diferenciación de OxmanIT es el acompañamiento y la adaptación a la operación del comercio. No se ha demostrado superioridad, demanda ni disposición de pago.

## Validación comercial pendiente

Conversaciones propuestas, aún no realizadas: conocer cómo un comerciante recibe pedidos de regiones, actualiza stock y carga productos. Mostrar la demo y acordar una prueba antes de decidir precios o escalar.

No se han contratado servicios, registrado dominios, publicado una tienda ni conectado pagos. Proveedores, costos e impuestos siguen pendientes de definición.
