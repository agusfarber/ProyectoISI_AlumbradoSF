# Idea — Chatbot asistente de la plataforma

Documento de construcción de la idea. **Implementado** en el layout autenticado (no aparece en login).  
Historia de usuario asociada: **Consultar al chatbot de la plataforma.**

---

## 1. Qué es

Un **asistente integrado** (chatbot) llamado **Lúmen** que aparece como **burbuja** en la esquina inferior de la plataforma, **después de iniciar sesión**.

El usuario elige una pregunta de un **catálogo precargado** y Lúmen responde con un texto ya definido.

También puede **escribir para buscar**: a medida que tipea, aparecen **sugerencias** de preguntas del catálogo (aunque no coincidan palabra por palabra). **No envía una pregunta libre**: siempre elige una de las sugerencias o del listado.

El catálogo **cambia según el rol** (Administrador, Supervisor u Operario). Cada persona ve solo las preguntas que le corresponden.

---

## 2. Nombre y personaje

La metáfora es doble y encaja con el dominio:

- Es un sistema de **alumbrado / luminarias**.
- Cuando se entiende algo, “**se te prende la lamparita**”.

### Nombre: **Lúmen** *(definitivo)*

- Es la unidad de **luz** (lumen).
- Suena cercano a “iluminar” y a “entender”.
- Es corto, formal y no parece una persona real (no se confunde con un operador humano).
- En la burbuja se presenta como: *“Hola, soy Lúmen, tu asistente de la plataforma.”*

### Cómo se ve

- Ícono de **foquito / luminaria**.
- **Cerrado:** foquito apagado o en reposo.
- **Al abrir** el chat y **al mostrar una respuesta:** el foquito **se enciende** con una animación sutil (gesto de “se te prendió la lamparita”).
- Colores alineados a la plataforma (violeta oscuro + acento ámbar/amarillo de la luz).

### Mensajes de cierre (rotar, no repetir siempre el mismo)

No van en todas las respuestas. Se usan al **final de una respuesta**, de a uno, para invitar a seguir o a cerrar. Ir rotando para que no se sienta repetitivo.

| # | Mensaje |
|---|---------|
| 1 | ¿Quedó más claro? Podés elegir otra consulta cuando quieras. |
| 2 | Con esto ya tenés el panorama. ¿Otra duda? |
| 3 | Quedó explicado. Podés volver a los temas o buscar otra pregunta. |
| 4 | Si te sirvió, el foquito ya está encendido. ¿Seguimos con otra? |
| 5 | Eso cubre esta parte. Elegí otro tema si lo necesitás. |
| 6 | Listo. Cuando quieras, volvé a los temas o cerrá el asistente. |
| 7 | Ahí está la idea. Podés buscar otra pregunta arriba. |
| 8 | Si ya te quedó claro el camino, seguí trabajando; Lúmen queda acá por si hace falta. |
| 9 | Una consulta menos. Si surge otra, escribí o elegí un tema. |

**Uso:** uno al azar (o en ronda) después de la respuesta, no pegados de a varios.

---

## 3. Cómo se usa (experiencia)

Estilo de referencia: **burbuja interactiva tipo Coderhouse** (fija abajo a la derecha, se abre/cierra sin salir de la pantalla).

### Burbuja cerrada

- Círculo fijo, abajo a la derecha.
- Ícono de foquito (apagado o en reposo).
- Visible en las pantallas de la plataforma **después de iniciar sesión**.
- **No aparece en la pantalla de login.** El asistente es una ayuda de uso una vez dentro del sistema, no del ingreso.

### Burbuja abierta

1. Saludo breve de Lúmen.  
2. Campo para **buscar** (opcional) y lista de **temas**.  
3. Si elige un tema → aparecen las **preguntas** de ese tema.  
4. Si escribe en el buscador → aparecen **sugerencias** de preguntas.  
5. Al elegir una pregunta (del tema o de las sugerencias), Lúmen muestra la **respuesta escrita** y, si hay, una **imagen o GIF** ilustrativo, más un **mensaje de cierre** (rotado).  
6. Luego se puede:
   - ver **otra pregunta** del mismo tema,
   - volver a **temas**,
   - **buscar** otra consulta,
   - **cerrar** la burbuja.

