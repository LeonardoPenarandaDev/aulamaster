Sistema de Gestión Escolar

1. Descripción general

Sistema web para la gestión académica, administrativa y financiera de un instituto educativo.

La plataforma permitirá administrar estudiantes, profesores, cursos, niveles, aulas, horarios, clases, asistencia, horas académicas, evaluaciones, recuperaciones, matrículas, promociones, referidos, pagos y progreso académico.

El sistema deberá garantizar trazabilidad e integridad de los registros importantes, especialmente asistencia, evaluaciones, pagos y resultados académicos.

2. Objetivo general

Desarrollar una plataforma centralizada que permita al instituto gestionar el ciclo completo del estudiante:

Registro
   ↓
Matrícula
   ↓
Asignación de curso/nivel
   ↓
Programación de clases
   ↓
Asistencia
   ↓
Acumulación de horas
   ↓
Evaluaciones
   ↓
Recuperaciones, si aplica
   ↓
Aprobación / Reprobación
   ↓
Siguiente nivel

3. Roles del sistema

Implementación: roles gestionados con spatie/laravel-permission (roles admin, profesor, estudiante) y reforzados con Policies de Laravel por modelo, de modo que las reglas de acceso descritas abajo se validen tanto en la interfaz (Vue/Inertia) como en el backend.

3.1 Administrador

Puede:

Crear y administrar cursos.

Crear niveles.

Configurar intensidad horaria.

Crear aulas.

Programar clases.

Asignar profesores.

Registrar estudiantes.

Matricular estudiantes.

Configurar evaluaciones.

Registrar y consultar pagos.

Crear promociones.

Gestionar referidos.

Configurar recuperaciones.

Consultar reportes.

Consultar auditoría.

Gestionar usuarios y permisos.

3.2 Profesor

Puede:

Consultar sus clases.

Consultar sus estudiantes.

Registrar asistencia.

Consultar horas acumuladas.

Registrar resultados de evaluaciones.

Consultar estudiantes en recuperación.

No puede modificar directamente registros históricos confirmados.

3.3 Estudiante

Puede consultar:

Datos personales.

Código de estudiante.

Matrículas.

Cursos y niveles.

Horario.

Aula asignada.

Profesor.

Asistencia.

Horas acumuladas.

Evaluaciones.

Recuperaciones.

Pagos.

Promociones/descuentos aplicados.

Estado de cuenta.

Progreso académico.

4. Cursos y niveles

El administrador podrá crear cursos.

Ejemplo:

Inglés
├── A1
├── A2
├── B1
├── B2
├── C1
└── C2

Cada nivel tendrá configuración independiente:

Nombre.

Código.

Curso.

Duración.

Horas semanales.

Horas mensuales.

Horas totales requeridas.

Nota mínima.

Evaluaciones.

Competencias.

Fecha de inicio.

Fecha de finalización.

Estado.

5. Ejemplo académico

Estudiante:

Nombre: Juan López
Código: 1565
Curso: Inglés
Nivel: A1

Configuración:

Horas semanales: 8
Horas mensuales: 32
Duración: 4 meses
Horas requeridas: 128

Evaluaciones:

1. Listening
2. Speaking
3. Reading
4. Writing

Para aprobar el nivel, el sistema deberá comprobar las reglas configuradas:

Cumplimiento de horas.

Presentación de evaluaciones.

Notas mínimas.

Promedio, si aplica.

Cualquier otro requisito configurado.

6. Estudiantes

Cada estudiante tendrá:

Código único.

Nombre.

Documento.

Correo.

Teléfono.

Información de contacto.

Estado.

Fecha de registro.

Ejemplo:

Código: 1565
Nombre: Juan López
Estado: Activo

7. Matrículas

La matrícula relacionará al estudiante con un curso y nivel.

Debe almacenar:

Estudiante.

Curso.

Nivel.

Fecha de matrícula.

Fecha de inicio.

Fecha estimada de finalización.

Fecha real de finalización.

Estado.

Horas requeridas.

Horas acumuladas.

Horas pendientes.

Precio base.

Precio final.

Promociones aplicadas.

Descuentos aplicados.

Estados:

Pendiente
Activa
En recuperación
Extendida
Finalizada
Cancelada
Aprobada
Reprobada

