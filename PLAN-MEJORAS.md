# Plan de mejoras AulaMaster

Plan acordado el 30/09/2026. Las secciones marcadas con **⏳ Pendiente** todavía necesitan una decisión antes de implementarse.

Las partes numeradas siguen el mismo orden que las fases de implementación: se leen y se desarrollan de arriba hacia abajo.

## Orden de implementación y seguimiento

Marca cada tarea con `[x]` cuando esté implementada y verificada. Las partes numeradas más abajo conservan el alcance y los detalles de cada tarea. Conviene completar las fases en este orden porque cada una prepara datos, permisos o procesos que necesita la siguiente.

### Dependencias externas (no bloquean el inicio)

Se puede empezar por la fase 1 sin tener nada de esto. Cada elemento solo hace falta para **poner en uso** la función indicada, no para desarrollarla: mientras tanto se trabaja con textos de prueba, mouse o pantalla táctil y envíos simulados.

| Pendiente | Mientras no llegue | Hace falta antes de |
|---|---|---|
| Textos de los 5 contratos del abogado | Se desarrolla con contratos de ejemplo; las plantillas son editables | Usar contratos con alumnos reales (fase 3) |
| Tableta digitalizadora (que no sea Wacom STU) | Se firma con mouse o pantalla táctil, que también funcionan | Firmar en la oficina con lápiz (parte 6.7) |
| Acceso al WhatsApp de la empresa y trámite de Meta Business | Se desarrolla y prueba con envíos simulados (`Http::fake()`) | Activar los avisos automáticos (fase 6) |

- [ ] Textos de los contratos recibidos y puntos legales de la parte 6 validados con el abogado.
- [ ] Tableta comprada y probada con `SignaturePad.vue`.
- [ ] Acceso al WhatsApp de la empresa, Meta Business verificado y plantilla aprobada. El trámite puede tardar días: conviene iniciarlo apenas haya acceso.

### Fase 1: acceso, marca y permisos

- [x] Compartir el nombre y logo de la institución en la aplicación. (Parte 1)
- [x] Actualizar el login y el flujo administrativo de restablecimiento de contraseña. (Parte 2)
- [x] Crear el rol secretaria con los permisos acordados. (Parte 3)

### Fase 2: estructura académica

- [x] Añadir color a los niveles y aplicarlo al portal del alumno. (Parte 4)
- [x] Configurar la secuencia y promoción entre niveles, incluidos sus controles y auditoría. (Parte 5)

### Fase 3: matrícula y contratos

- [x] Añadir datos del acudiente y reglas para alumnos menores de edad. (Parte 6.2)
- [x] Crear plantillas versionadas de contratos y su gestión administrativa. (Partes 6.1 y 6.5)
- [x] Implementar generación, firma en oficina y firma a distancia con evidencias y PDF. (Partes 6.4, 6.6, 6.7 y 6.8)
- [x] Crear el asistente de matrícula y la asignación de contratos. (Parte 6.3)
- [x] Activar automáticamente la matrícula cuando se cumplan los requisitos acordados. (Parte 7)

### Fase 4: mensualidades y cartera

- [x] Generar mensualidades, recordatorios y alertas de mora. (Parte 8)
- [x] Implementar bloqueo y desbloqueo por mora, acuerdos de pago y restricciones de asistencia. (Parte 8)
- [x] Crear la cartera en mora y el registro de contactos. (Parte 8)

### Fase 5: operación de clases y portales

- [x] Rediseñar los portales de alumnos y docentes respetando los permisos y estilos de los demás roles. (Parte 9)
- [x] Crear el calendario por rol con navegación y acciones correspondientes. (Parte 10)
- [x] Importar horarios semanales desde CSV/Excel con vista previa y validación de conflictos. (Parte 11)
- [x] Permitir materiales de clase por tipo, con acceso privado y límites de carga. (Parte 12)

### Fase 6: automatización de inasistencias

- [ ] Enviar y registrar avisos de WhatsApp por inasistencia, respetando consentimiento y exclusiones. (Parte 13; requiere aprobación de Meta)

### Fase 7: correo electrónico