### Búsqueda y sugerencias

El usuario **puede escribir**, pero no para inventar una pregunta libre. El texto sirve para **encontrar** preguntas del catálogo de su rol.

Comportamiento:

- A medida que escribe, Lúmen muestra sugerencias: *“Quizá quieras preguntar…”*  
- No hace falta copiar el título exacto de la pregunta.  
  - Ejemplo: escribe *“no me deja terminar la hoja”* → sugiere *“No me deja finalizar. ¿Por qué?”*  
  - Ejemplo: escribe *“clave del 103”* o *“no sincroniza”* → sugiere *“¿Qué es el token?”* y *“La sincronización falla. ¿Qué reviso?”*  
- Las sugerencias salen de coincidencias con el **título**, la **respuesta** y **palabras clave** asociadas a cada pregunta (sinónimos, errores habituales, formas de hablar).  
- Solo se sugieren preguntas **de su rol**.  
- Si no hay coincidencias: *“No encontré una pregunta parecida. Probá con otras palabras o elegí un tema.”*  
- Al tocar una sugerencia, se envía esa pregunta precargada y se muestra su respuesta.

Esto no es inteligencia artificial: es un **buscador de preguntas** del catálogo.

### Qué no hace

- No responde texto libre (no “inventa” una respuesta a lo que escribió).  
- No llama a una inteligencia artificial.  
- No ejecuta acciones en el sistema (no crea usuarios, no cierra reclamos, etc.). Solo **explica**.  
- No muestra preguntas de otro rol.

### Tono de las respuestas

Formal, claro y breve. Misma línea que el Manual de Usuario: “usted”, sin jerga innecesaria. Si hace falta un término del sistema, se explica en la misma respuesta.

---

## 4. Organización del catálogo

Cada pregunta tiene:

- **id** (para implementación futura)
- **rol** (`admin` / `supervisor` / `operario`)
- **tema**
- **pregunta** (tal como la ve el usuario)
- **respuesta**
- **palabras clave** (sinónimos y formas de buscar; para las sugerencias)
- **recurso visual** (opcional): imagen estática o **GIF** que ilustra lo que está explicando

Navegación del catálogo: **temas primero**, después las preguntas de ese tema. La búsqueda es un atajo paralelo.

### Recurso visual en la respuesta (imagen o GIF)

Además del texto, Lúmen puede mostrar un archivo visual **asociado a esa pregunta**.

- **No es obligatorio** en todas las respuestas. Si no hay archivo, se muestra solo el texto.
- El archivo se cargará **más adelante en la base de datos** (no hace falta tener las imágenes ahora).
- Formatos previstos: imagen estática (JPG/PNG/WebP) o **GIF animado**.

**¿GIF en lugar de foto fija?** Sí, y para esta plataforma encaja mejor en muchos casos.

Un GIF permite **mostrar el gesto** (un clic, abrir un menú, confirmar Sí/No) en lugar de una captura suelta. Ejemplos:

- “¿Cómo finalizo la hoja?” → GIF del botón **Finalizar ejecución** y la confirmación.
- “¿Cómo sincronizo?” → GIF del botón **Sincronizar** y las opciones.
- “¿Dónde está el ícono de la i en Análisis?” → GIF acercándose al ⓘ.

La foto fija sigue sirviendo cuando no hay un gesto (por ejemplo, un recuadro de estados o del menú).

En la base de datos, cada ítem del catálogo tendría algo así:

- `recurso_url` o archivo guardado  
- `recurso_tipo`: `imagen` | `gif` (o se infiere por la extensión `.gif`)  
- `recurso_texto_alternativo`: descripción corta para accesibilidad (*“Botón Finalizar ejecución en Tareas”*)

En el chat, el visual va **debajo del texto** de la respuesta, a un tamaño cómodo dentro de la burbuja (sin romper el ancho del panel). El GIF se reproduce en bucle, suave, sin sonido.

> Primera versión: un solo visual por respuesta. Si más adelante hace falta una galería, se puede ampliar.

Temas comunes a todos (con matices por rol):

- Ingreso y perfil
- Qué puedo hacer yo
- Dudas frecuentes