8. Aulas

El administrador podrá crear las aulas físicas del instituto.

Cada aula tendrá:

Nombre.

Código.

Capacidad.

Ubicación.

Piso.

Estado.

Observaciones.

Ejemplo:

Nombre: Salón 101
Código: AULA-101
Capacidad: 25
Ubicación: Primer piso
Estado: Disponible

También podrán utilizarse nombres personalizados:

Laboratorio de Sistemas
Sala Multimedia
Aula 201
Salón Principal

9. Programación de clases

El administrativo podrá programar cada clase indicando:

Curso.

Nivel.

Profesor.

Aula.

Fecha.

Hora de inicio.

Hora de finalización.

Duración.

Estado.

Observaciones.

Ejemplo:

Curso: Elementary 2
Profesor: Carlos Rodríguez
Aula: Salón 101
Fecha: 26/08/2026
Hora: 2:00 PM - 3:00 PM

La clase debe quedar almacenada como un registro independiente.

10. Reutilización de aulas

Un aula puede utilizarse varias veces durante el día.

Ejemplo válido:

SALÓN 101

14:00 - 15:00
Elementary 2
Profesor: Carlos

15:00 - 16:00
Elementary 2
Profesor: María

No existe conflicto porque los horarios no se superponen.

11. Validación de conflictos

Al programar una clase, el sistema deberá verificar:

Que el aula esté disponible.

Que el profesor esté disponible.

Que no exista otra clase en el mismo horario.

Que la fecha sea válida.

Que el horario de inicio sea menor que el de finalización.

No debe permitir:

Salón 101
14:00 - 15:00
Elementary 2

Salón 101
14:30 - 15:30
Elementary 3

porque existe superposición entre 14:30 y 15:00.

También debe impedir que el mismo profesor esté en dos clases simultáneamente.

12. Horarios recurrentes

El administrador podrá configurar clases recurrentes.

Ejemplo:

Elementary 2

Lunes      2:00 PM - 3:00 PM
Miércoles  2:00 PM - 3:00 PM
Viernes    2:00 PM - 3:00 PM

Aula:
Salón 101

Profesor:
Carlos Rodríguez

El sistema podrá generar las clases automáticamente para un periodo determinado.

13. Calendario académico

El administrador deberá contar con un calendario para consultar:

Clases.

Aulas ocupadas.

Profesores.

Cursos.

Horarios.

Clases canceladas.

Clases reprogramadas.

El calendario debe permitir filtrar por:

Fecha.

Aula.

Profesor.

Curso.

Nivel.

14. Asistencia

La asistencia deberá estar relacionada con una clase específica.

Ejemplo:

Clase #4582

Fecha: 26/08/2026
Hora: 2:00 PM - 3:00 PM
Curso: Elementary 2
Profesor: Carlos Rodríguez
Aula: Salón 101

Lista:

Juan López       1565    ✓
Pedro Pérez      1566    ✓
Ana Gómez        1567    ✗

Estados:

Presente
Ausente
Excusado

15. Registro de asistencia

Al registrar asistencia se debe almacenar:

Clase.

Estudiante.

Profesor.

Fecha de clase.

Hora de registro.

Estado.

Usuario que registró.

Fecha y hora del servidor.

La fecha y hora de auditoría deben provenir del servidor.

16. Inmutabilidad de asistencia

Una asistencia confirmada no podrá editarse o eliminarse directamente.

Ejemplo:

Estudiante: Juan López
Estado: Presente
Profesor: Carlos Rodríguez
Fecha: 26/08/2026
Hora de registro: 14:05:32

Una vez confirmada:

Editar: NO
Eliminar: NO

Si se requiere corregirla, deberá existir un proceso de corrección auditado.

El registro original deberá permanecer.

17. Horas académicas

Las horas acumuladas deberán calcularse a partir de las clases y asistencias válidas.

Ejemplo:

Clase 1 → 2 horas → Presente
Clase 2 → 2 horas → Presente
Clase 3 → 2 horas → Ausente
Clase 4 → 2 horas → Presente

Total:

6 horas acumuladas

Fórmula:

Horas pendientes =
Horas requeridas - Horas acumuladas

Porcentaje:

Progreso =
(Horas acumuladas / Horas requeridas) × 100