- [ ] Configurar y probar el proveedor de correo (SMTP en el `.env` del servidor). Hoy está en `MAIL_MAILER=log` y los correos no salen, solo quedan en el log.
- [ ] Verificar que lleguen los correos de las fases anteriores: código de verificación y copia en PDF de la firma a distancia (parte 6.8), activación de matrícula (parte 7) y avisos de mensualidad y mora (parte 8).

Mientras no esté el correo, las fases anteriores se desarrollan y prueban igual (los correos quedan en el log), pero en producción:
- La **firma a distancia** no puede completarse, porque el código de verificación llega por correo. Solo se usa la **firma en la oficina**.
- Los avisos de mensualidad y mora no le llegan al alumno; el contacto se hace desde la pantalla "Cartera en mora".

Cada fase se puede subir al servidor por separado siguiendo [update.md](update.md).

### Decisiones pendientes

Marca cada punto cuando quede decidido. Las preguntas de producto o legales deben resolverse antes de cerrar la tarea relacionada.

- [ ] Secretaria: confirmar los permisos propuestos. (Parte 3)
- [ ] Contratos: validar con el abogado qué ocurre con los contratos si el alumno cumple 18 años durante el curso. (Parte 6.2)
- [ ] Firma a distancia: confirmar si la foto del documento de identidad será opcional u obligatoria. (Parte 6.8)
- [ ] Matrícula: confirmar si basta un pago registrado o si debe estar pagada por completo para activarla. (Parte 7)
- [ ] Calendario: decidir si se ofrecerá enlace `.ics` para calendarios personales. (Parte 10)
- [ ] Importación de horarios: decidir si el docente puede quedar vacío, si se importa por día de semana o fecha exacta y si se alerta 48 horas antes. (Parte 11)
- [ ] Materiales: decidir si se aceptan Word/PowerPoint y si habrá límite total de almacenamiento por nivel. (Parte 12)
- [ ] WhatsApp: confirmar con Meta la coexistencia del número actual con la API o elegir otro número, y definir quién administrará Meta Business. (Parte 13)

---

# Fase 1: acceso, marca y permisos

## 1. Logo e icono de la institución

- Prop compartida `institution` (`name`, `logo_url`) desde `InstitutionSetting::current()`. Se guarda en caché con `Cache::rememberForever` y se limpia en `InstitutionSettingController::update`.
- `app.blade.php`: `<link rel="icon">` con el logo, o `/favicon.ico` si no hay logo. El `<title>` lleva el nombre de la institución. Los datos llegan con un `View::composer` en `AppServiceProvider`.
- Componente `InstitutionLogo.vue` en `AuthenticatedLayout`, `PortalLayout` y `GuestLayout`. Si no hay logo, usa el de AulaMaster.
- Prueba: `InstitutionBrandingTest`.

## 2. Login y contraseñas

- **Login moderno:** con el logo y el nombre de la institución, y los textos en español.
- **Se quita "¿Olvidaste tu contraseña?"**: las rutas `forgot-password` y `reset-password`, sus controladores y páginas, y `PasswordResetTest.php` (autorizado). Se reemplaza por una prueba de que esas rutas ya no existen.
- **Botón "Restablecer contraseña"** con contraseña temporal. Al entrar con ella hay que cambiarla (`users.must_change_password`), y queda en la auditoría.

| Quién restablece | Alumnos | Docentes | Personal | Admin |
|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ |
| Coordinador | ✅ | ✅ | ❌ | ❌ |

## 3. Rol "secretaria"

El rol se crea en la fase 1. Los permisos sobre contratos y cartera se activan a medida que esas pantallas existan (fases 3 y 4).

| Área | Permiso |
|---|---|
| Estudiantes y acudientes | Crear y editar |
| Matrículas (asistente) | Crear y editar |
| Contratos | Generar, firmar en la oficina, enviar enlace, reenviar, anular, descargar |
| Cartera en mora | Ver y registrar contactos, recibe la alerta del día 6 |
| Pagos | Solo ver |
| Plantillas de contrato | ❌ |
| Restablecer contraseñas | ❌ |

- **⏳ Pendiente:** confirmar estos permisos.

---

# Fase 2: estructura académica

## 4. Color por nivel

