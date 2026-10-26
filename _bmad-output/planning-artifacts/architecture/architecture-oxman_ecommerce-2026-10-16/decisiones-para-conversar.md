# Arquitectura: primera conversación

Fecha: 2026-10-16. Para Mauro, único desarrollador. Propuesta en descubrimiento guiado; no hay versiones fijadas ni autorización para contratar servicios. ARCHITECTURE-SPINE.md conserva solo metadatos hasta resolver las decisiones que condicionan la construcción.

## Dirección actual indicada por Mauro

Priorizar **PHP con Laravel, MySQL y Blade**, tienda y administración en una sola aplicación para hosting compartido en **SolucionHost**, por experiencia y costo. Procurar evitar servicios permanentes adicionales en la primera versión. MySQL pasa a ser el motor propuesto; MariaDB deja de ser alternativa activa. Podemos desarrollar y probar localmente mientras comprobamos el servidor. Ver [lista breve y evidencia del hosting](requisitos-hosting.md).

La propuesta interna es un monolito modular: identidad/tienda, catálogo/precios, pedidos e inventario; una aplicación desplegable. Pedido coordina confirmación y reserva dentro de una transacción MySQL; inventario es la única autoridad de movimientos y reservas. Blade usa peticiones PHP normales y los recursos CSS/JS se compilan localmente. Sesiones/caché en archivos o DB son supuestos a evaluar. Sin dependencia inicial de Redis, Node en producción, WebSockets ni workers residentes. Un fallo de notificación no debe duplicar ni invalidar un pedido ya confirmado.

La página oficial anuncia PHP, MySQL, cPanel, cron y respaldos diarios, pero no acredita versiones ni límites de la cuenta. La referencia técnica Laravel 13 es candidata, no una versión elegida. Quedan abiertos autenticación concreta, política de reserva, aislamiento por comercio, operación y compatibilidad del plan. No se han realizado pruebas funcionales o remotas ni modificado el sitio anterior.

## Límites que conservamos

Web móvil y computador, base reutilizable para comercios con identidad y datos separados. Catálogo, carro, pedido, administración y venta presencial comparten inventario. El servidor valida autorización, cantidades, dinero y disponibilidad; el navegador no es la autoridad. Pedido conserva precios históricos y los reintentos no duplican pedidos ni movimientos. No se almacenan tarjetas en la demo. Son requisitos documentados; aún no existen implementación ni pruebas.

## Primera elección: organización de la aplicación

| Alternativa | Consecuencia para este proyecto | Cuándo tendría sentido |
| --- | --- | --- |
| Aplicación única organizada por módulos (monolito modular) | Un despliegue de aplicación; catálogo, precios, pedidos e inventario tienen responsabilidades separadas dentro del mismo sistema. Permite coordinar pedido y reserva en una transacción de base de datos. | Propuesta inicial para que Mauro implemente y mantenga el piloto solo. |
| Frontend y API separados | Dos componentes desplegables y contrato de comunicación explícito; añade coordinación de autenticación, errores y versiones. | Si se necesitan otros clientes o Mauro ya trabaja cómodamente así. |
| Microservicios | Añade comunicación, operación y consistencia entre procesos. | Diferir hasta que haya una necesidad comprobada de escala o equipos independientes. |

**Dirección vigente:** una aplicación para tienda y administración, según Mauro. Monolito modular es la organización interna propuesta. No determina si cada tienda tendrá base propia o datos compartidos con aislamiento: esa es otra decisión.

Dentro de esa propuesta, conversar sobre módulos de identidad/tienda, catálogo/precios, pedidos e inventario. Inventario debe ser la única autoridad para reservar, liberar y consumir unidades; pedido coordina esas operaciones sin editar saldos por su cuenta. La frontera y la transacción definitiva se registrarán en AD estables una vez acordadas.

## Evaluación tecnológica anterior (antecedente)

Esta exploración precede la preferencia posterior de Mauro por Laravel/MySQL/Blade. Next.js/Supabase deja de ser candidato activo; Livewire y un starter concreto no están elegidos. Blade no obliga a incorporar Livewire. Conservar las siguientes referencias como antecedente, no como selección vigente.

Consulta de fuentes primarias: 2026-10-16. No se fijan versiones ni se instala nada.

- Si Mauro prefiere PHP y lógica en servidor, recomiendo empezar la evaluación por el [starter oficial Laravel con Livewire](https://laravel.com/starter-kits). El catálogo oficial ofrece esta opción y autenticación integrada. Esto da una base de acceso; el aislamiento por tienda y las reglas de inventario deben construirse y probarse.
- Si Mauro prefiere React y TypeScript, comparar el [starter Next.js con Supabase documentado por Supabase](https://supabase.com/docs/guides/getting-started/quickstarts/nextjs). La guía usa `with-supabase` e incluye configuración de autenticación con cookies. Implica evaluar explícitamente proveedor, políticas de acceso y ubicación transaccional de las reglas de negocio antes de adoptarlo.

La preferencia ya está registrada arriba. El starter reduce trabajo inicial; no implementa el ecommerce ni demuestra integridad o seguridad del piloto.

## Decisiones por cerrar, en orden de dependencia

1. Verificar requisitos de SolucionHost y elegir versiones de Laravel/PHP/MySQL; avanzar localmente sin confundirlo con compatibilidad del servidor.
2. A6: reservar al crear pedido, sin reserva en el carro y sin vencimiento automático inicial. Sigue siendo una propuesta del PRD; confirmar o modificar junto con estados y cancelación.
3. Aislamiento: despliegue/base separados por comercio o aplicación compartida con pertenencia validada y aislamiento en datos. La demo de una tienda no elimina las pruebas de acceso cruzado.
4. Acceso y recuperación del dueño, y facultades expresas de OxmanIT; archivos públicos frente a recursos privados.
5. Cobro, despacho y aceptación de cotización; integrar proveedores solo después de definir el flujo. No mostrar total definitivo con despacho pendiente.
6. Entornos de desarrollo y demo separados de producción, secretos fuera del repositorio y del espacio público, migraciones y pruebas; antes de datos reales acordar presupuesto, respaldos, restauración, retención y operación. SolucionHost es el proveedor existente preferido; su compatibilidad sigue pendiente. No hay ampliación de plan autorizada.

Antes de cerrar el contrato de arquitectura se comprobarán versiones y valores predeterminados del starter elegido, se definirán propiedad de datos y transacciones, y se ejecutarán las revisiones del flujo BMAD. No se ha realizado todavía esa finalización.
