# Manual de Usuario - Sistema de Gestión de Alumbrado Público

## 📋 Índice

1. [Portada / Introducción](#1-portada--introducción)
2. [Sección por Roles](#2-sección-por-roles)
   - [2.1 Administrador](#21-administrador)
   - [2.2 Supervisor](#22-supervisor)
   - [2.3 Operario](#23-operario)
3. [Funcionalidades Comunes / Tips](#3-funcionalidades-comunes--tips)
4. [Anexos](#4-anexos)

---

## 1. Portada / Introducción

### 🎯 Descripción del Sistema

El **Sistema de Gestión de Alumbrado Público** es una aplicación web diseñada para optimizar la gestión de reclamos relacionados con el alumbrado público de la ciudad. El sistema permite coordinar eficientemente las cuadrillas de trabajo, gestionar reclamos ciudadanos y planificar las tareas de mantenimiento.

### 🎯 Objetivos Principales

- **Gestión centralizada de reclamos**: Recepción, clasificación y seguimiento de reclamos ciudadanos
- **Planificación de cuadrillas**: Organización y asignación de personal técnico
- **Visualización geográfica**: Mapa interactivo para ubicar reclamos y optimizar rutas
- **Control de materiales**: Inventario y gestión de materiales necesarios para las reparaciones
- **Seguimiento de estados**: Monitoreo del progreso de cada reclamo desde su recepción hasta su resolución

### 👥 Público Objetivo

Este manual está dirigido a:

- **Administradores**: Personal responsable de la configuración del sistema y gestión de usuarios
- **Supervisores**: Personal encargado de coordinar cuadrillas y asignar tareas
- **Operarios**: Personal técnico que ejecuta las reparaciones en campo

### 📖 Cómo usar este manual

- **Pasos numerados**: Cada proceso está dividido en pasos claros y secuenciales
- **Capturas de pantalla**: Las imágenes muestran exactamente qué hacer en cada paso
- **Íconos explicativos**: Los símbolos te ayudan a identificar rápidamente las funciones
- **Notas importantes**: Las advertencias destacan acciones críticas que requieren atención especial

---

## 2. Sección por Roles

### 2.1 Administrador

El administrador tiene acceso completo al sistema y es responsable de la configuración general y gestión de usuarios.

#### 🔐 Acceso y Login

**Requisitos previos:**
- Navegador web actualizado (Chrome, Firefox, Safari, Edge)
- Conexión a internet estable
- Credenciales de acceso proporcionadas por el administrador del sistema

**Proceso de acceso:**

**Paso 1**: Abrir el navegador web e ingresar a la dirección del sistema
- La URL del sistema será proporcionada por el administrador
- Asegúrate de que la conexión sea segura (HTTPS)

**Paso 2**: En la pantalla de inicio, identificar y hacer clic en el botón **"Administrador"**
- El botón tiene un ícono de escudo (🛡️) y está destacado en color azul
- Si no ves esta opción, verifica que estés en la URL correcta

![Pantalla de selección de rol](imagenes/login_seleccion_rol.png)

**Paso 3**: Completar el formulario de login con:
- **Correo electrónico**: Tu email institucional completo (ejemplo: admin@municipalidad.com)
- **Contraseña**: Tu contraseña asignada (distinguir entre mayúsculas y minúsculas)

![Formulario de login administrador](imagenes/login_admin_formulario.png)

**Paso 4**: Hacer clic en **"Iniciar Sesión"**
- Si las credenciales son correctas, serás redirigido al dashboard principal
- Si hay error, verifica que el email y contraseña sean exactos

**Solución de problemas comunes:**
- **Error "Credenciales incorrectas"**: Verificar email y contraseña, asegurarse de no tener espacios adicionales
- **Página no carga**: Verificar conexión a internet y URL correcta
- **Botón no responde**: Refrescar la página y volver a intentar

> **⚠️ Importante**: Solo los administradores pueden usar email para el login. Los operarios deben usar su número de legajo. Si olvidas tu contraseña, contacta al administrador del sistema.

#### 👥 Gestión de Usuarios y Permisos

**Descripción general:**
La gestión de usuarios permite al administrador supervisar todos los usuarios del sistema, verificar sus roles y permisos, y mantener un control centralizado del acceso al sistema.

**Acceder a la gestión de usuarios:**

**Paso 1**: Desde el menú principal, hacer clic en **"Usuarios"**
- El menú principal se encuentra en la parte superior de la pantalla
- El ícono de usuarios muestra una silueta de persona (👤)

**Paso 2**: Visualizar la lista de usuarios registrados en el sistema
- La tabla se carga automáticamente con todos los usuarios
- Los datos se muestran en tiempo real

![Lista de usuarios](imagenes/usuarios_lista.png)

**Información disponible en la tabla:**

| Columna | Descripción | Ejemplo |
|---------|-------------|---------|
| **Nombre** | Nombre completo del usuario | "Juan Pérez" |
| **Email / Legajo** | Identificador de acceso | "jperez@municipalidad.com" o "12345" |
| **Rol** | Tipo de usuario y permisos | "Administrador", "Supervisor", "Operario" |

**Funcionalidades disponibles:**

**1. Consulta de usuarios:**
- **Ver todos los usuarios**: La tabla muestra todos los usuarios registrados
- **Información detallada**: Cada fila contiene los datos básicos del usuario
- **Búsqueda**: Usar la función de búsqueda del navegador (Ctrl+F) para encontrar usuarios específicos

**2. Verificación de roles:**
- **Administrador (ID: 1)**: Acceso completo al sistema, gestión de usuarios y configuración
- **Supervisor (ID: 2)**: Gestión de cuadrillas, asignación de tareas, visualización de mapas
- **Operario (ID: 3)**: Ejecución de tareas, cambio de estados, registro de observaciones

**3. Seguimiento de actividad:**
- **Usuarios activos**: Identificar qué usuarios están registrados en el sistema
- **Distribución de roles**: Verificar que haya una distribución adecuada de permisos
- **Control de acceso**: Asegurar que solo usuarios autorizados tengan acceso

**Ejemplo práctico:**
Si necesitas verificar quién tiene permisos de supervisor:
1. Abrir la página de usuarios
2. Buscar en la columna "Rol" por "Supervisor"
3. Identificar los usuarios con este rol
4. Verificar que tengan las credenciales correctas

**Limitaciones actuales:**
- **Creación de usuarios**: No disponible en la interfaz actual
- **Edición de usuarios**: No disponible en la interfaz actual
- **Eliminación de usuarios**: No disponible en la interfaz actual

> **📝 Nota**: La creación y edición de usuarios está deshabilitada en la versión actual del sistema. Para gestionar usuarios (crear, modificar, eliminar), contacta al administrador técnico del sistema.

**Recomendaciones de seguridad:**
- **Revisar regularmente**: Verificar mensualmente la lista de usuarios activos
- **Reportar usuarios no autorizados**: Si encuentras usuarios que no deberían tener acceso, contacta inmediatamente al administrador
- **Verificar roles**: Asegurar que cada usuario tenga el rol correcto según sus responsabilidades

#### ⚙️ Configuración General del Sistema

**Descripción general:**
La configuración del sistema incluye la gestión de tokens para sincronización externa, el inventario de materiales y otros parámetros críticos para el funcionamiento del sistema.

### 🔑 Gestión de Credenciales Basic Auth para Sincronización

**¿Qué son las credenciales Basic Auth?**
Las credenciales Basic Auth (username y password) permiten al sistema conectarse con el sistema externo 103 de la municipalidad para sincronizar reclamos. El sistema genera automáticamente un token Base64 a partir de estas credenciales para autenticarse con la API externa.

**Acceder a la gestión de credenciales:**

**Paso 1**: Desde el menú principal, hacer clic en **"Token 103"**
- El ícono muestra una llave (🔑)
- Esta opción solo está disponible para administradores
- La página se titula "Gestión de Credenciales Basic Auth"

**Paso 2**: Visualizar la configuración actual
- La página muestra dos paneles:
  - **Panel izquierdo**: Formulario para configurar credenciales
  - **Panel derecho**: Información del token actual y token Base64 generado

![Configuración de credenciales](imagenes/token103_configuracion.png)

### 📝 Configurar Credenciales

**Proceso de configuración:**

**Paso 1**: Obtener las credenciales del sistema 103
- Contactar al administrador del sistema 103 de la municipalidad
- Solicitar el **username** y **password** para acceso a la API
- Las credenciales son específicas para el sistema de sincronización

**Paso 2**: Ingresar las credenciales en el formulario
- **Username**: Ingresar el nombre de usuario proporcionado (ej: `usuario@municipalidad.com`)
- **Password**: Ingresar la contraseña proporcionada
- El sistema genera automáticamente el token Base64 mientras escribes

**Paso 3**: Guardar las credenciales
- Hacer clic en **"Guardar Credenciales"**
- Se mostrará un mensaje de confirmación
- El sistema guardará las credenciales en la base de datos
- Si ya existían credenciales, se actualizarán con las nuevas

**Generación automática del token:**
- El sistema genera automáticamente el token Base64 al ingresar username y password
- El token se crea codificando `username:password` en Base64
- No es necesario hacer clic en ningún botón para generar el token
- El token se actualiza en tiempo real mientras escribes

### 📋 Información del Token

**Panel de información:**

Una vez guardadas las credenciales, el panel derecho muestra:

**1. Estado de las credenciales:**
- **Credenciales Configuradas**: Si hay credenciales guardadas
- **Sin Credenciales**: Si no hay credenciales configuradas

**2. Datos mostrados:**
- **Username**: El nombre de usuario guardado
- **Password**: Se muestra como `••••••••••` por seguridad
- **Token Basic Auth (Base64)**: El token generado automáticamente

**3. Copiar el token:**
- Hacer clic en el botón de copiar (📋) junto al campo del token
- El token se copiará al portapapeles
- Aparecerá un mensaje "Copiado" confirmando la acción
- El token se puede usar directamente en peticiones HTTP como: `Authorization: Basic {token}`

**Formato del token:**
```
Authorization: Basic YWd1c2ZhcmJlckBnbWFpbC5jb206QWx1bWJyYWRvMjAyNSMh
```

### 🔄 Actualizar Credenciales

**Cuándo actualizar:**
- Cuando las credenciales han expirado
- Cuando se cambian las credenciales en el sistema 103
- Por seguridad, para rotar las credenciales periódicamente

**Proceso de actualización:**

**Paso 1**: Si ya hay credenciales guardadas, el formulario se carga automáticamente con los valores actuales

**Paso 2**: Modificar el username o password según sea necesario
- Puedes cambiar solo uno de los campos o ambos
- El token Base64 se regenera automáticamente

**Paso 3**: Hacer clic en **"Guardar Credenciales"**
- El sistema actualizará las credenciales existentes
- Se mostrará un mensaje de confirmación

> **⚠️ Importante**: 
> - Las credenciales son sensibles y deben mantenerse seguros
> - No compartas las credenciales con usuarios no autorizados
> - El password se almacena en texto plano en la base de datos (considerar encriptación para producción)
> - Solo los administradores pueden acceder a esta página

### 🔗 Uso de las Credenciales

**Sincronización automática:**
- Las credenciales se usan automáticamente cuando se sincronizan reclamos desde el sistema 103
- El sistema utiliza el token Base64 generado para autenticarse con la API externa
- No es necesario configurar nada adicional para usar las credenciales en la sincronización

**Sincronización de reclamos:**
- **Por rango de fechas**: El sistema usa las credenciales para obtener reclamos en un período específico
- **Por número específico**: El sistema usa las credenciales para obtener un reclamo específico
- **Cierre de reclamos**: Las credenciales se usan para enviar el cierre de reclamos al sistema 103

**Ejemplo práctico:**
Para configurar credenciales por primera vez:
1. Contactar al administrador del sistema 103
2. Solicitar las credenciales de acceso (username y password)
3. Ir a "Token 103" en el sistema
4. Ingresar el username en el campo correspondiente
5. Ingresar el password en el campo correspondiente
6. Verificar que el token Base64 se genera automáticamente
7. Hacer clic en "Guardar Credenciales"
8. Verificar que aparece el mensaje de éxito
9. (Opcional) Copiar el token Base64 si necesitas usarlo externamente

**Solución de problemas comunes:**
- **No se genera el token**: Verificar que ambos campos (username y password) tengan contenido
- **Error al guardar**: Verificar que los campos no estén vacíos y que tengas permisos de administrador
- **Token no funciona**: Verificar que las credenciales sean correctas y estén activas en el sistema 103
- **Error 401 Unauthorized**: Las credenciales pueden estar incorrectas o haber expirado

### 📦 Gestión de Materiales

**Descripción general:**
El módulo de materiales permite gestionar el inventario de materiales necesarios para las reparaciones de alumbrado público, incluyendo luminarias, cables, postes, y otros elementos.

**Acceder a la gestión de materiales:**

**Paso 1**: Hacer clic en **"Materiales"** en el menú principal
- El ícono muestra una caja o paquete (📦)
- Esta funcionalidad está disponible para administradores y supervisores

**Paso 2**: Visualizar el inventario actual de materiales
- La tabla muestra todos los materiales registrados
- Se puede ver la cantidad disponible de cada material

![Gestión de materiales](imagenes/materiales_gestion.png)

**Información disponible en la tabla:**

| Columna | Descripción | Ejemplo |
|---------|-------------|---------|
| **Nombre** | Nombre del material | "Luminaria LED 50W" |
| **Cantidad** | Stock disponible | "25" |
| **Tipo** | Categoría del material | "Luminarias", "Cables", "Postes" |
| **Acciones** | Opciones disponibles | Editar, Eliminar |

**Funcionalidades detalladas:**

**1. Agregar materiales manualmente:**
- **Paso 1**: Hacer clic en **"+ Nuevo Material"**
- **Paso 2**: Completar el formulario:
  - **Nombre**: Descripción clara del material
  - **Cantidad**: Número de unidades disponibles
  - **Tipo**: Seleccionar de la lista de tipos existentes
- **Paso 3**: Hacer clic en **"Guardar"**

**2. Importar materiales desde archivo:**
- **Formatos soportados**: CSV, Excel (.xlsx), Excel (.xls)
- **Paso 1**: Hacer clic en **"Seleccionar archivo"**
- **Paso 2**: Elegir el archivo desde tu computadora
- **Paso 3**: Hacer clic en **"Importar"**
- **Paso 4**: Verificar que los datos se carguen correctamente

**Estructura del archivo de importación:**
```csv
Nombre,Cantidad,Tipo
Luminaria LED 50W,25,Luminarias
Cable eléctrico 2.5mm,100,Cables
Poste de concreto 8m,15,Postes
```

**3. Gestionar tipos de materiales:**
- **Paso 1**: Hacer clic en **"Gestionar Tipos"**
- **Paso 2**: En el modal que se abre:
  - **Agregar tipo**: Ingresar nombre del nuevo tipo
  - **Ver tipos existentes**: Lista de todos los tipos configurados
  - **Eliminar tipos**: Remover tipos que ya no se usen

**Ejemplo de tipos de materiales:**
- **Luminarias**: Bombillas, lámparas LED, reflectores
- **Cables**: Cable eléctrico, cable de conexión, cable subterráneo
- **Postes**: Postes de concreto, postes metálicos, bases
- **Accesorios**: Portalámparas, interruptores, fusibles
- **Herramientas**: Destornilladores, multímetros, escaleras

**4. Control de inventario:**
- **Verificar stock**: Revisar regularmente las cantidades disponibles
- **Alertas de stock bajo**: El sistema puede alertar cuando las cantidades sean bajas
- **Actualizar cantidades**: Modificar las cantidades después de usar materiales

**Ejemplo práctico:**
Para agregar nuevas luminarias al inventario:
1. Ir a "Materiales"
2. Hacer clic en "+ Nuevo Material"
3. Nombre: "Luminaria LED 100W"
4. Cantidad: "50"
5. Tipo: "Luminarias"
6. Hacer clic en "Guardar"
7. Verificar que aparezca en la lista

**Recomendaciones de gestión:**
- **Actualizar regularmente**: Revisar el inventario semanalmente
- **Categorizar correctamente**: Usar tipos específicos para facilitar la búsqueda
- **Mantener stock mínimo**: Asegurar que siempre haya materiales suficientes
- **Documentar uso**: Registrar qué materiales se usan en cada reparación

> **📝 Nota**: La gestión de materiales es crucial para la planificación de trabajos. Un inventario actualizado permite asignar tareas sabiendo que se tienen los materiales necesarios.

---

### 2.2 Supervisor

El supervisor es el responsable de coordinar las cuadrillas de trabajo, gestionar los reclamos ciudadanos, planificar las tareas de mantenimiento y supervisar el progreso de las reparaciones. Tiene acceso a todas las funcionalidades operativas del sistema.

#### 🔐 Acceso y Login

**Requisitos previos:**
- Navegador web actualizado
- Conexión a internet estable
- Credenciales de acceso (legajo y contraseña)
- Autorización del administrador del sistema

**Proceso de acceso:**

**Paso 1**: En la pantalla de inicio, hacer clic en **"Supervisor / Operario"**
- El botón tiene un ícono de corbata (👔) y está destacado en color verde
- Esta opción es compartida entre supervisores y operarios

**Paso 2**: Completar el formulario con:
- **Legajo**: Tu número de legajo institucional (ejemplo: "12345")
- **Contraseña**: Tu contraseña asignada (distinguir entre mayúsculas y minúsculas)

![Formulario de login supervisor](imagenes/login_supervisor_formulario.png)

**Paso 3**: Hacer clic en **"Iniciar Sesión"**
- Si las credenciales son correctas, serás redirigido al dashboard principal
- El sistema verificará automáticamente tu rol y mostrará las opciones correspondientes

**Diferencias entre Supervisor y Operario:**
- **Supervisor**: Acceso completo a gestión de cuadrillas, mapas, filtros avanzados
- **Operario**: Acceso limitado principalmente a tareas asignadas y cambio de estados

**Solución de problemas comunes:**
- **Error "Credenciales incorrectas"**: Verificar legajo y contraseña, asegurarse de no tener espacios adicionales
- **No aparecen todas las opciones**: Verificar que tu rol esté correctamente asignado
- **Sesión expirada**: El sistema cerrará la sesión por seguridad después de un tiempo de inactividad

#### 🗺️ Visualización de Reclamos en Mapa

**Descripción general:**
El mapa interactivo es una de las herramientas más importantes para el supervisor, ya que permite visualizar geográficamente todos los reclamos, planificar rutas de trabajo y coordinar las cuadrillas de manera eficiente.

**Acceder al mapa de reclamos:**

**Paso 1**: Desde el menú principal, hacer clic en **"Mapa Google"** o **"Mapa Mapbox"**
- **Mapa Google**: Usa Google Maps como base cartográfica
- **Mapa Mapbox**: Usa Mapbox como alternativa (puede tener mejor rendimiento)
- Ambos mapas muestran la misma información, solo cambia el proveedor de mapas

**Paso 2**: El mapa se cargará mostrando todos los reclamos con marcadores de colores según su estado
- La carga puede tomar unos segundos dependiendo de la cantidad de reclamos
- El mapa se centrará automáticamente en el área de la ciudad

![Mapa de reclamos](imagenes/mapa_reclamos.png)

**Leyenda de colores y estados:**

| Color | Estado | Descripción | Acción Requerida |
|-------|--------|-------------|------------------|
| ⚫ **Gris/Negro** | Recibido | Reclamo nuevo, sin asignar | Asignar a cuadrilla o incluir en hoja de ruta |
| 🔴 **Rojo** | Asignado | Asignado a cuadrilla a través de una hoja de ruta | Enviar cuadrilla al lugar |
| 🟡 **Amarillo/Dorado** | En ejecución | Trabajo en progreso | Supervisar progreso |
| 🟢 **Verde** | Completado | Trabajo finalizado, pendiente de cierre | Cerrar reclamo formalmente |
| ⚫ **Gris** | En plan | Programado para futura ejecución | Planificar ejecución |
| ⚫ **Gris** | Error de datos | Información incorrecta | Corregir datos |
| ⚫ **Gris Oscuro** | Cerrado | Reclamo cerrado formalmente | Solo consulta, no editable |

**Funcionalidades del mapa:**

**1. Navegación básica:**
- **Zoom**: Usar la rueda del mouse o los botones +/- para acercar/alejar
- **Desplazamiento**: Hacer clic y arrastrar para mover el mapa
- **Centrar**: Hacer doble clic en cualquier área para centrar la vista

**2. Interacción con marcadores:**
- **Clic en marcador**: Ver información básica del reclamo
- **Doble clic**: Abrir detalles completos del reclamo
- **Hover**: Ver información rápida sin hacer clic

**3. Tabla lateral de reclamos:**
- **Lista completa**: Todos los reclamos visibles en el mapa
- **Información resumida**: ID, dirección, número de domicilio
- **Acciones rápidas**: Botones para ver detalles o cambiar estado

**4. Filtros en tiempo real:**
- **Por estado**: Mostrar solo reclamos de ciertos estados
- **Por prioridad**: Filtrar por nivel de urgencia
- **Por fecha**: Mostrar reclamos de un rango de fechas específico

**Ejemplo práctico de uso:**
Para planificar la ruta de una cuadrilla:
1. Abrir el mapa de reclamos
2. Filtrar por estado "Asignado" (marcadores rojos)
3. Identificar los reclamos en una zona geográfica específica
4. Hacer clic en cada marcador para ver detalles
5. Planificar la ruta más eficiente visitando los reclamos en orden
6. Asignar la ruta a la cuadrilla correspondiente

**Optimización de rutas:**
- **Agrupar por zona**: Trabajar en una zona específica antes de moverse a otra
- **Considerar tráfico**: Planificar horarios para evitar congestiones
- **Priorizar urgencia**: Atender primero los reclamos de alta prioridad
- **Materiales necesarios**: Verificar que se tengan los materiales antes de salir

**Solución de problemas comunes:**
- **Mapa no carga**: Verificar conexión a internet y refrescar la página
- **Marcadores no aparecen**: Verificar que haya reclamos en el área visible
- **Cambiar entre mapas**: Usar el botón "Cambiar a Mapbox" o "Cambiar a Google Maps"
- **Rendimiento lento**: Reducir el zoom o usar filtros para mostrar menos reclamos

#### 🔍 Filtros y Búsquedas

**Descripción general:**
Los filtros son herramientas esenciales para el supervisor, permitiendo encontrar rápidamente reclamos específicos, analizar patrones y tomar decisiones informadas sobre la asignación de recursos.

**Aplicar filtros a los reclamos:**

**Paso 1**: Hacer clic en el botón **"Filtros"** en la página de reclamos
- El botón tiene un ícono de embudo (🔍) y está ubicado en la parte superior derecha
- Al hacer clic, se despliega un panel con todas las opciones de filtrado

**Paso 2**: Configurar los filtros deseados según tus necesidades

![Panel de filtros](imagenes/reclamos_filtros.png)

**Tipos de filtros disponibles:**

**1. Filtro por Estado:**
- **Todos los estados**: Mostrar todos los reclamos sin filtrar
- **Recibido**: Reclamos nuevos que aún no han sido asignados
- **Asignado**: Reclamos asignados a cuadrillas pero no iniciados
- **En ejecución**: Reclamos con trabajo en progreso
- **Completado**: Reclamos finalizados
- **En plan**: Reclamos programados para futura ejecución
- **Error de datos**: Reclamos con información incorrecta

**2. Filtro por Prioridad:**
- **Todas las prioridades**: Sin filtro de prioridad
- **Alta**: Reclamos urgentes que requieren atención inmediata
- **Media**: Reclamos de prioridad normal
- **Baja**: Reclamos que pueden esperar

**3. Filtro por Fechas:**
- **Fecha Desde**: Mostrar reclamos desde una fecha específica
- **Fecha Hasta**: Mostrar reclamos hasta una fecha específica
- **Rango personalizado**: Combinar ambas fechas para un período específico

**4. Filtro por Motivo (disponible en algunas vistas):**
- **Luminaria agotada**: Problemas con bombillas que prenden y apagan
- **Postes/cables caídos**: Estructuras o cables en peligro
- **Semáforos**: Problemas con señalización vial
- **Luminarias quemadas**: Bombillas completamente fundidas
- **Corte de ramas**: Vegetación que interfiere con cables
- **Columnas caídas**: Postes de alumbrado en mal estado
- **Cables caídos**: Cables de alumbrado en el suelo

**Ejemplos de uso de filtros:**

**Ejemplo 1: Reclamos urgentes pendientes**
1. Estado: "Recibido" o "Asignado"
2. Prioridad: "Alta"
3. Fecha Desde: "Hoy"
4. Resultado: Solo reclamos urgentes que necesitan atención inmediata

**Ejemplo 2: Trabajo completado esta semana**
1. Estado: "Completado"
2. Fecha Desde: "Lunes de esta semana"
3. Fecha Hasta: "Hoy"
4. Resultado: Todos los trabajos finalizados en la semana actual

**Ejemplo 3: Reclamos de luminarias en un barrio específico**
1. Motivo: "Luminaria agotada" o "Luminarias quemadas"
2. Estado: "Todos los estados"
3. Usar búsqueda de texto para el nombre del barrio
4. Resultado: Solo problemas relacionados con luminarias en esa zona

**Funcionalidades avanzadas:**

**1. Combinación de filtros:**
- **Múltiples criterios**: Puedes usar varios filtros simultáneamente
- **Filtros complementarios**: Los filtros se combinan con lógica "Y" (AND)
- **Resultados precisos**: Cuantos más filtros uses, más específicos serán los resultados

**2. Búsqueda de texto:**
- **Buscar por dirección**: Escribir parte de la dirección para encontrar reclamos específicos
- **Buscar por ID**: Ingresar el número de reclamo para encontrar uno específico
- **Buscar por ciudadano**: Buscar reclamos de un ciudadano específico

**3. Ordenamiento de resultados:**
- **Por fecha**: Más recientes primero o más antiguos primero
- **Por prioridad**: Alta, Media, Baja
- **Por estado**: Agrupar por estado del reclamo
- **Por dirección**: Ordenar alfabéticamente por calle

**Paso 3**: Hacer clic en **"Limpiar"** para resetear todos los filtros
- Esto restaura la vista original con todos los reclamos
- Útil cuando quieres empezar una nueva búsqueda

**Guardar configuraciones de filtros:**
- **Filtros frecuentes**: El sistema puede recordar tus filtros más usados
- **Configuraciones personalizadas**: Crear combinaciones de filtros para casos específicos
- **Acceso rápido**: Usar filtros guardados para análisis repetitivos

**Análisis con filtros:**

**Análisis de productividad:**
1. Filtrar por estado "Completado" y fecha de la semana pasada
2. Contar cuántos reclamos se resolvieron
3. Comparar con semanas anteriores para medir eficiencia

**Análisis de tipos de problemas:**
1. Filtrar por motivo específico (ej: "Luminarias quemadas")
2. Ver la distribución geográfica en el mapa
3. Identificar patrones o zonas problemáticas

**Planificación de recursos:**
1. Filtrar por estado "Asignado" y prioridad "Alta"
2. Ver cuántos trabajos urgentes están pendientes
3. Asignar cuadrillas según la carga de trabajo

**Solución de problemas comunes:**
- **Filtros no funcionan**: Refrescar la página y volver a aplicar los filtros
- **No aparecen resultados**: Verificar que los criterios no sean demasiado restrictivos
- **Filtros se pierden**: Los filtros se mantienen mientras navegas en la misma sesión
- **Rendimiento lento**: Usar filtros más específicos para reducir la cantidad de datos

#### 📋 Gestión de Hojas de Ruta

**Descripción general:**
El módulo de Hojas de Ruta es una funcionalidad avanzada que permite generar automáticamente rutas optimizadas para las cuadrillas. El sistema utiliza algoritmos de optimización que consideran la prioridad de los reclamos y la proximidad geográfica para crear recorridos eficientes. Las hojas de ruta pueden generarse automáticamente o editarse manualmente según las necesidades operativas.

**Acceder al módulo de Hojas de Ruta:**

**Paso 1**: Hacer clic en **"Rutas"** en el menú principal
- El ícono muestra un mapa o ruta (🗺️)
- Esta funcionalidad está disponible para supervisores y administradores

**Paso 2**: Visualizar la tabla de hojas de ruta existentes
- La tabla muestra todas las rutas creadas en el sistema
- Cada ruta muestra su nombre, cantidad de reclamos, tiempo estimado y estado de asignación

![Tabla de hojas de ruta](imagenes/rutas_tabla.png)

### 🆕 Crear Nueva Hoja de Ruta

**Proceso de creación:**

**Paso 1**: Hacer clic en **"+ Nueva Hoja de Ruta"**
- El botón está ubicado en la parte superior de la página
- Se abrirá un modal con el formulario de creación

**Paso 2**: Configurar los parámetros básicos:
- **Nombre**: Nombre descriptivo de la hoja de ruta (ej: "Ruta Zona Norte - 15/03/2024")
- **Color**: Seleccionar un color para identificar la ruta en los mapas
- **Cantidad de reclamos**: Número de reclamos que deseas incluir en la ruta
  - El sistema validará que haya suficientes reclamos disponibles
  - Solo se pueden incluir reclamos que no estén completados ni asignados a otras rutas

![Formulario crear ruta](imagenes/ruta_crear_formulario.png)

**Paso 3**: Hacer clic en **"Generar Vista Previa"**
- El sistema ejecutará el algoritmo de optimización
- Se mostrará una vista previa de la ruta propuesta en un mapa
- Podrás ver el orden de los reclamos, la ruta trazada y el tiempo estimado

**Algoritmo de optimización:**
El sistema utiliza un algoritmo inteligente que:
1. **Prioriza reclamos urgentes**: Los reclamos de prioridad "Alta" tienen preferencia
2. **Optimiza por proximidad**: Selecciona reclamos cercanos entre sí para minimizar desplazamientos
3. **Calcula ruta eficiente**: Ordena los reclamos en el mejor orden de visita
4. **Estima tiempo total**: Calcula tiempo de trabajo y desplazamiento

**Paso 4**: Revisar la vista previa
- **Panel izquierdo**: Lista de reclamos en el orden propuesto
- **Panel derecho**: Mapa con la ruta trazada y marcadores numerados
- **Información mostrada**: Tiempo estimado total, distancia total, cantidad de reclamos

![Vista previa de ruta](imagenes/ruta_vista_previa.png)

**Paso 5**: Opciones disponibles después de la vista previa:

**A) Crear ruta automática:**
- Si estás conforme con la ruta propuesta, hacer clic en **"Crear Ruta Automática"**
- La ruta se guardará en el sistema con estado "No Asignada"
- Podrás asignarla a una cuadrilla posteriormente

**B) Editar la ruta manualmente:**
- Hacer clic en **"Editar Hoja de Ruta"** para activar el modo de edición
- En modo edición puedes:
  - **Reordenar reclamos**: Usar las flechas ↑ ↓ para cambiar el orden
  - **Eliminar reclamos**: Quitar reclamos que no deseas incluir
  - **Agregar reclamos**: Hacer clic en reclamos del mapa para agregarlos
- Los cambios se reflejan en tiempo real en el mapa
- Puedes cancelar la edición para volver a la ruta original

**C) Cancelar:**
- Si no deseas crear la ruta, hacer clic en **"Cancelar"**
- Se cerrará el modal sin guardar cambios

### ✏️ Edición Manual de Hojas de Ruta

**Cuándo editar manualmente:**
- Cuando necesitas incluir reclamos específicos que el algoritmo no seleccionó
- Para ajustar el orden según conocimiento local (calles cerradas, obras, etc.)
- Para priorizar reclamos de clientes importantes
- Para agrupar reclamos por tipo de trabajo

**Operaciones de edición:**

**1. Reordenar reclamos:**
- En modo edición, usar los botones ↑ (arriba) y ↓ (abajo) junto a cada reclamo
- El orden se actualiza inmediatamente en el mapa
- La ruta se recalcula automáticamente

**2. Eliminar reclamos:**
- Hacer clic en el botón 🗑️ (eliminar) junto al reclamo
- El reclamo se quita de la ruta y vuelve a estar disponible
- El mapa se actualiza mostrando la nueva ruta

**3. Agregar reclamos:**
- Hacer clic en cualquier marcador del mapa que no esté en la ruta
- El reclamo se agregará al final de la lista
- El sistema validará que el reclamo esté disponible (no completado, no en otra ruta)

**4. Cancelar edición:**
- Hacer clic en **"Cancelar Edición"** para restaurar la ruta original
- Se perderán todos los cambios realizados en modo edición

### 👥 Asignar Hoja de Ruta a Cuadrilla

**Proceso de asignación:**

**Paso 1**: Desde la tabla de rutas, identificar la ruta que deseas asignar
- Las rutas con estado "No Asignada" pueden ser asignadas
- Las rutas ya asignadas muestran el nombre de la cuadrilla

**Paso 2**: Hacer clic en el botón de **"Asignar"** (ícono de personas) en la columna de acciones
- Se abrirá un modal con las cuadrillas disponibles
- Solo se muestran cuadrillas que tienen operarios asignados

**Paso 3**: Seleccionar la cuadrilla destino
- Elegir la cuadrilla del menú desplegable
- Verificar que la cuadrilla tenga operarios suficientes para la tarea

**Paso 4**: Confirmar la asignación
- Hacer clic en **"Asignar Ruta"**
- El sistema actualizará:
  - Estado de la ruta: de "No Asignada" a "Asignada"
  - Estado de los reclamos: de "Recibido" a "Asignado"
  - Asociación: La ruta queda vinculada a la cuadrilla

**Efectos de la asignación:**
- Los reclamos de la ruta cambian automáticamente a estado "Asignado"
- La ruta queda bloqueada para edición (no se puede modificar ni eliminar mientras esté asignada)
- Los operarios de la cuadrilla pueden ver la ruta en sus dispositivos

### 🔄 Desasignar Hoja de Ruta

**Cuándo desasignar:**
- Cuando necesitas reasignar la ruta a otra cuadrilla
- Cuando la cuadrilla no puede completar la ruta
- Para liberar reclamos para una nueva planificación

**Proceso de desasignación:**

**Paso 1**: Desde la tabla de rutas, hacer clic en **"Desasignar"** en la ruta asignada
- El botón solo aparece en rutas que están asignadas

**Paso 2**: Confirmar la desasignación
- Se mostrará un mensaje de confirmación
- Al confirmar:
  - La ruta vuelve a estado "No Asignada"
  - Los reclamos vuelven a estado "Recibido"
  - La ruta queda disponible para reasignación o edición

> **⚠️ Importante**: Al desasignar una ruta, los reclamos vuelven a estar disponibles para incluirse en otras rutas. Verifica que esto sea lo que deseas antes de confirmar.

### 👁️ Visualizar Hoja de Ruta

**Ver una ruta específica:**

**Paso 1**: Desde la tabla de rutas, hacer clic en el botón de **"Ver"** (ícono de ojo)
- Se abrirá un modal con la visualización completa de la ruta

**Paso 2**: Explorar la ruta:
- **Panel izquierdo**: Lista de reclamos con información detallada
  - Hacer clic en un reclamo para centrar el mapa en ese punto
  - Ver dirección, motivo, estado y prioridad de cada reclamo
- **Panel derecho**: Mapa interactivo con la ruta trazada
  - Marcadores numerados indican el orden de visita
  - Línea de ruta muestra el recorrido optimizado
  - Puedes alternar entre Google Maps y Mapbox

**Visualizar todas las rutas simultáneamente:**

**Paso 1**: Hacer clic en **"Visualizar Rutas"** en la parte superior
- Se abrirá un modal mostrando todas las rutas del sistema

**Paso 2**: Explorar las rutas:
- **Panel izquierdo**: Lista de todas las rutas con su estado
  - Hacer clic en una ruta para centrar el mapa en ella
  - Cada ruta se muestra con su color distintivo
- **Panel derecho**: Mapa con todas las rutas superpuestas
  - Cada ruta tiene un color diferente para fácil identificación
  - Útil para planificar y evitar solapamientos geográficos

### 🗑️ Eliminar Hoja de Ruta

**Cuándo eliminar:**
- Cuando la ruta ya no es necesaria
- Para liberar reclamos para una nueva planificación
- Cuando se creó una ruta por error

**Restricciones:**
- **No se puede eliminar** una ruta que está asignada a una cuadrilla
- Primero debes desasignarla, luego podrás eliminarla

**Proceso de eliminación:**

**Paso 1**: Desde la tabla de rutas, hacer clic en el botón de **"Eliminar"** (ícono de basura)
- Solo aparece en rutas no asignadas

**Paso 2**: Confirmar la eliminación
- Se mostrará un mensaje de confirmación
- Al confirmar:
  - La ruta se elimina del sistema
  - Los reclamos vuelven a estar disponibles (si estaban en estado "Asignado", vuelven a "Recibido")
  - Se elimina el registro de la relación ruta-reclamo

> **⚠️ Importante**: La eliminación es permanente. Asegúrate de que realmente deseas eliminar la ruta antes de confirmar.

### 📊 Información de Hojas de Ruta

**Datos mostrados en la tabla:**

| Columna | Descripción | Ejemplo |
|---------|-------------|---------|
| **Nombre** | Nombre descriptivo de la ruta | "Ruta Zona Norte - 15/03/2024" |
| **Cantidad de Reclamos** | Número de reclamos incluidos | "12" |
| **Tiempo Estimado** | Tiempo total estimado de la ruta | "03:45:00" (3 horas 45 minutos) |
| **Asignación** | Estado y cuadrilla asignada | "Asignada - Cuadrilla Norte" o "No Asignada" |
| **Fecha de Creación** | Cuándo se creó la ruta | "15/03/2024 10:30" |

**Cálculo del tiempo estimado:**
- **Tiempo de trabajo**: 15 minutos por reclamo (trabajo en sitio)
- **Tiempo de desplazamiento**: Basado en distancia y velocidad promedio urbana (30 km/h)
- **Total**: Suma de ambos tiempos

**Estados de una hoja de ruta:**
- **No Asignada**: Ruta creada pero aún no asignada a ninguna cuadrilla
- **Asignada**: Ruta asignada a una cuadrilla, los reclamos están en estado "Asignado"

### 💡 Recomendaciones para Uso de Hojas de Ruta

**Mejores prácticas:**
- **Cantidad óptima**: Crear rutas con 10-20 reclamos para balance entre eficiencia y tiempo de trabajo
- **Revisar vista previa**: Siempre revisar la vista previa antes de crear la ruta
- **Agrupar por zona**: Crear rutas que agrupen reclamos de zonas geográficas cercanas
- **Priorizar urgencia**: El sistema ya prioriza reclamos de alta prioridad, pero puedes ajustar manualmente
- **Asignar temprano**: Asignar las rutas a las cuadrillas al inicio del día para mejor planificación
- **Monitorear progreso**: Revisar regularmente el estado de las rutas asignadas

**Solución de problemas comunes:**
- **No hay suficientes reclamos**: Verificar que haya reclamos disponibles (no completados, no en otras rutas)
- **Ruta no se puede asignar**: Verificar que la cuadrilla tenga operarios asignados
- **No puedo eliminar ruta**: La ruta debe estar desasignada antes de poder eliminarla
- **Reclamos no aparecen en mapa**: Verificar que los reclamos tengan direcciones válidas

---

### 👥 Gestión de Cuadrillas

**Crear nueva cuadrilla:**

**Paso 1**: Hacer clic en **"Cuadrillas"** en el menú principal
- El ícono muestra un grupo de personas (👥)
- Esta funcionalidad está disponible solo para supervisores y administradores

**Paso 2**: Hacer clic en **"+ Nueva Cuadrilla"**
- El botón está ubicado en la parte superior izquierda
- Se abrirá un modal para crear la nueva cuadrilla

**Paso 3**: Completar el formulario de creación:
- **Nombre**: Nombre descriptivo de la cuadrilla (ej: "Cuadrilla Norte", "Equipo Zona Centro")
- **Descripción**: Detalles adicionales sobre la cuadrilla (opcional pero recomendado)

![Formulario nueva cuadrilla](imagenes/cuadrilla_nueva.png)

**Ejemplos de nombres de cuadrillas:**
- "Cuadrilla Norte - Turno Mañana"
- "Equipo Especializado en Semáforos"
- "Cuadrilla de Emergencias"
- "Equipo Zona Centro - Turno Tarde"

**Paso 4**: Hacer clic en **"Guardar"**
- La cuadrilla se creará y aparecerá en la lista
- Inicialmente estará vacía (sin operarios asignados)

### 👷 Asignación de Operarios a Cuadrilla

**Proceso de asignación:**

**Paso 1**: Seleccionar una cuadrilla de la lista haciendo clic en la fila
- La fila se destacará en color azul cuando esté seleccionada
- Solo puedes administrar una cuadrilla a la vez

**Paso 2**: Hacer clic en **"ADMINISTRAR CUADRILLA"**
- El botón se activará solo cuando hayas seleccionado una cuadrilla
- Se abrirá un modal con opciones avanzadas de gestión

![Administración de cuadrilla](imagenes/cuadrilla_administrar.png)

**Paso 3**: En la sección "Agregar Operarios":
- **Ver operarios disponibles**: Lista de todos los operarios que no están asignados a otras cuadrillas
- **Seleccionar operarios**: Marcar con checkbox los operarios que deseas agregar
- **Seleccionar todos**: Usar el checkbox superior para seleccionar todos los operarios disponibles

**Información de operarios disponibles:**
| Columna | Descripción | Ejemplo |
|---------|-------------|---------|
| **Nombre** | Nombre completo del operario | "Carlos Mendoza" |
| **Legajo** | Número de identificación | "12345" |
| **Estado** | Disponibilidad actual | "Disponible", "En otra cuadrilla" |

**Paso 4**: Hacer clic en **"Agregar Seleccionados"**
- Los operarios seleccionados se moverán a la sección "Operarios Asignados"
- El contador mostrará cuántos operarios has seleccionado

### 📋 Gestión de Operarios Asignados

**Ver operarios asignados:**

**Paso 1**: En la sección "Operarios Asignados" verás:
- **Lista completa**: Todos los operarios que pertenecen a esta cuadrilla
- **Información detallada**: Nombre y legajo de cada operario
- **Acciones disponibles**: Botón para quitar operarios

**Paso 2**: Para quitar un operario:
- Hacer clic en el botón rojo de eliminar (🗑️) junto al operario
- El operario volverá a la lista de operarios disponibles
- Esta acción es inmediata y no requiere confirmación adicional

**Ejemplo práctico de gestión de cuadrilla:**
Para crear una cuadrilla especializada en semáforos:
1. Ir a "Cuadrillas"
2. Hacer clic en "+ Nueva Cuadrilla"
3. Nombre: "Cuadrilla Semáforos"
4. Descripción: "Equipo especializado en reparación y sincronización de semáforos"
5. Hacer clic en "Guardar"
6. Seleccionar la cuadrilla recién creada
7. Hacer clic en "ADMINISTRAR CUADRILLA"
8. Seleccionar 3-4 operarios con experiencia en semáforos
9. Hacer clic en "Agregar Seleccionados"
10. Hacer clic en "Guardar" para confirmar

### 📊 Seguimiento y Monitoreo de Cuadrillas

**Monitorear estado de cuadrillas:**

**Paso 1**: En la página de cuadrillas, revisar la lista de cuadrillas activas
- **Nombre de la cuadrilla**: Identificación clara
- **Descripción**: Detalles sobre el propósito de la cuadrilla
- **Cantidad de operarios**: Número de personas asignadas

![Lista de cuadrillas](imagenes/cuadrillas_lista.png)

**Información disponible:**
| Columna | Descripción | Uso |
|---------|-------------|-----|
| **Nombre** | Nombre de la cuadrilla | Identificación rápida |
| **Descripción** | Propósito y detalles | Contexto del trabajo |
| **Operarios** | Cantidad de personas | Planificación de recursos |

**Indicadores de eficiencia:**
- **Cuadrillas con pocos operarios**: Pueden necesitar más personal
- **Cuadrillas sobrecargadas**: Pueden necesitar redistribución de tareas
- **Cuadrillas especializadas**: Verificar que tengan las tareas adecuadas

**Recomendaciones de gestión:**
- **Revisar semanalmente**: Verificar que las cuadrillas estén bien balanceadas
- **Rotar operarios**: Evitar que siempre trabajen las mismas personas juntas
- **Especialización**: Crear cuadrillas especializadas para tipos específicos de trabajo
- **Capacitación**: Asegurar que todos los operarios tengan las habilidades necesarias

**Solución de problemas comunes:**
- **No se pueden agregar operarios**: Verificar que los operarios no estén asignados a otras cuadrillas
- **Operarios no aparecen**: Confirmar que los operarios estén registrados en el sistema
- **Cuadrilla no se guarda**: Verificar que se haya completado el nombre obligatorio
- **Acceso denegado**: Solo supervisores y administradores pueden gestionar cuadrillas

### 📊 Seguimiento de Reclamos

**Descripción general:**
El seguimiento de reclamos permite al supervisor monitorear el progreso de todos los trabajos, identificar cuellos de botella y tomar decisiones informadas sobre la asignación de recursos.

**Acceder al seguimiento de reclamos:**

**Paso 1**: Hacer clic en **"Tareas"** para ver la vista de tarjetas de reclamos
- Esta vista está optimizada para supervisores y operarios
- Muestra los reclamos en formato de tarjetas fáciles de leer
- Permite acciones rápidas sobre cada reclamo

**Paso 2**: Analizar la información de cada tarjeta

![Vista de tareas](imagenes/tareas_vista.png)

**Información disponible en cada tarjeta:**

| Elemento | Descripción | Uso |
|----------|-------------|-----|
| **ID del reclamo** | Número único de identificación | Referencia rápida |
| **Estado actual** | Badge de color con el estado | Identificación visual del progreso |
| **Dirección** | Ubicación completa del problema | Planificación de rutas |
| **Motivo** | Tipo de problema reportado | Asignación de especialistas |

**Estados y colores de las tarjetas:**

**Tarjetas por estado:**
- **Recibido** (Gris): Reclamos nuevos que necesitan asignación
- **Asignado** (Azul): Reclamos asignados a cuadrillas
- **En ejecución** (Amarillo): Trabajos en progreso
- **Completado** (Verde): Trabajos finalizados

**Funcionalidades de seguimiento:**

**1. Vista general del estado:**
- **Contar reclamos por estado**: Identificar cuántos trabajos hay en cada fase
- **Identificar cuellos de botella**: Ver dónde se acumulan los reclamos
- **Planificar recursos**: Asignar cuadrillas según la carga de trabajo

**2. Acciones rápidas:**
- **Ver detalles**: Hacer clic en la tarjeta para ver información completa
- **Cambiar estado**: Usar el botón "Acciones" para actualizar el estado
- **Asignar cuadrilla**: Cambiar el estado de "Recibido" a "Asignado"

**3. Filtrado y búsqueda:**
- **Por estado**: Ver solo reclamos de un estado específico
- **Por prioridad**: Filtrar por nivel de urgencia
- **Por zona**: Buscar reclamos en una área geográfica específica

**Ejemplo práctico de seguimiento:**
Para hacer un seguimiento diario:
1. Abrir "Tareas"
2. Contar reclamos por estado:
   - Recibidos: 15 (necesitan asignación)
   - Asignados: 8 (listos para ejecutar)
   - En ejecución: 12 (trabajando)
   - Completados: 25 (finalizados hoy)
3. Identificar que hay muchos reclamos recibidos
4. Asignar más cuadrillas a trabajos pendientes
5. Priorizar reclamos de alta prioridad

**Paso 3**: Hacer clic en **"Acciones"** para cambiar el estado del reclamo
- Se abrirá un modal con opciones para gestionar el reclamo
- Podrás cambiar el estado, ver historial y agregar observaciones

**Métricas de seguimiento:**

**Indicadores clave de rendimiento:**
- **Tiempo promedio de resolución**: Cuánto tiempo toma resolver un reclamo
- **Tasa de completado**: Porcentaje de reclamos resueltos exitosamente
- **Eficiencia de cuadrillas**: Cuántos reclamos resuelve cada cuadrilla por día
- **Satisfacción ciudadana**: Basado en reclamos repetidos en la misma zona

**Reportes de seguimiento:**
- **Reporte diario**: Resumen de trabajos del día
- **Reporte semanal**: Análisis de tendencias y productividad
- **Reporte mensual**: Evaluación de metas y objetivos

**Recomendaciones de seguimiento:**
- **Revisar diariamente**: Hacer seguimiento diario del progreso
- **Identificar patrones**: Buscar zonas o tipos de problemas recurrentes
- **Optimizar procesos**: Mejorar la eficiencia basándose en los datos
- **Comunicar resultados**: Informar a la administración sobre el progreso

#### 🔒 Cierre de Reclamos

**Descripción general:**
El módulo de Cierre de Reclamos permite cerrar formalmente los reclamos que han sido completados. Al cerrar un reclamo, se marca como cerrado en el sistema local y se sincroniza automáticamente con el sistema externo 103 de la municipalidad. Los reclamos cerrados quedan bloqueados para edición y no pueden cambiar de estado.

**Acceder al módulo de cierre:**

**Paso 1**: Hacer clic en **"Cierre"** en el menú principal
- El ícono muestra un candado (🔒)
- Esta funcionalidad está disponible para supervisores y administradores

**Paso 2**: Visualizar el resumen de reclamos
- **Tarjeta "Completados"**: Muestra la cantidad de reclamos completados pendientes de cierre
- **Tarjeta "Cerrados"**: Muestra la cantidad de reclamos ya cerrados formalmente

![Vista de cierre de reclamos](imagenes/cierre_reclamos_vista.png)

### 📋 Cerrar Reclamos Completados

**Proceso de cierre:**

**Paso 1**: Revisar la tabla de reclamos completados
- La tabla muestra todos los reclamos con estado "Completado" que aún no han sido cerrados
- Información mostrada: ID, motivo, domicilio, fecha de inicio, fecha de modificación

**Paso 2**: Seleccionar reclamos para cerrar
- **Selección individual**: Marcar el checkbox de cada reclamo que deseas cerrar
- **Selección múltiple**: Usar el checkbox del encabezado para seleccionar todos
- El contador muestra cuántos reclamos has seleccionado

**Paso 3**: Hacer clic en **"Cerrar Reclamos"**
- El botón muestra la cantidad de reclamos seleccionados
- Se iniciará el proceso de cierre

**Proceso automático de cierre:**
1. **Validación**: El sistema verifica que los reclamos estén en estado "Completado"
2. **Sincronización con sistema 103**: Se envía el cierre al sistema externo de la municipalidad
3. **Registro local**: Si la sincronización es exitosa, se marca el reclamo como cerrado en la base de datos local
4. **Registro de fecha**: Se guarda la fecha y hora del cierre
5. **Bloqueo**: El reclamo queda bloqueado para edición

**Resultado del cierre:**
- **Reclamos cerrados exitosamente**: Se muestran en un mensaje de confirmación
- **Reclamos enviados al sistema 103**: Se indica cuántos se sincronizaron correctamente
- **Errores**: Si algún reclamo no se pudo cerrar, se muestra un mensaje de error específico

> **⚠️ Importante**: Una vez cerrado, un reclamo NO puede cambiar de estado ni ser editado. Asegúrate de que el trabajo esté realmente completado antes de cerrar.

### 📊 Visualizar Reclamos Cerrados

**Ver historial de cierres:**

**Paso 1**: En la sección "Reclamos Cerrados", revisar la tabla
- Muestra todos los reclamos que han sido cerrados formalmente
- Información mostrada: ID, motivo, domicilio, fecha de inicio, fecha de cierre

**Información disponible:**
- **Fecha de cierre**: Cuándo se cerró formalmente el reclamo
- **Estado**: Siempre muestra "Cerrado" (bloqueado)
- **Sincronización**: Indica si se sincronizó correctamente con el sistema 103

**Filtros y búsqueda:**
- Usar la búsqueda de la tabla para encontrar reclamos específicos
- Filtrar por fecha de cierre para ver cierres de períodos específicos
- Ordenar por cualquier columna para organizar la información

### 🔄 Sincronización con Sistema 103

**¿Qué es la sincronización?**
Cuando se cierra un reclamo, el sistema automáticamente:
1. Envía una petición al sistema externo 103 de la municipalidad
2. Actualiza el estado del reclamo en el sistema 103 a "CP" (Completado)
3. Registra el cierre en la base de datos local

**Requisitos para sincronización:**
- **Token 103 configurado**: Debe haber credenciales válidas configuradas en "Token 103"
- **Conexión a internet**: El sistema necesita conectarse al sistema externo
- **Reclamo válido**: El reclamo debe tener un `municipalidad_id` válido

**Manejo de errores:**
- **Si falla la sincronización**: El reclamo NO se cierra localmente
- **Mensaje de error**: Se muestra un mensaje específico indicando qué reclamos no se pudieron cerrar
- **Reintento**: Puedes intentar cerrar nuevamente los reclamos que fallaron

**Ejemplo de mensaje de éxito:**
```
✅ Cierre exitoso
Reclamos cerrados: 5
Fecha de cierre: 15/03/2024 14:30

Sincronización con Sistema 103:
✅ 5 reclamo(s) cerrado(s) y enviado(s) exitosamente al sistema 103
IDs: 12345, 12346, 12347, 12348, 12349
```

### 💡 Recomendaciones para Cierre de Reclamos

**Mejores prácticas:**
- **Cerrar en lotes**: Cerrar múltiples reclamos a la vez para eficiencia
- **Verificar antes de cerrar**: Asegurarse de que el trabajo esté realmente completado
- **Revisar regularmente**: Cerrar los reclamos completados al final de cada día o semana
- **Monitorear sincronización**: Verificar que los cierres se sincronicen correctamente con el sistema 103
- **Mantener historial**: Los reclamos cerrados quedan registrados para auditoría

**Solución de problemas comunes:**
- **No puedo cerrar reclamos**: Verificar que los reclamos estén en estado "Completado"
- **Error de sincronización**: Verificar que el Token 103 esté configurado correctamente
- **Reclamos no aparecen**: Solo aparecen reclamos con estado "Completado" que no están cerrados
- **Cierre fallido**: Revisar los logs del sistema o contactar al administrador

---

### 2.3 Operario

El operario es el personal técnico que ejecuta las reparaciones en campo. Su función principal es realizar los trabajos asignados, actualizar el estado de los reclamos y registrar las observaciones del trabajo realizado. Tiene acceso limitado pero esencial para el funcionamiento del sistema.

#### 🔐 Acceso y Login

**Requisitos previos:**
- Navegador web actualizado (funciona mejor en Chrome o Firefox)
- Conexión a internet estable (importante para trabajo en campo)
- Credenciales de acceso (legajo y contraseña)
- Autorización del supervisor o administrador

**Proceso de acceso:**

**Paso 1**: En la pantalla de inicio, hacer clic en **"Supervisor / Operario"**
- El botón tiene un ícono de corbata (👔) y está destacado en color verde
- Esta opción es compartida entre supervisores y operarios

**Paso 2**: Completar el formulario con:
- **Legajo**: Tu número de legajo institucional (ejemplo: "12345")
- **Contraseña**: Tu contraseña asignada (distinguir entre mayúsculas y minúsculas)

![Formulario de login operario](imagenes/login_operario_formulario.png)

**Paso 3**: Hacer clic en **"Iniciar Sesión"**
- Si las credenciales son correctas, serás redirigido al dashboard principal
- El sistema verificará automáticamente tu rol y mostrará las opciones correspondientes

**Diferencias de acceso del Operario:**
- **Acceso limitado**: Solo puede ver y gestionar tareas asignadas
- **Sin gestión de cuadrillas**: No puede crear o modificar cuadrillas
- **Sin configuración**: No puede acceder a configuraciones del sistema
- **Enfoque en ejecución**: Su interfaz está optimizada para ejecutar tareas

**Solución de problemas comunes:**
- **Error "Credenciales incorrectas"**: Verificar legajo y contraseña, asegurarse de no tener espacios adicionales
- **No aparecen las tareas**: Contactar al supervisor para verificar asignaciones
- **Sesión expirada**: El sistema cerrará la sesión por seguridad después de un tiempo de inactividad
- **Acceso desde móvil**: El sistema funciona en dispositivos móviles para trabajo en campo

#### 📱 Visualización de Reclamos Asignados

**Descripción general:**
La vista de tareas está especialmente diseñada para operarios, mostrando los reclamos en formato de tarjetas fáciles de leer y gestionar desde dispositivos móviles o computadoras. Esta interfaz permite un acceso rápido a la información esencial y acciones necesarias.

**Acceder a los reclamos asignados:**

**Paso 1**: Hacer clic en **"Tareas"** en el menú principal
- Esta es la vista principal para operarios
- Muestra todos los reclamos disponibles para trabajar
- La interfaz está optimizada para dispositivos móviles

**Paso 2**: Analizar las tarjetas de reclamos disponibles
- Cada tarjeta representa un reclamo que necesita atención
- Las tarjetas se organizan automáticamente por prioridad y fecha
- Puedes ver múltiples reclamos en la misma pantalla

![Tarjetas de reclamos](imagenes/operario_tareas.png)

**Información disponible en cada tarjeta:**

| Elemento | Descripción | Importancia |
|----------|-------------|-------------|
| **ID del reclamo** | Número único de identificación | Referencia para reportes y comunicación |
| **Estado actual** | Badge de color con el estado | Identificación visual del progreso |
| **Dirección** | Ubicación completa del problema | Navegación y ubicación |
| **Motivo** | Tipo de problema reportado | Preparación de herramientas y materiales |

**Estados de las tarjetas y su significado:**

**Estados disponibles para operarios:**
- **Recibido** (Gris): Reclamo nuevo, listo para ser asignado
- **Asignado** (Azul/Rojo): Reclamo asignado a tu cuadrilla a través de una hoja de ruta, listo para ejecutar
- **En ejecución** (Amarillo/Dorado): Trabajo en progreso (tuyo o de otro operario)
- **Completado** (Verde): Trabajo finalizado exitosamente, pendiente de cierre formal

**Interpretación de colores:**
- **Gris**: Trabajo pendiente de asignación
- **Azul**: Tu próximo trabajo
- **Amarillo**: Trabajo en progreso
- **Verde**: Trabajo completado

**Funcionalidades de las tarjetas:**

**1. Información rápida:**
- **Vista previa**: Información esencial sin necesidad de abrir detalles
- **Identificación visual**: Colores y badges para reconocimiento rápido
- **Priorización**: Los reclamos más urgentes aparecen primero

**2. Acceso a detalles:**
- **Clic en tarjeta**: Abrir información completa del reclamo
- **Información detallada**: Dirección completa, descripción, contacto del ciudadano
- **Historial**: Ver cambios de estado anteriores

**3. Acciones rápidas:**
- **Botón "Acciones"**: Acceso directo a cambiar estado del reclamo
- **Cambio de estado**: Actualizar el progreso del trabajo
- **Registro de observaciones**: Documentar el trabajo realizado

**Ejemplo práctico de uso:**
Para comenzar tu jornada de trabajo:
1. Abrir "Tareas"
2. Identificar reclamos con estado "Asignado" (azul)
3. Seleccionar el primer reclamo de la lista
4. Hacer clic en la tarjeta para ver detalles completos
5. Anotar la dirección y materiales necesarios
6. Hacer clic en "Acciones" para cambiar estado a "En ejecución"
7. Dirigirse al lugar del reclamo

**Optimización para trabajo en campo:**

**Uso en dispositivos móviles:**
- **Interfaz responsive**: Se adapta automáticamente a pantallas pequeñas
- **Botones grandes**: Fáciles de presionar con guantes
- **Navegación simple**: Menos clics para llegar a la información necesaria
- **Carga rápida**: Optimizado para conexiones lentas

**Información esencial visible:**
- **Dirección**: Para navegación con GPS
- **Motivo**: Para preparar herramientas adecuadas
- **Estado**: Para saber qué acción tomar
- **ID**: Para comunicación con supervisor

**Recomendaciones de uso:**
- **Revisar antes de salir**: Verificar todos los reclamos asignados
- **Planificar ruta**: Agrupar reclamos por proximidad geográfica
- **Verificar materiales**: Asegurar tener las herramientas necesarias
- **Actualizar estados**: Mantener el progreso actualizado en tiempo real

**Solución de problemas comunes:**
- **No aparecen reclamos**: Verificar que tengas reclamos asignados a tu cuadrilla
- **Tarjetas no cargan**: Verificar conexión a internet
- **Información incompleta**: Contactar al supervisor para datos adicionales
- **Estado no se actualiza**: Refrescar la página y volver a intentar

#### ✅ Marcado de Estados y Registro de Observaciones

**Descripción general:**
El cambio de estados es la función principal del operario en el sistema. Permite documentar el progreso del trabajo, mantener informado al supervisor y crear un historial completo de cada reclamo. Es fundamental para el seguimiento y la gestión eficiente de los recursos.

**Cambiar estado de un reclamo:**

**Paso 1**: Hacer clic en la tarjeta del reclamo que deseas gestionar
- Se abrirá un modal con información detallada del reclamo
- Podrás ver todos los datos: dirección, motivo, descripción, contacto del ciudadano

**Paso 2**: Hacer clic en **"Acciones"**
- El botón está ubicado en la parte inferior de la tarjeta
- Se abrirá un modal con opciones para gestionar el reclamo

**Paso 3**: En el modal que se abre, seleccionar el **"Nuevo Estado"**:

![Cambio de estado](imagenes/operario_cambiar_estado.png)

**Estados disponibles y cuándo usarlos:**

| Estado | Cuándo usar | Descripción | Acción siguiente |
|--------|-------------|-------------|------------------|
| **Recibido** | Reclamo nuevo | Reclamo ingresado al sistema | Asignar a cuadrilla |
| **Asignado** | Trabajo asignado | Asignado a cuadrilla, listo para ejecutar | Ir al lugar del trabajo |
| **En ejecución** | Trabajo iniciado | Trabajo en progreso | Continuar con la reparación |
| **Completado** | Trabajo finalizado | Trabajo terminado exitosamente | Verificar calidad |

**Proceso detallado de cambio de estado:**

**1. Estado "En ejecución":**
- **Cuándo usar**: Cuando llegas al lugar del reclamo y comienzas a trabajar
- **Proceso**: 
  1. Llegar al lugar del reclamo
  2. Verificar el problema reportado
  3. Hacer clic en "Acciones"
  4. Seleccionar "En ejecución"
  5. Hacer clic en "Guardar Cambio de Estado"

**2. Estado "Completado":**
- **Cuándo usar**: Cuando has terminado la reparación exitosamente
- **Proceso**:
  1. Completar la reparación
  2. Verificar que funcione correctamente
  3. Limpiar el área de trabajo
  4. Hacer clic en "Acciones"
  5. Seleccionar "Completado"
  6. Hacer clic en "Guardar Cambio de Estado"

**Ejemplo práctico de cambio de estado:**
Para reparar una luminaria quemada:
1. **Asignado → En ejecución**: Llegar al lugar, verificar el problema, cambiar estado
2. **En ejecución**: Trabajar en la reparación (cambiar bombilla, verificar conexiones)
3. **En ejecución → Completado**: Terminar la reparación, verificar que funcione, cambiar estado

**Paso 4**: Hacer clic en **"Guardar Cambio de Estado"**
- El sistema registrará el cambio con fecha y hora
- Se creará una entrada en el historial del reclamo
- El supervisor será notificado del cambio de estado

**Registro de observaciones:**

**Importancia de las observaciones:**
- **Documentación**: Registrar qué se hizo exactamente
- **Comunicación**: Informar al supervisor sobre detalles importantes
- **Historial**: Crear un registro completo del trabajo realizado
- **Mejora continua**: Identificar patrones y problemas recurrentes

**Tipos de observaciones a registrar:**

**1. Observaciones técnicas:**
- **Materiales utilizados**: Qué materiales se usaron en la reparación
- **Técnicas aplicadas**: Cómo se resolvió el problema
- **Tiempo empleado**: Cuánto tiempo tomó la reparación
- **Dificultades encontradas**: Problemas adicionales encontrados

**2. Observaciones de seguridad:**
- **Condiciones peligrosas**: Situaciones de riesgo encontradas
- **Medidas de seguridad**: Precauciones tomadas
- **Equipos de protección**: EPP utilizado
- **Accidentes o incidentes**: Cualquier situación no planificada

**3. Observaciones de calidad:**
- **Estado del trabajo**: Calidad de la reparación realizada
- **Verificación**: Confirmación de que el problema se resolvió
- **Recomendaciones**: Sugerencias para futuras reparaciones
- **Seguimiento necesario**: Si requiere revisión posterior

**Ejemplo de observaciones:**
```
OBSERVACIONES - Reclamo #12345
Fecha: 15/03/2024
Operario: Carlos Mendoza

Trabajo realizado:
- Reemplazada luminaria LED 50W quemada
- Verificadas conexiones eléctricas
- Limpiada área de trabajo

Materiales utilizados:
- 1 Luminaria LED 50W
- 1 Portalámparas E27
- Cable de conexión 2.5mm

Tiempo empleado: 25 minutos

Observaciones adicionales:
- La luminaria anterior tenía 3 años de uso
- Las conexiones estaban en buen estado
- Se recomienda revisar otras luminarias de la zona

Estado: Completado exitosamente
```

**Ver historial del reclamo:**

**Paso 1**: En el modal de acciones, hacer clic en la pestaña **"Historial"**

**Paso 2**: Revisar todos los cambios de estado anteriores con:
- **Estado anterior** y **estado actual**: Transición de estados
- **Usuario**: Quién realizó el cambio
- **Fecha de cambio**: Cuándo se realizó el cambio

![Historial de reclamo](imagenes/operario_historial.png)

**Información del historial:**
| Columna | Descripción | Uso |
|---------|-------------|-----|
| **Estado Anterior** | Estado previo al cambio | Seguimiento del progreso |
| **Estado Actual** | Nuevo estado asignado | Estado actual del reclamo |
| **Usuario** | Quién hizo el cambio | Responsabilidad y seguimiento |
| **Fecha de Cambio** | Cuándo se realizó | Cronología del trabajo |

**Beneficios del historial:**
- **Trazabilidad**: Seguir el progreso completo del reclamo
- **Responsabilidad**: Saber quién hizo cada cambio
- **Análisis**: Identificar patrones y tiempos de resolución
- **Comunicación**: Coordinación entre diferentes operarios

**Recomendaciones para el registro de estados:**
- **Actualizar frecuentemente**: Cambiar el estado tan pronto como sea apropiado
- **Ser preciso**: Usar el estado correcto para cada situación
- **Documentar bien**: Agregar observaciones detalladas cuando sea necesario
- **Comunicar problemas**: Si encuentras problemas adicionales, documentarlos
- **Verificar antes de completar**: Asegurar que el trabajo esté realmente terminado

**Solución de problemas comunes:**
- **Estado no se guarda**: Verificar conexión a internet y refrescar la página
- **No aparece el historial**: El historial puede tardar unos segundos en cargar
- **Error al cambiar estado**: Verificar que hayas seleccionado un estado válido
- **Observaciones no se guardan**: Asegurar que el texto no sea demasiado largo

#### 📝 Registro de Observaciones y Materiales

**Descripción general:**
El registro de observaciones es fundamental para documentar el trabajo realizado. Aunque el sistema no tiene actualmente funcionalidad de subida de fotos, las observaciones de texto permiten documentar todos los aspectos importantes del trabajo realizado.

**Tipos de información a registrar en observaciones:**

**1. Trabajo realizado:**
- Descripción detallada de la reparación ejecutada
- Técnicas y métodos utilizados
- Tiempo empleado en la reparación
- Dificultades encontradas durante el trabajo

**2. Materiales utilizados:**
- Lista de materiales empleados
- Cantidades utilizadas
- Códigos o referencias de materiales
- Materiales adicionales necesarios

**3. Observaciones técnicas:**
- Estado de las instalaciones encontradas
- Recomendaciones para futuras reparaciones
- Problemas adicionales detectados
- Medidas de seguridad aplicadas

**4. Calidad del trabajo:**
- Verificación de funcionamiento
- Estado final del reclamo
- Necesidad de seguimiento posterior

> **📝 Nota**: Las observaciones se registran en el campo de texto al cambiar el estado del reclamo. Es importante ser detallado y claro en las observaciones para facilitar el seguimiento y la auditoría.

#### 🗺️ Visualización de Hoja de Ruta y Ruta Optimizada

**Descripción general:**
El mapa es una herramienta esencial para los operarios, permitiendo visualizar la ubicación de los reclamos, planificar rutas eficientes y navegar hasta los lugares de trabajo. Está optimizado para uso en dispositivos móviles durante el trabajo en campo.

**Acceder al mapa de reclamos:**

**Paso 1**: Hacer clic en **"Mapa Google"** o **"Mapa Mapbox"**
- **Mapa Google**: Usa Google Maps como base cartográfica (recomendado para navegación)
- **Mapa Mapbox**: Alternativa que puede tener mejor rendimiento en algunas áreas
- Ambos mapas muestran la misma información de reclamos

**Paso 2**: El mapa se cargará mostrando todos los reclamos con marcadores de colores
- La carga puede tomar unos segundos dependiendo de la conexión
- El mapa se centrará automáticamente en el área de trabajo

![Mapa para operarios](imagenes/operario_mapa.png)

**Interpretación de marcadores para operarios:**

| Color | Estado | Significado para operario | Acción recomendada |
|-------|--------|---------------------------|-------------------|
| ⚫ **Gris/Negro** | Recibido | Reclamo nuevo, sin asignar | Informar al supervisor |
| 🔴 **Rojo** | Asignado | Asignado a tu cuadrilla a través de una hoja de ruta | Preparar para ir al lugar |
| 🟡 **Amarillo/Dorado** | En ejecución | Trabajo en progreso | Continuar o verificar |
| 🟢 **Verde** | Completado | Trabajo finalizado, pendiente de cierre | Verificar calidad y esperar cierre formal |
| ⚫ **Gris Oscuro** | Cerrado | Reclamo cerrado formalmente | Solo consulta |

**Funcionalidades del mapa para operarios:**

**1. Navegación básica:**
- **Zoom**: Usar la rueda del mouse o pellizcar en dispositivos móviles
- **Desplazamiento**: Hacer clic y arrastrar para mover el mapa
- **Centrar**: Hacer doble clic para centrar la vista en un punto

**2. Interacción con marcadores:**
- **Clic en marcador**: Ver información básica del reclamo
- **Doble clic**: Abrir detalles completos del reclamo
- **Información rápida**: Ver dirección y motivo sin abrir detalles

**3. Tabla lateral de reclamos:**
- **Lista de reclamos**: Todos los reclamos visibles en el mapa
- **Información resumida**: ID, dirección, número de domicilio
- **Acciones rápidas**: Botones para ver detalles o cambiar estado

**Planificación de rutas de trabajo:**

**Estrategias de optimización:**

**1. Agrupación por proximidad:**
- **Identificar zonas**: Agrupar reclamos por barrios o calles cercanas
- **Planificar secuencia**: Ordenar reclamos por proximidad geográfica
- **Minimizar desplazamientos**: Reducir tiempo de viaje entre trabajos

**2. Consideraciones de tráfico:**
- **Horarios pico**: Evitar zonas congestionadas en horas de mayor tráfico
- **Calles principales**: Priorizar rutas por avenidas principales
- **Restricciones vehiculares**: Considerar calles de un solo sentido o restricciones

**3. Priorización por urgencia:**
- **Alta prioridad**: Atender primero los reclamos más urgentes
- **Seguridad**: Priorizar situaciones de riesgo (cables caídos, postes inclinados)
- **Impacto ciudadano**: Considerar el impacto en la comunidad

**Ejemplo práctico de planificación de ruta:**
Para una jornada de trabajo:
1. Abrir el mapa de reclamos
2. Filtrar por estado "Asignado" (marcadores rojos)
3. Identificar reclamos en la zona norte de la ciudad
4. Ordenar por proximidad:
   - Reclamo #12345 - Av. Principal 123 (luminaria quemada)
   - Reclamo #12346 - Calle Secundaria 456 (poste inclinado)
   - Reclamo #12347 - Plaza Central (semáforo desincronizado)
5. Planificar ruta: Av. Principal → Calle Secundaria → Plaza Central
6. Verificar materiales necesarios para cada trabajo
7. Salir con la ruta optimizada

**Uso en dispositivos móviles:**

**Optimizaciones para campo:**
- **Pantalla táctil**: Interfaz optimizada para uso con dedos
- **Botones grandes**: Fáciles de presionar con guantes
- **Carga rápida**: Optimizado para conexiones lentas
- **Modo offline**: Funcionalidad básica sin conexión (en desarrollo)

**Navegación GPS:**
- **Integración con GPS**: Usar el mapa para navegación
- **Direcciones precisas**: Obtener direcciones exactas para cada reclamo
- **Tiempo estimado**: Ver tiempo estimado de viaje
- **Tráfico en tiempo real**: Información de tráfico actualizada

**Recomendaciones de uso:**
- **Cargar antes de salir**: Abrir el mapa y cargar los reclamos antes de salir
- **Verificar conexión**: Asegurar buena señal antes de comenzar
- **Tener respaldo**: Llevar lista impresa como respaldo
- **Actualizar estados**: Cambiar estados en tiempo real desde el lugar

**Solución de problemas comunes:**
- **Mapa no carga**: Verificar conexión a internet y refrescar la página
- **Marcadores no aparecen**: Verificar que haya reclamos asignados
- **GPS no funciona**: Verificar permisos de ubicación en el dispositivo
- **Carga lenta**: Reducir el zoom o usar filtros para mostrar menos reclamos
- **Información incorrecta**: Contactar al supervisor para corregir datos

---

## 3. Funcionalidades Comunes / Tips

### 🔍 Búsquedas y Filtros Globales

**Aplicar filtros en cualquier módulo:**

1. **Buscar por texto**: Usar la barra de búsqueda cuando esté disponible
2. **Filtros por fecha**: Seleccionar rangos de fechas específicos
3. **Filtros por estado**: Filtrar por estado actual del elemento
4. **Combinar filtros**: Usar múltiples filtros simultáneamente para resultados más precisos

### 🗺️ Navegación entre Mapa y Lista de Reclamos

**Alternar entre vistas:**

1. **Vista de mapa**: Ideal para visualización geográfica y planificación de rutas
2. **Vista de lista**: Mejor para análisis detallado y gestión masiva
3. **Vista de tarjetas**: Optimizada para operarios en dispositivos móviles

### ⚡ Atajos y Buenas Prácticas

**Navegación eficiente:**
- **Menú principal**: Siempre visible para acceso rápido a todas las funciones
- **Breadcrumbs**: Seguir la ruta de navegación para no perderse
- **Botones de acción**: Usar los botones destacados para acciones principales

**Gestión de datos:**
- **Guardar frecuentemente**: Los cambios se guardan automáticamente en la mayoría de casos
- **Verificar antes de eliminar**: Algunas acciones son irreversibles
- **Usar filtros**: Reducir la cantidad de datos mostrados para mejor rendimiento

**Trabajo en equipo:**
- **Comunicar cambios**: Informar a otros usuarios sobre cambios importantes
- **Actualizar estados**: Mantener el estado de los reclamos actualizado
- **Documentar observaciones**: Registrar información relevante en los campos de descripción

---

## 4. Anexos

### 🎨 Leyenda de Íconos

| Ícono | Significado | Uso |
|-------|-------------|-----|
| 👤 | Usuario/Perfil | Gestión de usuarios |
| 🗺️ | Mapa | Visualización geográfica |
| 📋 | Lista/Tabla | Vista de datos tabulares |
| ➕ | Agregar | Crear nuevo elemento |
| ✏️ | Editar | Modificar elemento existente |
| 🗑️ | Eliminar | Borrar elemento |
| 🔍 | Buscar | Funciones de búsqueda |
| 🔧 | Configurar | Ajustes y configuración |
| 📊 | Reportes | Informes y estadísticas |
| ⚠️ | Advertencia | Información importante |
| ✅ | Confirmar | Aprobar o confirmar acción |
| ❌ | Cancelar | Cancelar operación |

### 📚 Glosario de Términos

**Administrador**: Usuario con permisos completos para configurar el sistema y gestionar otros usuarios.

**Cierre de Reclamo**: Proceso formal de finalización de un reclamo completado. Al cerrar, el reclamo se sincroniza con el sistema 103 y queda bloqueado para edición.

**Cuadrilla**: Grupo de operarios asignados para trabajar en conjunto en las tareas de mantenimiento. Una cuadrilla puede tener hasta 4 operarios.

**Estado del Reclamo**: Situación actual del reclamo en el proceso de resolución:
- **Recibido**: Reclamo ingresado al sistema, sin asignar
- **Asignado**: Asignado a una cuadrilla a través de una hoja de ruta
- **En ejecución**: Trabajo en progreso
- **Completado**: Trabajo finalizado, pendiente de cierre formal
- **En plan**: Programado para futura ejecución
- **Error de datos**: Requiere corrección de información
- **Cerrado**: Reclamo cerrado formalmente, bloqueado para edición

**Hoja de Ruta**: Conjunto de reclamos organizados en un orden optimizado para ser atendidos por una cuadrilla. Puede generarse automáticamente o editarse manualmente.

**Legajo**: Número de identificación único de cada operario en el sistema. Se usa para el login de operarios y supervisores.

**Operario**: Personal técnico que ejecuta las reparaciones en campo. Tiene acceso limitado principalmente a tareas asignadas y cambio de estados.

**Prioridad**: Nivel de urgencia del reclamo:
- **Alta**: Reclamos urgentes que requieren atención inmediata
- **Baja**: Reclamos de mantenimiento rutinario

**Reclamo**: Solicitud ciudadana relacionada con problemas de alumbrado público. Puede ser recibida desde el sistema 103 o ingresada manualmente.

**Sistema 103**: Sistema externo de la municipalidad para gestión de reclamos ciudadanos. El sistema se sincroniza con este para recibir reclamos y enviar cierres.

**Supervisor**: Usuario encargado de coordinar cuadrillas, gestionar la asignación de tareas, crear hojas de ruta y cerrar reclamos.

**Token 103 / Credenciales Basic Auth**: Username y password que permiten al sistema autenticarse con el sistema externo 103 de la municipalidad. El sistema genera automáticamente un token Base64 a partir de estas credenciales para las peticiones HTTP. Se configura en el módulo "Token 103" del administrador.

### ❓ FAQ - Preguntas Frecuentes

**P: ¿Cómo puedo recuperar mi contraseña?**
R: Contacta al administrador del sistema para que te asigne una nueva contraseña.

**P: ¿Puedo cambiar el estado de un reclamo desde cualquier lugar?**
R: Sí, puedes cambiar el estado desde la vista de tareas, el mapa o la lista de reclamos. Sin embargo, los reclamos cerrados no pueden cambiar de estado.

**P: ¿Qué hago si no veo un reclamo en el mapa?**
R: Verifica que el reclamo tenga una dirección válida y que los filtros no estén ocultando el reclamo. También verifica que el reclamo no esté completado o cerrado si estás buscando reclamos activos.

**P: ¿Cómo sé qué cuadrilla está asignada a un reclamo?**
R: Puedes ver esta información en la hoja de ruta asignada. Si un reclamo está en una ruta asignada, la cuadrilla aparece en la información de la ruta.

**P: ¿Puedo trabajar desde mi teléfono móvil?**
R: Sí, el sistema es responsive y funciona en dispositivos móviles, especialmente la vista de tareas y el mapa.

**P: ¿Qué hago si el sistema está lento?**
R: Usa los filtros para reducir la cantidad de datos mostrados y mejora el rendimiento. También puedes cerrar pestañas del navegador que no estés usando.

**P: ¿Cómo funciona la generación automática de hojas de ruta?**
R: El sistema utiliza un algoritmo que prioriza reclamos de alta prioridad y optimiza el orden por proximidad geográfica. Puedes generar una vista previa antes de crear la ruta y editarla manualmente si es necesario.

**P: ¿Puedo editar una hoja de ruta después de crearla?**
R: Solo puedes editar una hoja de ruta si no está asignada a una cuadrilla. Una vez asignada, debes desasignarla primero para poder editarla.

**P: ¿Qué pasa si cierro un reclamo por error?**
R: Los reclamos cerrados no pueden ser modificados. Si necesitas corregir un cierre, contacta al administrador del sistema.

**P: ¿Cómo sé si un reclamo se sincronizó correctamente con el sistema 103?**
R: Al cerrar reclamos, el sistema muestra un mensaje indicando cuántos se enviaron exitosamente al sistema 103. Si hay errores, se mostrarán en el mensaje de resultado.

**P: ¿Puedo asignar una hoja de ruta a más de una cuadrilla?**
R: No, cada hoja de ruta solo puede estar asignada a una cuadrilla a la vez. Si necesitas reasignarla, primero debes desasignarla de la cuadrilla actual.

**P: ¿Qué reclamos puedo incluir en una hoja de ruta?**
R: Solo puedes incluir reclamos que no estén completados y que no estén ya asignados a otra hoja de ruta. El sistema valida esto automáticamente.

### 🚨 Problemas Frecuentes y Soluciones

**Problema**: No puedo iniciar sesión
**Solución**: 
1. Verificar que estés usando el tipo de login correcto (email para administradores, legajo para operarios)
2. Confirmar que las credenciales sean correctas
3. Contactar al administrador si el problema persiste

**Problema**: El mapa no carga los reclamos
**Solución**:
1. Verificar la conexión a internet
2. Refrescar la página
3. Cambiar entre Google Maps y Mapbox usando el botón correspondiente

**Problema**: Los filtros no funcionan
**Solución**:
1. Hacer clic en "Limpiar" para resetear los filtros
2. Aplicar los filtros uno por uno
3. Verificar que los datos cumplan los criterios de filtrado

**Problema**: No puedo ver todos los reclamos
**Solución**:
1. Verificar que no haya filtros activos
2. Comprobar que tengas permisos para ver todos los reclamos
3. Contactar al supervisor si el problema persiste

---

## 📞 Soporte y Contacto

Para soporte técnico o consultas sobre el sistema, contacta a:

- **Administrador del Sistema**: [email del administrador]
- **Soporte Técnico**: [email de soporte]
- **Teléfono**: [número de teléfono]

---

*Manual de Usuario v2.0 - Sistema de Gestión de Alumbrado Público*
*Última actualización: Diciembre 2024*
