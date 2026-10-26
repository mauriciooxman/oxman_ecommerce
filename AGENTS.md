# Oxman Ecommerce — contexto para continuar

Leer README.md, docs/intake.md y docs/estado-del-proyecto.md antes de implementar. Los documentos de planificación están en `_bmad-output/planning-artifacts/`.

## Decisiones del usuario

Mauro desarrolla un producto de OxmanIT para tiendas de Meiggs. Nombre comercial: Oxman Ecommerce; proyecto: oxman_ecommerce. Aplicación web para venta al detalle y mayorista con catálogo, carro, datos persistentes y panel privado del dueño. Primera demo de tecnología y accesorios de celular. Usar BMAD; está instalado localmente para Codex. Conversación y documentos en español.

## Estado y disciplina

Los requisitos iniciales son borradores: distinguir decisiones del usuario, propuestas y supuestos. No describir documentos como funcionalidad implementada. No se ha elegido proveedor, pasarela de pago, transportista, arquitectura ni comercio piloto.

No atribuir ventas, entrevistas, precios reales o certificaciones a los productos ficticios. Antes de operar con datos reales deben resolverse autenticación, separación de datos entre tiendas, reservas de stock, pagos y despacho.

Los descuentos al detalle y por mayor comparten inventario. La regla propuesta es un precio unitario por tramo sobre todas las unidades del mismo artículo. Validar dinero, cantidades y stock en el servidor. Las ventas presenciales se registran para mantener las existencias.

Para continuar BMAD, revisar el catálogo local `_bmad/_config/bmad-help.csv`. Los flujos y sus instrucciones viven en `.agents/skills/`; no sustituir su ejecución con una afirmación de cumplimiento. Conservar los borradores y memlogs para retomar sin repetir el descubrimiento ya realizado.

## Preparación de uv en Windows

**Actualización:** el chat de implementación recibió `Acceso denegado` incluso con preparación en línea. La solución inferior solo está comprobada en el chat de diagnóstico. Leer primero el estado actual al inicio de `docs/solucion-uv-windows.md`; diagnosticar acceso al ejecutable y solicitar el permiso mínimo mediante mecanismos admitidos en el chat afectado antes de más intentos. No repetir el generador ni trasladar binarios para sortear el bloqueo.

Leer `docs/solucion-uv-windows.md` y usar su preparación con comandos en línea en la misma invocación de PowerShell que el comando oficial del SKILL.md. Esta ruta reemplaza la carga de `tools/Initialize-BmadEnvironment.ps1`, bloqueada por la política del otro chat. No cargar `.ps1`, cambiar políticas ni ejecutar su contenido mediante evaluadores. Preparar PATH y temporales con los comandos explícitos documentados, sin cambiar el directorio actual; conservar el comando oficial, leer su workflow generado si tiene éxito y detener ese flujo si falla. No sustituir uv por Python directo. La ejecución se validó en este chat con salida 0; el otro chat debe comprobarla en su propio proceso.