El sistema deberá evitar contabilizar horas de una asistencia ausente.

18. Dashboard del estudiante

Ejemplo:

JUAN LÓPEZ
Código: 1565

Curso: Inglés
Nivel: A1

Horas:
104 / 128

Horas pendientes:
24

Progreso:
81.25%

Asistencia:
87%

Evaluaciones:
3 / 4

Estado:
En curso

19. Evaluaciones

Cada nivel podrá tener evaluaciones configurables.

Para Inglés A1:

Listening
Speaking
Reading
Writing

Cada evaluación almacenará:

Nombre.

Competencia.

Nivel.

Nota mínima.

Fecha.

Profesor evaluador.

Resultado.

Observaciones.

20. Validación para presentar evaluación

El sistema deberá validar si el estudiante cumple los requisitos.

Ejemplo:

[✓] Horas completas
[✓] Matrícula activa
[✓] Curso vigente
[✓] Requisitos académicos

Si no cumple:

NO APTO PARA PRESENTAR

Faltan:
- 24 horas académicas

Las reglas deberán ser configurables.

21. Aprobación de nivel

Ejemplo:

Horas: 128 / 128 ✓

Listening: 85 ✓
Speaking: 78 ✓
Reading: 91 ✓
Writing: 88 ✓

Resultado:
APROBADO

Siguiente nivel:
A2

Si pierde una o más evaluaciones, entra al proceso de recuperación.

22. Recuperaciones

Por cada evaluación perdida:

Primer intento original.

Primera recuperación gratuita.

Segunda recuperación pagada.

Tercera recuperación pagada.

Máximo de 2 recuperaciones pagadas por evaluación.

Configuración inicial:

Recuperaciones gratuitas: 1
Recuperaciones pagadas máximas: 2

Debe ser configurable por el administrador.

23. Ejemplo de recuperación

Juan pierde Speaking:

Intento #1
Nota: 55
Resultado: Reprobado
Costo: $0

Primera recuperación:

Recuperación #1
Nota: 62
Resultado: Reprobado
Costo: $0

Segunda recuperación:

Recuperación #2
Nota: 65
Resultado: Reprobado
Costo: $50.000

Tercera recuperación:

Recuperación #3
Nota: 72
Resultado: Aprobado
Costo: $50.000

Total de recuperaciones pagadas:

$100.000

24. Pago de recuperación

Una recuperación pagada deberá estar vinculada a un pago.

El sistema deberá poder configurar:

Valor recuperación: $50.000

El estudiante no podrá realizar una recuperación pagada si el requisito de pago no se encuentra satisfecho, según las reglas del instituto.

Cada pago deberá conservar su historial.

25. Periodo de recuperación

El administrador podrá configurar el plazo para presentar una recuperación.

Ejemplo:

Periodo:
15 días

Evaluación perdida:
18/08/2026

Fecha límite:
02/09/2026

El sistema deberá notificar al estudiante cuando se acerque la fecha límite.

26. Continuidad de clases durante recuperación

Un estudiante que pierda una evaluación deberá continuar asistiendo a clases mientras esté en recuperación, según las reglas del instituto.

Ejemplo:

Nivel: A1

Horas requeridas: 128
Horas completadas: 128

Evaluación Speaking:
Reprobada

Estado:
En recuperación

Debe continuar asistiendo:
SÍ

Las nuevas asistencias deberán seguir registrándose.

27. Extensión del nivel

Cuando un estudiante necesite recuperación, el nivel podrá extenderse.

Ejemplo:

Fecha original:
30/08/2026

Estado:
En recuperación

Nueva fecha:
05/09/2026

Motivo:
Recuperación de evaluación

La extensión deberá quedar registrada con:

Fecha anterior.

Nueva fecha.

Motivo.

Usuario que realizó la extensión.

Fecha y hora.

Observaciones.

28. Estados académicos

En curso
Apto para evaluación
Pendiente de evaluación
En recuperación
Extendido
Aprobado
Reprobado
Retirado

29. Precios

Los precios pueden variar dependiendo de promociones, descuentos o referidos.

El precio final de una matrícula o mensualidad no deberá depender exclusivamente del precio actual configurado en el curso.

Se debe conservar el precio realmente aplicado.

Ejemplo:

Precio base:             $300.000
Promoción:                -$50.000
Descuento por referido:   -$25.000
--------------------------------
Precio final:             $225.000

30. Promociones

El administrador podrá crear promociones.

Datos:

Nombre.

Descripción.

Tipo de descuento.

Valor.

Porcentaje.

Fecha de inicio.

Fecha de finalización.

Cursos aplicables.

Niveles aplicables.

Requisitos.

Cantidad máxima de usos.

Estado.

Ejemplo:

Nombre:
Promoción de inscripción

Descuento:
20%

Inicio:
01/09/2026

Fin:
30/09/2026

31. Sistema de referidos

Un estudiante podrá referir a una persona nueva.

Ejemplo:

Estudiante nuevo:
Pedro Pérez

Referido por:
Juan López
Código: 1565

El sistema podrá aplicar beneficios a uno o ambos estudiantes.

Ejemplo:

Juan López:
Descuento $50.000

Pedro Pérez:
Descuento $25.000

Debe conservarse la relación de referido.

32. Historial de precios

Si una promoción cambia o termina, los pagos históricos no deben cambiar.

Ejemplo:

Pago realizado el 05/08/2026

Precio base: $300.000
Promoción: $50.000
Total pagado: $250.000

Aunque posteriormente la promoción sea eliminada, el historial deberá seguir mostrando $250.000.

33. Pagos

Cada pago deberá almacenar:

Estudiante.

Matrícula.

Concepto.

Valor base.

Descuento.

Valor final.

Promoción.

Referido.

Fecha de pago.

Método de pago.

Comprobante.

Usuario que registró.

Fecha y hora de registro.

Estado.

Estados:

Pendiente
Pagado
Vencido
Anulado

Los pagos no deberán eliminarse físicamente.

34. Estado de cuenta

El estudiante podrá consultar:

Total facturado: $1.500.000
Total pagado:    $1.200.000
Saldo pendiente: $300.000

Estados:

Al día
Pendiente
Vencido

35. Dashboard administrativo

Debe mostrar indicadores como:

Estudiantes activos
Cursos activos
Profesores
Aulas
Clases del día
Asistencias
Evaluaciones pendientes
Estudiantes en recuperación
Estudiantes con mora
Ingresos del periodo

36. Dashboard del profesor

Mis cursos

Elementary 2
Elementary 3
A1

Clase de hoy:
Elementary 2
14:00 - 15:00
Salón 101

Estudiantes:
25

Asistencia:
22 presentes
3 ausentes

37. Dashboard del estudiante

Juan López
Código: 1565

Curso: Inglés A1

Próxima clase:
Elementary A1
26/08/2026
2:00 PM - 3:00 PM
Salón 101
Profesor: Carlos Rodríguez

Horas:
104 / 128

Evaluaciones:
3 / 4

Pagos:
Al día

38. Auditoría

Todas las operaciones importantes deberán generar registros de auditoría.

Debe almacenarse:

Usuario.

Rol.

Acción.

Módulo.

Registro afectado.

Fecha.

Hora.

IP.

Descripción.

Valores relevantes antes/después cuando aplique.

Especialmente:

Asistencia.

Evaluaciones.

Pagos.

Recuperaciones.

Extensiones.

Cambios de matrícula.

Promociones.

Referidos.

Programación de clases.

39. Integridad de información

Los registros críticos deberán manejarse de forma histórica.

No se deberán borrar físicamente:

Asistencias.

Evaluaciones.

Resultados.

Pagos.

Recuperaciones.

Auditorías.

Si existe una corrección, deberá generarse un nuevo registro o evento de corrección.

40. Reglas de negocio principales

Regla 1

Un estudiante necesita una matrícula activa para pertenecer a un nivel.

Regla 2

Las horas se acumulan mediante clases y asistencias válidas.

Regla 3

Una asistencia confirmada no puede modificarse directamente.

Regla 4

Una evaluación confirmada no puede modificarse directamente.

Regla 5

Un pago histórico no debe cambiar aunque cambien las promociones.

Regla 6

La primera recuperación es gratuita.

Regla 7

Se permiten hasta dos recuperaciones pagadas por evaluación.

Regla 8

Mientras el estudiante esté en recuperación, continúa asistiendo según las reglas del instituto.