- Migración `add_color_to_levels_table`: `color` `string(7)`, por defecto `#DBEAFE` (azul claro).
- `Level`: agregar `color` a `#[Fillable]`. Validación: `nullable|regex:/^#[0-9A-Fa-f]{6}$/`. Factory con color.
- Formulario del nivel: **paleta de colores pastel + opción "Personalizado"** (`<input type="color">`) con vista previa en vivo. En el listado de niveles, una muestra de color.
- Prop compartida `studentTheme` en `HandleInertiaRequests`: el color del nivel actual del alumno.
- **Regla del color:** se toma de **la matrícula más reciente del alumno que no esté cancelada**. Por eso **cambia en cuanto pasa de nivel**, aunque la matrícula nueva todavía esté `pendiente`. Sin matrículas, el fondo es el gris actual.
- **Dónde se aplica:** **solo en el fondo**, como un degradado suave del color hacia blanco. Las tarjetas quedan blancas encima.
- La consulta de matrícula actual pasa a `Student::currentEnrollment()`, para reutilizarla en `GetStudentDashboardData`.
- Pruebas: `LevelColorTest`, `StudentThemeTest`, que incluye el caso "aprobó y se creó el siguiente nivel".

## 5. Secuencia de niveles

- **Migración en `levels`:** `next_level_id`, que apunta a un nivel del mismo curso y puede quedar vacío, y `position`.
- **Migración en `enrollments`:** `previous_enrollment_id`.
- **Relaciones:**
  - `Level`: `nextLevel()` y `previousLevel()`.
  - `Enrollment`: `previousEnrollment()`.
- **Validación:** el nivel siguiente debe ser del mismo curso, no puede ser el mismo nivel y no puede formar ciclos.
- **Listado de niveles:** agrupado por curso, mostrando la ruta (Elementary 1 → Elementary 2 → …).
- **Acción `PromoteToNextLevel`:**
  - Al quedar una matrícula `aprobada`, crea **automáticamente** la del nivel siguiente como `pendiente`, con su precio calculado y la matrícula anterior enlazada.
  - No crea duplicados y avisa al alumno.
  - Se llama desde los dos puntos de `EvaluateLevelCompletion` donde se aprueba.
- **Botón manual** "Pasar al siguiente nivel" para el admin.
- **Requisito previo:** no se puede matricular en el nivel 2 sin aprobar el 1. El admin puede marcar "Omitir requisito", y queda en la auditoría.
- **Dashboard del alumno:** la ruta de progreso con el color de cada nivel.
- Prueba: `LevelProgressionTest`.

---

# Fase 3: matrícula y contratos

## 6. Contratos con firma digital

### 6.1 Plantillas (solo el admin)
- **Tabla `contract_templates`:**
  - `name`, `type`, `body`, `version`, `status` (borrador/publicado) y `published_at`.
  - `acceptance_mode`: obligatorio u opcional (Sí/No).
  - `scope`: por matrícula o por alumno.
  - `requires_guardian`.
- **Editor:** botones para insertar variables (`{{alumno.nombre}}`, `{{nivel}}`, `{{precio_final}}`, `{{acudiente.nombre}}`…) y vista previa.
- **Versiones:** una plantilla publicada no se edita, se crea una versión nueva. Lo firmado conserva su versión.
- Aquí se cargan los **5 contratos del abogado** (matrícula, publicación de imágenes, tratamiento de datos, …).

### 6.2 Alumno y acudiente
- **Campos nuevos en `students`:** `birth_date` y `document_type`, más los del acudiente: `guardian_name`, `guardian_document`, `guardian_relationship`, `guardian_email` y `guardian_phone` (con indicativo internacional).
- **`Student::isMinor()`:** se calcula con la fecha del día en que se firma.
- **Si el alumno es menor de edad, firma SOLO el acudiente.** El alumno ve sus contratos solo para leer, con el estado "Firmado por tu acudiente".
- Si faltan la fecha de nacimiento o el correo del acudiente, aparece como "Datos incompletos" y no se pueden enviar contratos.
- Se actualizan los formularios y la importación CSV/Excel de estudiantes.
- **⏳ Pendiente (abogado):** ¿qué pasa si el alumno cumple 18 durante el curso? Lo propuesto es que lo firmado por el acudiente siga vigente y que lo nuevo lo firme el alumno.

