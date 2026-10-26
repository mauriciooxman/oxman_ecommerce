---
title: "Oxman Ecommerce — definición del producto"
status: draft
created: 2026-10-16
updated: 2026-10-16
owner: Mauro / OxmanIT
---

# Oxman Ecommerce

## Qué vamos a crear

Una aplicación web de OxmanIT para que comercios de Meiggs tengan su propia tienda online y administren productos, precios, existencias y pedidos. Permitirá vender al detalle y al por mayor en un mismo catálogo, desde el celular o computador.

Cada comercio mostrará su propia marca. Oxman Ecommerce será el producto reutilizable que OxmanIT configura y mantiene. El primer ejemplo será una tienda ficticia de tecnología y accesorios de celular, rubro elegido por Mauro.

## Problema y evidencia disponible

Mauro visitó Meiggs y observó locales que promocionan sus productos mediante redes sociales. Quiere ofrecerles un canal de compra organizado para clientes que compran a distancia, especialmente desde regiones.

Esa observación es nuestro punto de partida. Aún no hay entrevistas con comerciantes ni evidencia de ventas perdidas o disposición de pago. [SUPUESTO] Consultar precios, disponibilidad y cantidades por mensajes genera trabajo que una tienda online podría reducir. El piloto debe comprobarlo.

## Para quién

- **Comprador al detalle:** encuentra un accesorio compatible, conoce su precio y envía un pedido con pocas unidades.
- **Comprador mayorista:** revisa los descuentos y mínimos por producto, combina artículos y entiende el total antes de pedir.
- **Dueño del comercio:** mantiene su catálogo desde el celular, registra movimientos de stock y prepara pedidos.
- **OxmanIT:** configura cada tienda y ofrece mantenimiento y soporte con un alcance acordado.

## Experiencia principal

El dueño publica un cable con fotografías, descripción, tipo de conector, precio al detalle, descuentos por cantidad y unidades disponibles. El comprador lo encuentra, elige una cantidad y ve el precio que corresponde. En el carro revisa productos, cantidades y total; completa sus datos y selecciona retiro o despacho, según lo habilitado por la tienda. El dueño recibe el pedido y registra su avance.

Este recorrido es una propuesta basada en la conversación, no una sesión observada con un comprador. [SUPUESTO] Los descuentos dependen de la cantidad del mismo producto; no se suman artículos diferentes para alcanzar un tramo.

## Alcance de la primera versión

1. Catálogo adaptable al celular, categorías, búsqueda, fotografías, disponibilidad y compatibilidad cuando corresponda.
2. Carro con precios visibles al detalle y por cantidad; compra como invitado.
3. Registro de pedidos con contacto y datos de entrega. El despacho pendiente de cotización debe indicarse antes de confirmar.
4. Acceso privado del dueño para gestionar productos, imágenes, precios, inventario y estados de pedidos.
5. Inventario único por producto vendible; las ventas presenciales también deben registrarse.
6. Tiendas con identidad y datos separados, aunque compartan la base del producto.

Las presentaciones de paquete o caja tendrán una equivalencia explícita en unidades. [SUPUESTO] La demo comenzará con unidades y descuentos por cantidad; cajas, paquetes y variantes más complejas se incorporarán cuando el piloto las necesite y se definan sus reglas.

Quedan fuera inicialmente: marketplace que mezcle comercios, ventas a crédito, integración automática con cajas físicas, facturación tributaria automática y precios negociados por comprador. Los pagos y transportistas conectados se elegirán antes de habilitar esas funciones.

## Cómo evaluaremos el piloto

Metas propuestas para la prueba, aún no acordadas con un comercio:

- Un dueño carga y edita un producto desde el celular sin intervención de OxmanIT.
- Un comprador completa un pedido con venta al detalle y descuentos mayoristas, entendiendo el total y la condición de despacho.
- Cambiar la cantidad aplica el tramo correcto y confirma el stock disponible en el servidor.
- Dos pedidos simultáneos no pueden comprometer la misma última unidad.
- El dueño registra una venta presencial y cancela un pedido sin duplicar existencias.

Como contrapeso, registraremos consultas que siguen necesitando mensajes, diferencias entre stock físico y publicado y errores de compatibilidad. No atribuiremos ingresos a la web sin evidencia.

## Propuesta comercial a validar

[SUPUESTO] OxmanIT cobrará una configuración inicial y una mensualidad por alojamiento, mantenimiento y soporte. Los precios, carga incluida, horas de soporte, propiedad de datos y condiciones de salida todavía deben definirse. No se prometen aumentos de ventas.

La propuesta a probar es una puesta en marcha acompañada y una operación sencilla. Catálogo, carro, inventario y precios por volumen ya existen en otras plataformas; no son funciones exclusivas de OxmanIT. El addendum conserva las fuentes oficiales revisadas.

## Pendientes y siguiente entrega

Mauro debe seleccionar un comercio piloto y validar cobro, despacho, reserva de stock y presentaciones de los productos. Estos puntos permiten preparar una demo con datos ficticios, pero condicionan la primera operación real.

La siguiente entrega es un borrador de requisitos del piloto, con reglas verificables de precios, inventario y separación entre tiendas. Después se diseñarán las pantallas y se elegirá la arquitectura antes de construir la primera compra completa.