Temas propios:

| Rol | Temas |
|-----|--------|
| Administrador | Usuarios |
| Supervisor | Cuadrillas, Reclamos, Materiales, Mapa, Rutas, Cierre, Análisis, Notas |
| Operario | Hojas de ruta / Tareas, Ejecución en campo, Materiales y bitácora, Estados |

---

## 5. Catálogo — Administrador

### 5.1 Ingreso y perfil

**¿Con qué dato inicio sesión?**  
Con su **correo electrónico** y su contraseña. Supervisores y operarios, en cambio, ingresan con **legajo**.

**¿A qué pantalla llego al entrar?**  
A **Usuarios**, que es su pantalla principal.

**¿Cómo cierro la sesión?**  
Abra el menú de su cuenta y elija **Cerrar sesión**.

**¿Dónde cambio mi foto de perfil?**  
En **Mi Perfil**, desde el menú de su cuenta. La foto es opcional.

**¿Qué datos veo en Mi Perfil?**  
Su nombre, su tipo de usuario y su email. También puede cambiar la foto de perfil.

**¿Qué es Lúmen?**  
Soy yo 💡, el asistente de la plataforma. Elija un tema o busque una pregunta y le explico cómo usar esa parte, según su rol. No hago cambios en el sistema.

**Olvidé mi contraseña. ¿Qué hago?**  
Desde la plataforma no hay un recuperador automático. Debe gestionarla quien administra las cuentas.

---

### 5.2 Qué puedo hacer

**¿Qué hace un administrador en esta plataforma?**  
Administra las **cuentas de acceso**: quién entra y con qué rol. No gestiona reclamos, hojas de ruta, materiales de obra ni cierres. Esa operación diaria corresponde al **Supervisor**.

**¿Por qué no veo Cuadrillas, Reclamos o Rutas?**  
Porque esas pantallas no forman parte de su rol. Cada usuario ve solo lo que le corresponde. Es esperado.

**¿Qué pantallas tengo?**  
**Usuarios** y **Mi Perfil**.

---

### 5.3 Usuarios

**¿Cómo creo un usuario nuevo?**  
En **Usuarios**, pulse **Nuevo usuario**. Complete nombre, rol, email (si es administrador) o legajo (si es supervisor u operario) y contraseña. Luego pulse **Guardar**.

**¿Qué requisitos tiene la contraseña?**  
Debe tener al menos 4 caracteres.

**¿Puedo crear otro administrador?**  
Sí. Al crear el usuario elija el rol Administrador y complete el email. Ese será su dato de ingreso.

**¿Qué le entrego a la persona nueva?**  
Su dato de acceso (email si es administrador, o legajo si es supervisor u operario) y la contraseña inicial.

**¿Qué dato de acceso lleva cada rol?**  
Administrador: **email**. Supervisor y operario: **legajo**.

**¿Cómo edito un usuario?**  
Ubíquelo en el listado, abra la edición, cambie lo necesario y guarde. Si no desea cambiar la contraseña, déjela en blanco.

**¿Puedo cambiar el rol de alguien ya creado?**  
Sí, desde la edición del usuario. Recuerde que el administrador ingresa con email y el supervisor u operario con legajo.

**¿Cómo le cambio la contraseña a un usuario?**  
Ábralo en edición, escriba la nueva contraseña y guarde. Si deja el campo en blanco, la contraseña no se modifica.

**¿Cómo elimino un usuario?**  
Desde el listado, con la acción de eliminar. Hágalo con cuidado: esa persona dejará de poder ingresar.

**¿Puedo filtrar el listado?**  
Sí. Puede filtrar por tipo de usuario (por ejemplo, ver solo operarios).

**¿Puedo buscar un usuario por nombre?**  
Sí. Use el buscador del listado para encontrarlo más rápido.

**¿Cómo le cargo o cambio la foto a un usuario?**  
Use el botón de cambiar foto del usuario, elija la imagen y confirme con **Guardar foto**.

**¿La foto de perfil es obligatoria?**  
No. Es opcional. Sirve para identificarse mejor en listados.

---

## 6. Catálogo — Supervisor

### 6.1 Ingreso y perfil