### 6.3 Asistente de matrícula
1. Alumno, más acudiente si es menor.
2. Nivel y precio, con promoción y referido.
3. Contratos: se asignan solos. Los que son por alumno y ya están firmados se omiten.
4. Resumen y forma de firmar: en la oficina o por enlace.

### 6.4 Registro de firmas
- **Tabla `contract_signatures`:**
  - Vínculos: `enrollment_id`, `student_id`, `contract_template_id`, `template_version`.
  - Estado: `status` y `decision`.
  - Firmante: `signer_name`, `signer_document`, `signer_role`.
  - Evidencia: `signature_path`, `signed_at`, la zona horaria del firmante, `ip`, `user_agent` y `otp_verified_at`.
  - Documento: `rendered_body` (copia exacta del texto), `content_hash` (SHA-256) y `pdf_path`.
- **PDF:** se genera con `barryvdh/laravel-dompdf`, que ya está instalado. Incluye una **hoja de evidencia** y se guarda en el **disco privado**.
- **Autorización de imágenes:** queda también en `students.image_consent`, y el alumno puede revocarla desde su portal.
- Todo queda en la auditoría.

### 6.5 Gestión
- **Matrícula:** una pestaña "Contratos" con el estado, el PDF, reenviar y anular.
- **Listado de matrículas:** filtro de "Contratos pendientes".
- **Ficha del alumno:** el historial de lo firmado.

### 6.6 Generar el contrato
- Botón **"Generar contratos"** en la matrícula y **vista previa en PDF**.
- **Cláusulas especiales** opcionales para un alumno.
- Una vez generado y enviado, **no se modifica**: se anula y se genera uno nuevo.

### 6.7 Firma en la oficina (computador de la secretaria)
- **"Firmar aquí"** abre el contrato en pantalla completa.
- **Área de firma:** el componente `SignaturePad.vue`, hecho con canvas y Pointer Events. Toma la presión del lápiz y funciona con **tabletas de lápiz** (Wacom Intuos/One, Huion, XP-Pen), pantallas táctiles y mouse. **Las Wacom STU no son compatibles sin un trabajo aparte.**
- La secretaria confirma que **verificó el documento** y toma la **foto del documento de identidad** (anverso y reverso), con la cámara web o subiendo el archivo (máximo 5 MB).
- **La foto:**
  - Se guarda **cifrada** en el disco privado.
  - Solo la ven el admin y la secretaria, y cada acceso queda en la auditoría.
  - **No va en el PDF.**
- Varios contratos se firman seguidos, sin salir de pantalla completa.
- **⏳ Pendiente:** el modelo de la tableta digitalizadora que se va a comprar.

### 6.8 Firma a distancia (otra ciudad u otro país)
- **"Enviar para firma"** por **correo**, por **WhatsApp** (abre WhatsApp Web con el mensaje listo, sin API) o **copiando el enlace**.
- **El enlace:** es único, está firmado (`URL::temporarySignedRoute`), **vence en 7 días** (configurable) y no requiere cuenta.
- **Pasos:** leer hasta el final, marcar "He leído y acepto", firmar con el dedo y escribir el **código de verificación** que llega por correo.
- **Seguimiento:** Enviado → Abierto → Firmado, con botones para reenviar y anular.
- Al firmar, se avisa a quien envió el enlace y el firmante recibe su copia en PDF.
- **⏳ Pendiente:** ¿la foto del documento de identidad es **opcional u obligatoria** en la firma a distancia?

### 6.9 Pruebas y nota legal
- **Pruebas:** `ContractTemplateTest`, `ContractSigningTest` y `EnrollmentContractsTest`.
- **Nota legal (para validar con el abogado):** el esquema busca cumplir con la Ley 527 de 1999 y el Decreto 2364 de 2012 (firma electrónica), y con la Ley 1581 de 2012 (datos personales e imagen). El contrato de tratamiento de datos debe mencionar la **foto del documento de identidad** y autorizar el contacto por WhatsApp (parte 13).

## 7. Activación de la matrícula

- La matrícula queda `pendiente` hasta cumplir **contratos obligatorios firmados + pago registrado**. Entonces pasa sola a `activa`.
- Acción `ActivateEnrollmentIfReady`. Se ejecuta al firmar un contrato, al registrar un pago `pagado` y con la confirmación de Wompi. No importa el orden.
- El alumno recibe una notificación cuando su matrícula se activa.
- **⏳ Pendiente:** confirmar si basta con **al menos un pago `pagado`** de la matrícula, que es lo propuesto, o si debe estar pagada completa.

