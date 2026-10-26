# Contexto inicial

Fecha: 2026-10-16. Fuente: conversación con Mauro, fundador de OxmanIT.

Rubro confirmado por Mauro para la primera demo: tecnología y accesorios de celular.

## Problema observado

Mauro visitó Meiggs y observó comercios que muestran su oferta mediante redes sociales. Quiere ofrecerles una tienda propia para facilitar la compra a distancia y ampliar la clientela de OxmanIT.

## Hipótesis a validar

1. Los comercios reciben consultas repetidas sobre disponibilidad y precios.
2. Sus compradores de regiones necesitan armar pedidos sin intercambiar mensajes por cada producto.
3. Los dueños pueden mantener un catálogo y registrar cambios de stock desde el celular.
4. Existe disposición a pagar por configuración inicial y un servicio mensual con alcance definido.

Estas hipótesis no equivalen a resultados de entrevistas ni a evidencia de demanda.

## Personas

- Comprador al detalle: revisa productos, compra cantidades pequeñas y pide entrega o retiro.
- Comprador mayorista: revisa tramos de precio, mínimos y cantidades por presentación.
- Dueño: carga productos y fotos, ajusta stock, prepara pedidos y registra ventas presenciales.
- OxmanIT: configura cada tienda y mantiene el producto; cualquier acceso a datos de comercios requiere roles y límites definidos.

## Reglas propuestas

- Moneda inicial: CLP. Ejemplos y datos de demostración deben indicarse como ficticios.
- El tramo de precio aplica a la cantidad del mismo producto; no mezcla productos distintos.
- Las presentaciones de caja o paquete tienen una equivalencia explícita en unidades base.
- El precio y el stock definitivos se calculan en el servidor al confirmar un pedido.
- Un pedido registra una copia de los precios aplicados; editar un producto no cambia pedidos previos.
- La reserva de stock, su vencimiento y la devolución por cancelación deben definirse y probarse antes de la primera venta real.
- El stock físico requiere registrar también las ventas presenciales.
- Cada consulta y operación del dueño debe validar a qué tienda pertenece el usuario.
- Costo de despacho desconocido se muestra como pendiente de cotización; nunca como envío gratis implícito.

## Fuera del piloto propuesto

Marketplace que mezcle varios comercios, ventas a crédito, facturación tributaria automática, integraciones con cajas existentes, precios negociados por cliente y transportistas conectados automáticamente.

## Decisiones pendientes

- Comercio y rubro para el piloto; productos y fotografías autorizados.
- Preferencias de marca visual de OxmanIT.
- Cobro inicial: confirmación manual, pasarela de pago o ambos.
- Despacho: cotización manual y/o tarifas conocidas; zonas cubiertas y retiro.
- Política de reserva de stock y cancelaciones.
- Tratamiento de impuestos y documentos comerciales, a verificar antes de operar.
- Presupuesto de infraestructura, dominio y servicio mensual.

Para avanzar con la planificación no es necesario resolver aún todas estas decisiones. Deben quedar explícitas como supuestos y condiciones previas al lanzamiento.