**¿Con qué dato inicio sesión?**  
Con su **número de legajo** y su contraseña.

**¿A qué pantalla llego al entrar?**  
A **Cuadrillas**.

**¿Cómo cierro la sesión?**  
Desde el menú de su cuenta, **Cerrar sesión**.

**¿Dónde está Mi Perfil?**  
En el menú de su cuenta. Allí ve su nombre, rol, legajo y puede cambiar la foto.

**Olvidé mi contraseña. ¿Qué hago?**  
Desde la plataforma no hay un recuperador automático. Pídale al administrador de cuentas que se la actualice.

**¿Qué es Lúmen?**  
Soy yo 💡, el asistente de la plataforma. Elija un tema o busque una pregunta y le explico cómo usar esa parte, según su rol. No hago cambios en el sistema.

---

### 6.2 Qué puedo hacer

**¿Cuál es mi rol?**  
Organizar el trabajo diario: cuadrillas, reclamos, hojas de ruta, consulta de lo registrado en campo, cierre formal, análisis, notas y mapa.

**¿Qué opciones tiene el menú?**  
Cuadrillas, Reclamos, Cierre, Materiales, Mapa, Rutas, Análisis y Notas, además de Perfil y Cerrar sesión.

**¿El operario ve las mismas pantallas que yo?**
No. El operario trabaja en **Tareas** (sus hojas de ruta).

**¿Por dónde conviene empezar el día?**  
Revisar **Cuadrillas** (quién tiene Gestión), traer o cargar reclamos, armar hojas en **Rutas** y, cuando haya completados, pasar por **Cierre**. Análisis y Notas son de apoyo.

---

### 6.3 Cuadrillas

**¿Para qué sirve Cuadrillas?**  
Para armar y mantener los equipos de trabajo: crear, buscar, editar, agregar o quitar operarios y marcar quién tiene **Gestión**.

**¿Cómo creo una cuadrilla?**  
En **Cuadrillas**, cree una nueva, asigne un nombre, agregue operarios y marque quién tiene Gestión. Luego guarde.

**¿Puedo buscar una cuadrilla?**  
Sí. Use el buscador de la pantalla Cuadrillas.

**¿Cómo edito una cuadrilla?**  
Ábrala y cambie el nombre u otros datos, agregue o quite operarios y actualice quién tiene Gestión. Guarde.

**¿Cómo agrego o quito operarios?**  
Desde la cuadrilla: agregue integrantes o quítelos si ya no corresponden. Después revise quién queda con Gestión.

**¿Qué es el permiso de Gestión?**  
Una marca que usted asigna a uno o más operarios de la cuadrilla. Quien tiene Gestión puede, en **Tareas**, iniciar y finalizar la hoja, y controlar cada reclamo (iniciar, pausar, continuar, completar, materiales y bitácora).

**¿Qué pasa si un operario no tiene Gestión?**  
Puede consultar la hoja y ver información, pero **no** controla la ejecución (no verá botones como Iniciar ejecución).

**¿Puede haber más de un operario con Gestión?**  
Sí. Puede marcar Gestión a uno o más operarios de la misma cuadrilla.

**¿Qué reviso en la cuadrilla antes de armar una hoja?**  
Que tenga los operarios correctos y al menos una persona con **Gestión**.

---

### 6.4 Reclamos

**¿Qué estados puedo filtrar?**  
Recibido, Asignado, Pendiente, En ejecución y Completado. También puede filtrar por prioridad (Baja o Alta) y por fechas.

**¿Cómo veo el detalle de un reclamo?**  
Ábralo desde el listado. Verá motivo, dirección, descripción, estado, prioridad y fechas, entre otros datos.

**¿Qué es la prioridad?**  
El nivel de urgencia del reclamo: **Baja** o **Alta**. Puede filtrar por ella en Reclamos y en el Mapa.

**¿Cómo creo un reclamo que no viene del 103?**  
En **Reclamos**, **Nuevo reclamo**, complete la ficha y guarde.

**¿Cómo edito la ficha de un reclamo?**  
Use **Editar ficha** para corregir datos como domicilio o descripción. El estado del trabajo en calle no se cambia desde acá.