---

# Fase 4: mensualidades y cartera

## 8. Mensualidades y mora

- **Migración en `payments`:** `type` (matrícula / mensualidad / otro), `period` (por ejemplo `2026-10`) y `due_date`. Los pagos que ya existen quedan como `otro`, sin fecha de vencimiento, y no activan la mora.
- **Valor de la mensualidad:** es **fijo, pero puede variar**.
  - `levels.monthly_fee` es el valor de referencia.
  - `enrollments.monthly_fee` se puede ajustar por alumno.
  - Si cambia el valor del nivel, el sistema pregunta si se aplica a las matrículas activas desde el próximo mes.
  - El cajero puede corregir una mensualidad mientras está pendiente, y queda en la auditoría.
- **Configuración institucional:** el día límite de pago (**5**) y los días para la alerta de 10 días de mora.

**Calendario mensual** (comando `app:generate-monthly-fees`, se ejecuta a diario):

| Día | Qué pasa | A quién se avisa |
|---|---|---|
| 1 | Se genera la mensualidad | Alumno, y acudiente si es menor |
| 3 | Recordatorio de que vence el día 5 | Alumno / acudiente |
| 5 | Último día para pagar | — |
| **6** | Pasa a `vencido`, **se bloquea al alumno** | **Cajero, secretaria y admin**: contactar al alumno o al acudiente |
| **15** | 10 días en mora | **Admin** |

- **Bloqueo del alumno:** el middleware `EnsureStudentIsUpToDate` lo manda a la pantalla "Tu cuenta tiene pagos pendientes".
  - **Puede seguir usando:** el pago en línea con Wompi, su perfil, cerrar sesión y firmar contratos.
  - **Desbloqueo:** inmediato al pagar, o con un **acuerdo de pago** hasta la fecha acordada.
- **El docente NO puede tomar asistencia a un alumno en mora:** aparece deshabilitado y el backend lo rechaza. Si después hay que reconocerle una clase, se usa una corrección de asistencia.
- **Pantalla "Cartera en mora"** (admin, cajero y secretaria), igual que la de Inasistencias:
  - Semáforo de mora: amarillo de 1 a 9 días, rojo desde 10.
  - Botones de WhatsApp, llamada y correo. Si el alumno es menor, van al acudiente.
  - Registro de contactos en la tabla `payment_follow_ups`, con el resultado y una nota.
  - Filtros por días de mora, por nivel y "sin contactar".
- Pruebas: `MonthlyFeeTest` y `StudentPaymentBlockTest`.

---

# Fase 5: operación de clases y portales

## 9. Rediseño moderno y minimalista (solo docentes y alumnos)

Admin, coordinador, cajero y secretaria **mantienen el estilo actual**.

- **Estilo:**
  - Más espacio entre elementos.
  - Tarjetas blancas con `rounded-2xl` y borde fino, sin sombras marcadas.
  - Barra superior blanca semitransparente (`bg-white/80 backdrop-blur`).
  - Un solo color de acento (índigo).
  - Íconos de línea, con el componente `Icon.vue`.
- **Layout nuevo `PortalLayout.vue`:** lleva el logo de la institución, una barra de navegación inferior en el celular y el fondo con el color del nivel, este último solo para el alumno.
- **Componentes nuevos en `resources/js/Components/Portal/`:** `PortalCard`, `PortalButton`, `PortalStat` y `PortalEmptyState`. Los componentes actuales no se tocan.
- **Pantallas compartidas con el admin** (`Attendance/Create`, `ClassMaterials/Index`): un componente `AppLayout` elige el layout según el rol.
- **Pantallas del alumno:**
  - Dashboard: saludo, ruta de niveles, progreso de horas, próxima clase con botón "Unirme" y estado de cuenta.
  - Mis horarios, Mi material y Mis contratos.
- **Pantallas del docente:**
  - Dashboard: las clases de hoy, con accesos directos a tomar asistencia, subir material y poner el enlace virtual.
  - Asistencia: cómoda en celular, con botones grandes.
  - Fondo neutro, porque el docente no tiene nivel.