Regla 9

El nivel puede extenderse durante un proceso de recuperación.

Regla 10

Una recuperación pagada debe estar vinculada al pago correspondiente.

Regla 11

El aula no puede tener dos clases simultáneas.

Regla 12

El profesor no puede estar asignado a dos clases simultáneas.

Regla 13

La asistencia debe estar vinculada a una clase concreta.

Regla 14

Los registros importantes deben identificar quién los creó y cuándo.

41. Modelo de datos inicial

Entidades principales:

users
roles
permissions

students
teachers

courses
levels
academic_periods

classrooms
class_schedules
classes (implementada como class_sessions / modelo ClassSession, ya que `class` es palabra reservada en PHP y no puede usarse como nombre de clase)

enrollments

attendance
attendance_corrections

evaluations
evaluation_attempts
evaluation_results
recovery_attempts

pricing
promotions
discounts
referrals

payments
payment_concepts

extensions

notifications
audit_logs

Relaciones principales:

COURSE
  │
  └── LEVEL
        │
        └── ENROLLMENT
              │
              └── STUDENT


CLASSROOM
   │
   └── CLASS
         ├── COURSE / LEVEL
         ├── TEACHER
         ├── DATE
         ├── START_TIME
         ├── END_TIME
         │
         └── ATTENDANCE
                │
                └── STUDENT


ENROLLMENT
   ├── PAYMENTS
   ├── EVALUATIONS
   ├── RECOVERIES
   ├── EXTENSIONS
   ├── PROMOTIONS
   └── REFERRALS


Todas las operaciones críticas
             ↓
         AUDIT_LOGS

Nota de implementación (inmutabilidad):
Los registros inmutables (asistencia, evaluaciones, pagos, recuperaciones) no deben usar SoftDeletes de Eloquent como mecanismo de corrección. En su lugar: un campo de estado (p. ej. confirmed) bloqueado a nivel de Policy/Form Request una vez confirmado, y tablas dedicadas de corrección (attendance_corrections y equivalentes) para registrar cualquier ajuste posterior sin alterar el registro original, según la sección 16.

42. Flujo completo

ADMINISTRADOR
      │
      ├── Crea curso
      ├── Crea nivel
      ├── Configura horas
      ├── Crea aulas
      ├── Crea profesores
      └── Programa clases
                │
                ↓
        REGISTRA ESTUDIANTE
                │
                ↓
             MATRÍCULA
                │
                ↓
          ASISTE A CLASES
                │
                ↓
        PROFESOR MARCA ASISTENCIA
                │
                ↓
          ACUMULA HORAS
                │
                ↓
       COMPLETA REQUISITOS
                │
                ↓
          PRESENTA EXAMEN
                │
          ┌─────┴─────┐
          ↓           ↓
       APRUEBA      PIERDE
          │           │
          ↓           ↓
    SIGUIENTE     RECUPERACIÓN
      NIVEL           │
                      ↓
                PRIMERA GRATIS
                      │
                ┌─────┴─────┐
                ↓           ↓
             APRUEBA      PIERDE
                │           │
                ↓           ↓
          SIGUIENTE      PAGA
             NIVEL         │
                           ↓
                    RECUPERACIÓN #2
                           │
                       ¿APRUEBA?
                       /                            SÍ         NO
                     │           │
                     ↓           ↓
               SIGUIENTE     PAGA
                  NIVEL         │
                                ↓
                         RECUPERACIÓN #3
                                │
                           ¿APRUEBA?
                           /                                SÍ         NO
                         │           │
                         ↓           ↓
                   SIGUIENTE      LÍMITE
                      NIVEL

43. Arquitectura propuesta

Para el MVP se recomienda:

Backend:
Laravel 13.x (PHP 8.3+)

Frontend:
Vue 3 + Inertia.js + Tailwind CSS (SPA servida por Laravel, sin API REST separada)

Autenticación base:
Laravel Breeze (stack Inertia + Vue) como punto de partida, extendido con los 3 roles del sistema.

Roles y permisos:
spatie/laravel-permission (roles: admin, profesor, estudiante; permisos granulares por módulo).

Auditoría:
Tabla audit_logs propia (evento + listener), ya que los campos requeridos en la sección 38 (valores antes/después, IP, módulo) son más específicos que lo que ofrece un paquete genérico de activity log.