**¿Qué es sincronizar?**  
Traer reclamos desde el **Sistema 103** hacia esta plataforma. Opciones habituales: **Pendientes**, **Por fechas** y **Por número**.

**¿Qué opciones tiene Sincronizar?**  
**Pendientes** (los que aún no se trajeron), **Por fechas** (un rango) y **Por número** (un reclamo puntual).

**¿Qué es el token?**  
Una clave de acceso necesaria para conectar con el Sistema 103. Se configura desde Reclamos, en **Token / Configurar token**.

**La sincronización falla. ¿Qué reviso?**  
Que el token esté cargado y vigente. Reintente con la opción adecuada. Si sigue fallando, solicite un token actualizado.

**¿Puedo cambiar el estado de un reclamo desde acá?**  
El trabajo en campo (iniciar, pausar, completar) lo hace el **operario en Tareas**. El **cierre formal** se hace en la pantalla **Cierre**. Desde Reclamos puede **editar la ficha** (por ejemplo domicilio o descripción).

**¿Puedo eliminar un reclamo?**
Sí, cuando no deba seguir visible en el sistema local. Úselo solo cuando corresponda.

**¿Qué significa Recibido?**  
El reclamo está en la plataforma y todavía no fue asignado a una hoja de ruta.

---

### 6.5 Materiales (catálogo)

**¿Esta pantalla es el stock de cada reclamo?**  
No. Acá se administra el **catálogo** (tipos de materiales disponibles). Lo que se usó en un reclamo lo registra el operario en **Tareas** y usted lo consulta en el detalle de la hoja, en **Rutas**.

**¿Qué puedo hacer con las categorías?**  
Crear, editar (nombre, ícono, color) o eliminar las que ya no se usan.

**¿Cómo agrego un material al catálogo?**  
En **Materiales**, cree uno nuevo, asígnelo a una categoría y guarde. También puede editarlo, eliminarlo o asociarle una foto.

**¿Puedo importar materiales?**  
Sí, desde un archivo. Verifique que la categoría indicada en el archivo ya exista en el sistema.

**¿Dónde veo los materiales usados en un reclamo?**  
En el detalle de la hoja, en **Rutas**: **Materiales utilizados**. Es solo consulta; el operario los carga en **Tareas**.

---

### 6.6 Mapa

**¿Para qué sirve el Mapa?**  
Para ver los reclamos ubicados en el territorio, filtrar por prioridad y estado, abrir el detalle de un punto, exportar y, si hace falta, corregir la ubicación.

**¿Puedo filtrar por estado y prioridad a la vez?**  
Sí. Los filtros se combinan y el mapa se actualiza en el momento.

**¿Cómo veo el detalle de un reclamo en el mapa?**  
Haga clic en el punto. Se abre una ventana con la información; puede cerrarla sin perder la vista.

**¿Puedo corregir la ubicación de un reclamo?**  
Sí, desde el Mapa, si las coordenadas no coinciden con la realidad.

**¿Puedo exportar desde el Mapa?**  
Sí. En esa pantalla puede exportar información, además de filtrar y abrir el detalle de cada punto.

---

### 6.7 Rutas (hojas de ruta)

**¿Qué es una hoja de ruta?**  
Una lista ordenada de domicilios (paradas) con uno o más reclamos para que atienda una cuadrilla.

**¿Qué son las hojas activas?**  
Las hojas vigentes, las que todavía se gestionan. Las ya finalizadas se consultan en **Historial de rutas**.

**¿Quién ejecuta la hoja en campo?**  
El operario con **Gestión**, en **Tareas**. Usted organiza, asigna y consulta; no inicia ni completa reclamos desde Rutas.

**¿Cómo creo una hoja?**  
En **Rutas**, **Nueva hoja de ruta**. El sistema propone un recorrido. Revíselo en mapa o lista, elija la cuadrilla, edite si hace falta y confirme.

**¿Puedo cambiar el orden o quitar reclamos antes de asignar?**  
Sí. Puede ajustar el recorrido (quitar, reordenar o revisar reclamos) y luego confirmar.

**¿Cómo asigno la hoja a una cuadrilla?**  
Al crear o desde el detalle: **Asignar / Cambiar cuadrilla**. El operario la verá en **Tareas**.

