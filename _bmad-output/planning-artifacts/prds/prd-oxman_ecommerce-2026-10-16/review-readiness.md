# Revisión del borrador de requisitos

Fecha: 2026-10-16, America/Santiago. Revisión proporcional a un PRD inicial, ejecutada localmente sin revisores adicionales. Este documento ayuda a Mauro y a los futuros responsables de UX y arquitectura a reconocer qué está definido y qué sigue pendiente.

## Dictamen

Útil como borrador de discusión; no aprobado para operación real. Los 14 requisitos tienen consecuencias comprobables y conservan el alcance elegido. Reserva, cobro y despacho requieren decisión humana antes de cerrar los flujos operativos.

## Reconciliación de fuentes

- **Brief:** catálogo, carro, mayorista/detalle, acceso móvil, inventario físico/online e identidad por tienda aparecen en FR-1 a FR-14. La hipótesis comercial sigue siendo hipótesis; no hay entrevistas ni métricas inventadas.
- **Addendum del brief:** ejemplos de precio reproducidos y ampliados con límites de tramos en FR-3; cálculo del servidor, copia histórica e idempotencia en FR-5 y FR-8; compatibilidad y fotos autorizadas en FR-2 y FR-11. Fuentes oficiales permanecen en el addendum original, sin nueva comparación o selección de proveedores.
- **Intake:** preservados CLP, aislamiento y despacho pendiente. Reconciliada su mención antigua de rubro pendiente con la selección confirmada de tecnología y accesorios de celular. Sigue pendiente elegir un comercio real. No se han añadido promesas de ingresos.
- **Memoria:** las decisiones confirmadas corresponden al brief; A1–A6 agrupan hipótesis y propuestas, OQ-1–OQ-7 especifican revisión humana. No fue necesario duplicar contenido técnico en otro addendum.

## Rúbrica de calidad

| Dimensión | Juicio | Evidencia / límite |
| --- | --- | --- |
| Preparación para decidir | Adecuada para borrador | §7 identifica responsable y condiciones; A6 no se disfraza de decisión aprobada. |
| Sustancia | Sólida | Cálculos concretos, doble confirmación de última unidad, archivos inválidos y acceso entre tiendas. |
| Coherencia estratégica | Adecuada | Funciones sirven al pedido a distancia y administración sencilla; diferenciación y disposición de pago sin validar. |
| Claridad de aceptación | Adecuada | FR-1–FR-14 contienen criterios. NFR-1 ofrece prueba móvil; rendimiento depende de escala acordada, sin umbral inventado. |
| Honestidad de alcance | Sólida | Exclusiones explícitas, A1–A6 indexados, siete decisiones pendientes. |
| Uso posterior | Adecuado con límites | Glosario, IDs estables y reglas de inventario. Bloquear cierre de historias de cobro/reserva hasta resolver OQ-2/OQ-3. |
| Ajuste de forma | Adecuado | Escenarios propuestos cortos sustituyen recorridos observados inexistentes; UX debe validarlos con personas reales. |

Hallazgos que requieren revisión humana: **alto**, OQ-2/OQ-3 condicionan consistencia operativa y dinero; **medio**, OQ-5 condiciona datos reales; **medio**, objetivos de rendimiento y métricas comerciales no acordados. Se conservan como pendientes, no se resuelven por inferencia. No se identifican defectos críticos de coherencia en el borrador; esto no equivale a probar una implementación.

## Revisión editorial: estructura y después prosa

Modelo: estratégico, conclusión primero y requisitos agrupados. Contenido: documento, lectores humanos. Medición con `word_metrics.py`: 1.754 palabras; objetivo de 1.500–2.000 cumplido.

| Pass | Original Text | Revised Text | Changes |
| --- | --- | --- | --- |
| structure | §7 y §8, 195 palabras | PRESERVE | Decisiones y supuestos se consultan por separado; no eliminar por aparente duplicación. Impacto: 0 palabras. |

Prosa: sin cambios necesarios para comprensión. Se preservan las etiquetas ASSUMPTION y open_questions para distinguir inferencias de confirmaciones. Una recomendación de preservación, reducción estimada 0 palabras (0 %), sin pérdida de contexto. No se solicitó revisión adversarial completa ni validación formal independiente.

## Siguiente condición de avance

Mauro revisa el alcance y confirma o modifica A2–A6. Puede prepararse diseño exploratorio con datos ficticios; no cerrar arquitectura, comprometer servicios ni aceptar pedidos reales con las preguntas bloqueantes abiertas. El PRD conserva `status: draft` y el resultado headless es `partial`.