- **Textos:** "Profile" y "Log Out" pasan a "Mi perfil" y "Cerrar sesión".

## 10. Calendario estilo Google Calendar

- **Componente propio** `Calendar/CalendarView.vue`, sin dependencias nuevas.
- **Vistas:** semana (horas en filas y días en columnas), día, mes y agenda.
- **Navegación:** botones Hoy, ◀ y ▶, la línea de la hora actual, y en el celular abre en vista Día o Agenda.
- **Colores:** las clases van con el color de su nivel. Las clases que se cruzan se muestran lado a lado.
- **Sin arrastrar y soltar:** se reprograma con "Editar", que valida choques y avisa a los alumnos.
- **Lo que ve cada rol:**

| Rol | Ve | Acciones |
|---|---|---|
| Alumno | Clases de su nivel, con asistió / faltó / próxima | Unirse a la clase virtual, ver el material |
| Docente | Sus clases | Tomar asistencia, subir material, poner el enlace |
| Admin / coordinador | Todas, con filtros por aula, docente y nivel | Editar, cancelar, crear |

- **Carga de datos:** por rango de fechas, con la acción `GetCalendarSessions`. Prueba: `CalendarTest`.
- **⏳ Pendiente:** el **enlace .ics** para ver las clases en el Google Calendar personal: ¿sí o no?

## 11. Programar la semana de clases desde CSV / Excel

- Usa `maatwebsite/excel` con el mismo patrón que la importación de estudiantes.
- **Columnas:** `dia`, `hora_inicio`, `hora_fin`, `nivel` (código), `aula`, `modalidad`, `docente` (correo o documento), `enlace_virtual` y `notas`.
- **Plantilla .xlsx:** con ejemplos, una hoja de instrucciones y listas desplegables de niveles, aulas y docentes.
- **Al subir:** se elige la **semana** y, opcionalmente, "Repetir durante X semanas".
- **Pasos:** subir → **vista previa** con filas correctas, sin docente y con errores (incluidos los choques de aula o docente, también dentro del mismo archivo) → confirmar.
  - Se descargan las filas con error para corregirlas.
  - Se avisa a los docentes asignados.
- **Clases sin docente:**
  - `class_sessions.teacher_id` pasa a ser opcional.
  - En el calendario aparecen con la etiqueta "Sin docente".
  - El docente se asigna después.
  - Alerta 48 horas antes.
  - No se puede tomar asistencia mientras no tenga docente.
- **Permisos:** admin y coordinador. Prueba: `ClassSessionImportTest`.
- **⏳ Pendiente:**
  - ¿La columna del docente puede quedar vacía?
  - ¿Día de la semana o fecha exacta?
  - ¿Alerta con 48 horas de anticipación?

## 12. Material de clase por tipo

- **Migración en `class_materials`:** `type` (youtube / enlace / pdf / imagen), `url` opcional, `file_path`, `file_name`, `file_size` y `mime_type`. El material que ya existe se convierte a `enlace` o a `youtube`.
- **Formulario del docente:** 4 botones de tipo. YouTube muestra una vista previa. Los archivos se arrastran o se seleccionan, con barra de progreso.
- **Límites** (configurables):

| Tipo | Formatos | Máximo |
|---|---|---|
| PDF | `.pdf` | **5 MB** |
| Imagen | `.jpg`, `.png`, `.webp` | **2,5 MB** |

- **Validación:** se revisa el tipo real del archivo. Si pasa del límite, el mensaje sugiere comprimirlo o compartirlo por Google Drive.
- **Lo que ve el alumno:**
  - Los videos se reproducen dentro de la página, con `youtube-nocookie.com`.
  - Los PDF se ven en un visor dentro de la página.
  - Las imágenes se amplían al hacer clic.
- **Archivos privados:**
  - Pueden verlos el docente de la clase, el admin y los alumnos del nivel. El alumno en mora no puede.
  - Se borran junto con el material.
- **⚠️ Servidor:** subir `upload_max_filesize` y `post_max_size` de PHP a **6 MB** en `docker/`, porque el límite de fábrica es de 2 MB. Nginx ya permite 25 MB.
- Prueba: `ClassMaterialTest`, ampliado.
- **⏳ Pendiente:** ¿se agregan Word y PowerPoint? ¿Hay un límite total de espacio por nivel?