**¿Puedo cambiar la cuadrilla de una hoja ya creada?**  
Sí. Desde el detalle use **Asignar / Cambiar cuadrilla**.

**¿Qué veo en el detalle de una hoja activa?**  
Mapa o lista, cuadrilla, estado, tiempos, y consulta (solo lectura) de **Materiales utilizados** y **Registro en obra**.

**¿Qué es Visualizar rutas?**  
Una vista para ver **todas** las hojas juntas en el mapa, con listado y seguimiento.

**¿Qué hay en Historial de rutas?**  
Ejecuciones **ya finalizadas**: eventos, mapa, tiempos, materiales y observaciones.

**¿Puedo cambiar el mapa en el historial?**  
Sí. En el modo mapa del historial puede alternar entre Google Maps y Mapbox.

**¿Puedo eliminar una hoja?**  
Sí, cuando la situación lo permita, desde el detalle. Si hay trabajo en curso, el sistema puede impedirlo.

---

### 6.8 Cierre

**¿Completar y cerrar son lo mismo?**  
No. **Completar** lo hace el operario en campo (el trabajo físico terminó). **Cerrar** lo hace usted en **Cierre**: el reclamo queda cerrado formalmente y ese cierre se envía al Sistema 103.

**¿El cierre se envía al Sistema 103?**  
Sí. Al cerrar formalmente, ese cierre se registra y se envía al Sistema 103.

**¿Cómo cierro reclamos?**  
En **Cierre**, pestaña **Completados**, seleccione uno o varios y pulse **Cerrar seleccionados**. Confirme.

**¿Dónde veo los ya cerrados?**  
En la pestaña **Cerrados**, con su historial y detalle.

---

### 6.9 Análisis

**¿Para qué sirve Análisis?**  
Para ver cómo viene el trabajo con números y gráficos (totales por estado, tiempos, materiales, etc.).

**¿Qué indicadores veo arriba?**  
Totales del período, por ejemplo Recibidos, Asignados, Pendientes, En ejecución, Completados y Cerrados.

**¿Cómo elijo el período?**  
Con las opciones de período (hoy, últimos 7 días, mes o año actual, o un rango de fechas). Los gráficos se actualizan según esa elección.

**¿Cómo sé qué muestra cada gráfico?**  
Use el símbolo de información (**ⓘ**) junto a ese gráfico. Ahí aparece la explicación puntual.

**¿Qué tipo de gráficos hay?**  
Según el caso: distribución por estado o motivo, ingresos frente a cierres, tiempos, materiales y otras vistas de seguimiento. La **i** de cada gráfico aclara el contenido.

**¿Puedo guardar un gráfico?**  
Sí. En el detalle del gráfico, **Exportar como Imagen**.

---

### 6.10 Notas

**¿Las notas las ven otros supervisores?**  
No. Son **personales**: cada supervisor ve solo las suyas.

**¿Qué puedo hacer con una nota?**  
Crearla, fijarla, marcarla como hecha, filtrar activas/hechas, editarla o eliminarla.

**¿Cómo creo una nota?**  
En **Notas**, cree una con título y contenido. Luego puede fijarla, marcarla como hecha, editarla o eliminarla.

**¿Las notas están ligadas a un reclamo?**  
No. Son apuntes personales, independientes del trabajo de reclamos y rutas.

---

## 7. Catálogo — Operario

### 7.1 Ingreso y perfil

**¿Con qué dato inicio sesión?**  
Con su **número de legajo** y su contraseña.

**¿A qué pantalla llego al entrar?**  
A **Tareas**, con las hojas de ruta de su cuadrilla.

**¿Por qué no veo Reclamos, Rutas o Análisis?**
Esas pantallas son del Supervisor. Usted trabaja en **Tareas**. Es esperado.

**¿Dónde cambio mi foto?**  
En **Mi Perfil**, desde el menú de su cuenta.

**¿Cómo cierro la sesión?**  
Abra el menú de su cuenta y elija **Cerrar sesión**.

**Olvidé mi contraseña. ¿Qué hago?**
Desde la plataforma no hay un recuperador automático. Debe gestionarla quien administra las cuentas.