Colas y notificaciones:
Laravel Queue (driver database en el MVP) + Laravel Notifications (canales database y mail).

Reportes:
barryvdh/laravel-dompdf (PDF) y maatwebsite/excel (Excel/CSV).

Base de datos:
MySQL 8 / MariaDB 10.6+

Servidor:
Linux

Web server:
Nginx o Apache (Laragon para entorno de desarrollo local)

Esta arquitectura permitirá desarrollar el sistema de forma rápida y mantener una estructura sencilla.

43.1 Convenciones y estructura del proyecto Laravel

app/Models
Un modelo Eloquent por entidad de la sección 41 (Student, Teacher, Course, Level, Classroom, ClassSchedule, ClassSession, Enrollment, Attendance, AttendanceCorrection, Evaluation, EvaluationAttempt, EvaluationResult, RecoveryAttempt, Pricing, Promotion, Discount, Referral, Payment, PaymentConcept, Extension, AuditLog).

resources/js/Pages
Vistas Vue de página completa, una carpeta por módulo (Students/, Attendance/, Evaluations/, Payments/, Scheduling/, etc.), renderizadas por controladores vía Inertia::render(), siguiendo la lista de módulos de la sección 44.

resources/js/Components
Componentes Vue reutilizables (tablas, formularios, modales, calendario, indicadores de dashboard) compartidos entre páginas.

app/Http/Controllers
Controladores clásicos de Laravel (uno por módulo) que preparan los datos y devuelven Inertia::render('Modulo/Vista', [...]); reemplazan a los componentes con estado que tendría Livewire.

app/Policies
Una Policy por modelo sensible, para reforzar en el backend las reglas de acceso por rol de la sección 3 (además de los permisos de spatie/laravel-permission).

app/Http/Requests
Form Requests para toda entrada de formularios (matrícula, asistencia, evaluaciones, pagos, etc.).

app/Actions (o app/Services)
Lógica de negocio compleja fuera de controladores/componentes: cálculo de horas acumuladas (sección 17), validación de conflictos de horario/aula/profesor (sección 11), flujo de recuperaciones (secciones 22-26), cálculo de precio final con promociones/descuentos (sección 29).

app/Events + app/Listeners
Para disparar el registro en audit_logs de forma desacoplada en las operaciones críticas listadas en la sección 38 (asistencia, evaluaciones, pagos, recuperaciones, extensiones, matrículas, promociones, referidos, programación de clases).

database/migrations
Una migración por tabla; los nombres ya listados en snake_case plural en la sección 41 siguen la convención estándar de Laravel.

database/seeders
Seeders para roles/permisos iniciales (spatie/laravel-permission) y datos de prueba (cursos, niveles, aulas).

44. Módulos del MVP

Módulo 1 — Autenticación

Login.

Logout.

Roles.

Permisos.

Módulo 2 — Estudiantes

CRUD.

Código único.

Estado.

Módulo 3 — Profesores

CRUD.

Asignación de cursos.

Módulo 4 — Cursos y niveles

Cursos.

Niveles.

Intensidad horaria.

Evaluaciones.

Módulo 5 — Aulas

Crear aulas.

Editar aulas.

Capacidad.

Estado.

Módulo 6 — Horarios y clases

Programación.

Horarios recurrentes.

Validación de conflictos.

Calendario.

Módulo 7 — Matrículas

Inscripción.

Nivel.

Fechas.

Precio.

Promociones.

Módulo 8 — Asistencia

Registro.

Horas.

Historial.

Auditoría.

Módulo 9 — Evaluaciones

Evaluaciones.

Resultados.

Competencias.

Aprobación.

Módulo 10 — Recuperaciones

Recuperación gratuita.

Hasta dos recuperaciones pagadas.

Control de pagos.

Fechas límite.

Extensiones.

Módulo 11 — Pagos

Pagos.

Historial.

Estado de cuenta.

Promociones.

Descuentos.

Módulo 12 — Referidos

Estudiante referente.

Nuevo estudiante.

Beneficios.

Historial.

Módulo 13 — Reportes

Estudiantes.

Asistencia.

Horas.

Evaluaciones.

Recuperaciones.