---

# Fase 6: automatización de inasistencias

## 13. WhatsApp automático por inasistencia

Se envía un mensaje automático **desde el número de la institución** cuando un alumno lleva **una semana sin asistir**, con su nombre.

- **Canal:** la **API oficial de WhatsApp Business (Meta Cloud API)**, llamada con el cliente HTTP de Laravel, sin dependencias nuevas.
  - **No** se usan librerías no oficiales que simulan WhatsApp Web, porque Meta **bloquea el número**.
- **Requisitos de Meta**, que hace la institución:
  - Una cuenta de Meta Business verificada.
  - El número registrado en la API.
  - Una **plantilla de mensaje aprobada** por Meta. Los mensajes que inicia la empresa solo pueden enviarse con plantillas aprobadas.
  - **Costo:** Meta cobra por cada mensaje enviado, según la tarifa vigente para Colombia.
- **Situación actual del número:** la institución **ya usa la app de WhatsApp Business** en ese número.
  - Hay que **conectar ese número a la API**. Meta ofrece un modo de **coexistencia** que permite seguir usando la app en el celular y, al mismo tiempo, enviar mensajes automáticos por la API.
  - Hay que **confirmar con Meta** al registrarlo que la coexistencia está disponible para ese número. Si no lo está, la alternativa es usar **otro número solo para mensajes automáticos**.
- **Plantilla propuesta** (categoría "utilidad"):
  > Hola {{1}}, en {{2}} notamos que no has asistido a clases desde el {{3}}. ¿Todo bien? Escríbenos para ayudarte a ponerte al día. 📚

  Si el alumno es menor de edad: *"Hola {{1}}, le escribimos de {{2}} porque {{3}} no ha asistido a clases desde el {{4}}…"*, dirigido al **acudiente**.
- **Regla de envío** (comando diario `app:send-absence-whatsapp`):
  - Alumnos con matrícula activa que tuvieron clases programadas en los **últimos 7 días** y **no asistieron a ninguna** (faltas "ausente"). Las faltas excusadas no cuentan.
  - Reutiliza `GetStudentsWithAbsences` y su fecha de "última asistencia".
  - **Un solo mensaje por racha de faltas.** No se repite cada día. Si vuelve a clase y luego falta otra semana, se envía de nuevo.
  - Si el alumno es menor, el mensaje va al **acudiente**.
  - Solo se envía a números válidos con indicativo. Los celulares colombianos sin indicativo se completan con +57, como ya hace `Absences.vue`.
- **Registro:**
  - Tabla `whatsapp_messages`: alumno, teléfono, plantilla, estado (enviado / entregado / leído / fallido), error y fecha.
  - Un **webhook de Meta** actualiza el estado.
  - En la pantalla de Inasistencias se ve la etiqueta "WhatsApp enviado el 30/09 ✓✓ leído", y hay un botón para enviarlo **manualmente** desde ahí.
- **Configuración institucional:**
  - Activar o desactivar el envío automático.
  - Los días sin asistir que disparan el mensaje (**7**).
  - La hora de envío.
  - El token y el ID del número, guardados en `.env` y nunca en la base de datos sin cifrar.
- **Consentimiento:** el contrato de tratamiento de datos debe autorizar el contacto por WhatsApp (Ley 1581 de 2012). Si el alumno o el acudiente piden no recibir más mensajes, se marca `whatsapp_opt_out` y no se le vuelve a escribir.
- **Uso futuro del mismo canal:** avisos de mora (parte 8) y enlaces de firma de contratos (parte 6.8), cada uno con su propia plantilla aprobada.
- Prueba `AbsenceWhatsappTest`, con `Http::fake()` para no enviar mensajes reales:
  - Se envía tras 7 días sin asistir.
  - No se envía dos veces por la misma racha.
  - Las faltas excusadas no cuentan.
  - Si es menor, se envía al acudiente.
  - Respeta `whatsapp_opt_out`.
  - Registra el error si Meta rechaza el mensaje.
- **⏳ Pendiente:** confirmar la coexistencia del número y decidir quién gestiona la cuenta de **Meta Business**.