**¿Qué es Lúmen?**  
Soy yo 💡, el asistente de la plataforma. Elija un tema o busque una pregunta y le explico cómo usar esa parte, según su rol. No hago cambios en el sistema.

---

### 7.2 Hojas de ruta / Tareas

**¿Qué veo en Tareas?**  
Las hojas asignadas a su cuadrilla. Elija una, ábrala y trabaje en **lista** o **mapa**.

**¿Qué hago si no aparece ninguna hoja?**
Las hojas las asigna el Supervisor en **Rutas**. Si no ve ninguna, avísele para que revise la asignación a su cuadrilla.

**¿Cuál es la diferencia entre lista y mapa?**
Es la misma hoja. **Lista** muestra las paradas en orden. **Mapa** muestra la ubicación. Las acciones principales están en las dos, según su permiso.

**¿La lista y el mapa tienen las mismas acciones?**  
Sí. Es la misma hoja: cambia la forma de verla. Las acciones principales están en las dos, según su permiso de Gestión.

**¿Qué es una parada?**  
Un domicilio del recorrido. En un mismo domicilio puede haber más de un reclamo.

**¿Por qué no veo Iniciar ejecución?**  
Probablemente no tiene permiso de **Gestión**. Pídale al Supervisor que lo revise en **Cuadrillas**.

---

### 7.3 Permiso de Gestión

**¿Qué es Gestión?**  
Un permiso que el Supervisor marca en la cuadrilla. Con Gestión usted controla la hoja y cada reclamo. Sin Gestión puede consultar, pero no aparecen los botones de control.

**Si no tengo Gestión, ¿puedo trabajar igual?**  
Puede ver la hoja y los registros. No puede iniciar la hoja ni cambiar el estado de los reclamos. Quien tiene Gestión en el equipo es quien opera.

**¿A quién aviso si no tengo Gestión?**  
Al Supervisor, para que lo revise en **Cuadrillas**.

---

### 7.4 Ejecución en campo (con Gestión)

**¿Cómo empiezo la jornada?**  
Abra la hoja, pulse **Iniciar ejecución** y confirme. Recién ahí puede atender los reclamos del recorrido.

**¿Cuál es el orden típico de una jornada?**  
Abrir la hoja, iniciar ejecución y, en cada domicilio: iniciar el reclamo, trabajar, registrar materiales o notas si hace falta, y completar o pausar. Al terminar, finalizar la ejecución.

**¿Cómo empiezo un reclamo?**  
Con **Iniciar reclamo**. Pasa a **En ejecución** y comienza a contar el tiempo.

**¿Qué es Continuar ejecución?**  
Retomar un reclamo que había quedado en **Pendiente**, con el tiempo ya acumulado.

**¿Qué hace Completar?**  
Indica que el trabajo en ese reclamo **terminó**. Queda en **Completado**. El Supervisor, más adelante, hará el **cierre** formal.

**¿Qué hace Pausar?**  
Deja el reclamo en **Pendiente** para continuar otro día.

**¿Cómo agrego un reclamo que apareció en el recorrido?**
Con **Añadir Reclamos**, elija los reclamos que desee añadir a la ruta y confirme.

**¿Cómo finalizo la hoja?**  
Deje cada reclamo como **Completado** o **Pendiente** (nada a medias) y pulse **Finalizar ejecución**.

**No me deja finalizar. ¿Por qué?**  
Todavía hay algún reclamo con trabajo en curso. Márquelo como Completado o Pendiente y vuelva a intentar.

**¿Qué pasa con los reclamos que seguían Asignados al finalizar?**  
Vuelven a **Recibido** para que puedan asignarse en otra hoja, según el funcionamiento de la plataforma.

---

### 7.5 Materiales y bitácora

**¿Cómo registro materiales?**  
En el reclamo, botón de **Materiales**, elija lo utilizado y guarde. El catálogo lo carga el Supervisor.

**¿Es obligatorio cargar materiales para completar?**  
El sistema puede recordárselo. Puede registrarlos en ese momento o continuar sin registrarlos, según la situación.

**¿Qué es Registro en obra?**  
La **bitácora**: notas y/o fotos de lo ocurrido en el lugar. El Supervisor puede leerlo desde **Rutas**.