Pagos.

Cartera.

Aulas.

Clases.

Módulo 14 — Auditoría

Registro de operaciones.

Usuario.

Fecha.

Hora.

IP.

Historial.

45. Reportes

El sistema deberá permitir generar:

Listado de estudiantes.

Estudiantes por curso.

Estudiantes por nivel.

Asistencia por fecha.

Asistencia por profesor.

Asistencia por aula.

Horas acumuladas.

Estudiantes próximos a evaluación.

Evaluaciones aprobadas.

Evaluaciones reprobadas.

Recuperaciones.

Recuperaciones pagadas.

Estudiantes con pagos pendientes.

Ingresos.

Promociones utilizadas.

Referidos.

Ocupación de aulas.

Horarios.

Auditoría.

Formatos iniciales:

PDF
Excel
CSV

46. Seguridad

El sistema deberá implementar:

Contraseñas cifradas (hashing bcrypt/argon2 nativo de Laravel).

Control de roles (spatie/laravel-permission).

Permisos (spatie/laravel-permission + Policies).

Protección CSRF (middleware VerifyCsrfToken, incluido por defecto).

Validación de formularios (Form Requests).

Protección contra SQL Injection (Eloquent ORM / query builder con bindings parametrizados).

Sesiones seguras (driver de sesión de Laravel con cookies firmadas y HttpOnly).

Control de acceso (Policies + Gates por módulo).

Registro de actividad (tabla audit_logs vía eventos/listeners, sección 43.1).

Copias de seguridad (spatie/laravel-backup).

HTTPS (forzado vía middleware/configuración de servidor).

47. Notificaciones

Implementación: sistema de Notifications de Laravel, encoladas (Laravel Queue) para no bloquear la operación que las dispara. Canales del MVP: database (para el dashboard) y mail. Los canales de WhatsApp y otros de la sección 48 se incorporarán como canales de notificación adicionales en fases futuras.

El sistema podrá generar notificaciones para:

Próxima clase.

Cambio de aula.

Clase cancelada.

Falta de horas.

Evaluación próxima.

Evaluación perdida.

Recuperación pendiente.

Fecha límite de recuperación.

Pago pendiente.

Pago vencido.

Promoción.

Aprobación del nivel.

48. Evolución futura

Después del MVP se podrán agregar:

Aplicación móvil.

Código QR para asistencia.

Carnet digital.

Notificaciones por WhatsApp.

Correo electrónico.

Pagos en línea.

Facturación electrónica.

Certificados.

Firma digital.

Control de salones por QR.

Clases virtuales.

Integración con Google Meet / Zoom.

Portal para padres o acudientes.

Estadísticas avanzadas.

Inteligencia artificial para análisis académico.

49. Principio fundamental

El sistema deberá cumplir el siguiente principio:

Todo registro académico o financiero importante debe poder demostrar quién lo realizó, cuándo lo realizó, sobre qué estudiante o entidad se realizó y cuál era el contexto de la operación.

Por esta razón, la aplicación deberá conservar la trazabilidad de:

Asistencias.

Evaluaciones.

Recuperaciones.

Pagos.

Promociones.

Referidos.

Matrículas.

Extensiones.

Programación de clases.

50. Resultado esperado

El instituto tendrá una plataforma centralizada capaz de gestionar:

ESTUDIANTES
     ↓
CURSOS Y NIVELES
     ↓
MATRÍCULAS
     ↓
AULAS
     ↓
HORARIOS
     ↓
CLASES
     ↓
ASISTENCIAS
     ↓
HORAS ACADÉMICAS
     ↓
EVALUACIONES
     ↓
RECUPERACIONES
     ↓
PAGOS Y PROMOCIONES
     ↓
APROBACIÓN
     ↓
SIGUIENTE NIVEL

El estudiante podrá consultar desde su portal:

Mi información
Mis cursos
Mi nivel
Mi horario
Mis aulas
Mis profesores
Mi asistencia
Mis horas
Mis evaluaciones
Mis recuperaciones
Mis pagos
Mis descuentos
Mi estado de cuenta
Mi progreso

El administrador tendrá control completo sobre la operación académica y financiera, mientras que los profesores tendrán acceso únicamente a las funciones necesarias para gestionar sus clases y estudiantes.