**¿Cómo subo fotos?**  
En **Registro en obra**, adjunte las imágenes y guarde. Deben ser un tipo de archivo válido.

**¿El Supervisor ve mis notas y fotos?**  
Sí. Puede leer el Registro en obra y los materiales desde el detalle de la hoja, en **Rutas**.

**¿Quién carga el catálogo de materiales?**  
El Supervisor, en la pantalla **Materiales**. Usted solo registra lo usado en cada reclamo.

---

### 7.6 Estados

**¿Qué significa Asignado?**  
El reclamo ya está en su hoja, pero todavía no empezó ese trabajo. Color celeste.

**¿Qué significa En ejecución?**  
Lo está trabajando ahora. Color amarillo.

**¿Qué significa Pendiente?**  
Quedó pausado para otro día. Color rojo.

**¿Qué significa Completado?**  
El trabajo en el lugar terminó. Color verde. Aún falta el **cierre** del Supervisor.

**¿Quién cierra el reclamo?**  
Usted lo **completa**. El Supervisor lo **cierra** en la pantalla **Cierre**.

**¿Qué significa Recibido?**  
El reclamo todavía no está en una hoja. Si necesita sumarlo al recorrido, use **Añadir Reclamos** (con Gestión) eligiendo reclamos en Recibido.

**¿Qué significa Cerrado?**  
El Supervisor ya dio el cierre formal. Usted no cierra: usted completa el trabajo en el lugar.

**¿Qué es la prioridad?**  
El nivel de urgencia del reclamo: Baja o Alta. Lo define la ficha del reclamo.

---

## 8. Preguntas transversales (misma idea, respuesta según rol)

Estas se pueden mostrar en los tres catálogos, con respuesta adaptada:

| Pregunta | Admin | Supervisor | Operario |
|----------|-------|------------|----------|
| ¿Por qué no veo una pantalla que otra persona sí ve? | Su rol solo cubre Usuarios y Perfil. | El operario no ve su menú completo; el admin no opera reclamos. | Usted no administra cuadrillas, cierre ni análisis. |
| ¿Dónde pido ayuda si no está mi pregunta? | Manual de usuario (enlace). | Manual de usuario (enlace). | Manual de usuario (enlace). |

---

## 9. Decisiones

1. **Nombre:** **Lúmen**. *(Cerrada.)*  
2. **¿Aparece en login?** No. Solo después de iniciar sesión. *(Cerrada.)*  
3. **Foquito animado** al abrir y al responder: sí, sutil. *(Cerrada.)*  
4. **Mensajes de cierre:** rotar entre las frases de la sección 2; no usarlas en todas las respuestas de corrido. *(Cerrada la idea; se puede recortar la lista si alguna no convence.)*  
5. **Navegación:** temas primero, luego preguntas. *(Cerrada.)*  
6. **Búsqueda:** el usuario escribe y aparecen sugerencias de preguntas del catálogo (no hace falta el texto exacto). No hay pregunta libre ni IA. *(Cerrada.)*  
7. **Visual en la respuesta:** opcional; imagen o **GIF**. Se carga después en la base de datos. El GIF se usa sobre todo para mostrar clics y recorridos de pantalla. *(Cerrada.)*  
8. Revisión del catálogo: ¿falta o sobra alguna pregunta?

---

## 10. Implementación

Estado: **en la plataforma**, solo después de iniciar sesión.

| Pieza | Dónde |
|-------|--------|
| Catálogo (temas, preguntas, keywords, cierres, `manual_url`) | `public/static/data/lumen-catalogo.json` |
| Estilos de la burbuja | `public/static/css/lumen.css` |
| Lógica (temas → preguntas → respuesta, búsqueda, foquito) | `public/static/js/lumen.js` |
| Inclusión autenticada | `app/Views/templates/header.php` (CSS) y `footer.php` (JS) |
| GIFs / imágenes futuras | `public/static/uploads/lumen/` (`recurso_url` en el JSON) |

Pendiente de contenido, no de código: cargar un visual por respuesta cuando exista el archivo.

---

*Fuente de las respuestas: comportamiento actual de la plataforma y el Manual de Usuario (`docs/Manual_de_Usuario.md`).*
