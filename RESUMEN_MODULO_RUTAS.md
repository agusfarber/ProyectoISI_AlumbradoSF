# Resumen del Módulo de Hojas de Ruta

## Descripción General
Este módulo implementa la funcionalidad completa de generación, visualización y edición de hojas de ruta optimizadas para la gestión de reclamos de alumbrado público. Utiliza algoritmos de optimización basados en prioridad y proximidad geográfica.

---

## 1. RutaModel.php (Backend - Modelo)

**Ubicación:** `app/Models/RutaModel.php`  
**Propósito:** Modelo de datos para la tabla `ruta` en la base de datos.

### Estructura de la Tabla
- **id**: Identificador único de la ruta
- **nombre**: Nombre descriptivo de la hoja de ruta
- **cantidadReclamos**: Cantidad total de reclamos incluidos en la ruta
- **asignada**: Estado de asignación (0 = No Asignada, 1 = Asignada a cuadrilla)
- **cuadrilla_id**: ID de la cuadrilla asignada (puede ser NULL)
- **tiempoEstimado**: Tiempo estimado de completar la ruta (formato TIME)
- **fecha**: Fecha y hora de creación (formato DATETIME, zona horaria Argentina)
- **color**: Color hexadecimal para identificación visual en mapas

### Características
- Hereda de CodeIgniter Model
- Tabla: `ruta`
- Permite operaciones CRUD estándar

---

## 2. Ruta_reclamoModel.php (Backend - Modelo)

**Ubicación:** `app/Models/Ruta_reclamoModel.php`  
**Propósito:** Modelo de relación entre rutas y reclamos (tabla intermedia).

### Estructura de la Tabla
- **id**: Identificador único del registro
- **ruta_id**: ID de la ruta a la que pertenece el reclamo
- **reclamo_id**: ID del reclamo incluido en la ruta
- **posicion**: Orden secuencial del reclamo en la ruta (1, 2, 3, ...)

### Características
- Tabla intermedia para relación muchos-a-muchos
- Tabla: `ruta_reclamo`
- La posición determina el orden de visita en el recorrido

---

## 3. Rutas.php (Backend - Controlador API)

**Ubicación:** `app/Controllers/Api/Rutas.php`  
**Propósito:** Controlador RESTful que maneja toda la lógica de negocio para rutas.

### Métodos Principales

#### `index()`
- **Función:** Obtiene todas las rutas con información de cuadrilla asignada
- **Retorna:** Lista completa de rutas con JOIN a tabla cuadrilla
- **Uso:** Cargar tabla de rutas en frontend

#### `show($id)`
- **Función:** Obtiene una ruta específica por ID
- **Parámetros:** ID de la ruta
- **Retorna:** Datos completos de la ruta con cuadrilla

#### `create()`
- **Función:** Crea una nueva ruta (método básico)
- **Valida:** cantidadReclamos obligatorio
- **Establece:** asignada = 0 (no asignada por defecto)

#### `update($id)`
- **Función:** Actualiza datos de una ruta existente
- **Uso:** Modificar nombre, color, estado de asignación, etc.

#### `delete($id)`
- **Función:** Elimina una ruta y sus reclamos asociados
- **Cascada:** Elimina registros en ruta_reclamo

#### `generarRuta()`
**Función Principal:** Genera hoja de ruta optimizada
**Recibe:**
- nombre, color, cantidadReclamos
- reclamosManuales (opcional): IDs de reclamos seleccionados manualmente
- primerReclamoManual (opcional): ID del primer reclamo
- modoManual (boolean): Indica si es ruta editada manualmente

**Proceso:**
1. Obtiene reclamos disponibles (no completados, no en otras rutas)
2. Si es modo manual: Usa orden exacto del usuario sin optimizar
3. Si es modo automático:
   - Filtra reclamos disponibles
   - Obtiene coordenadas (personalizadas o geocodificadas)
   - Selecciona reclamos por prioridad
   - Optimiza orden de recorrido
4. Crea ruta en BD con estado asignada = 0
5. Inserta reclamos en ruta_reclamo con su posición

**Retorna:** Ruta creada con lista de reclamos ordenados

#### `vistaPreviaRuta()`
- **Función:** Genera vista previa usando el mismo algoritmo sin guardar en BD
- **Retorna:** Ruta optimizada, tiempo estimado, distancia total
- **Uso:** Mostrar preview antes de confirmar creación

#### `getReclamosRuta($id)`
- **Función:** Obtiene reclamos de una ruta específica con sus coordenadas
- **Retorna:** Array de reclamos ordenados por posición con datos completos

### Métodos Privados (Algoritmos)

#### `filtrarReclamosDisponibles($reclamos)`
- **Función:** Filtra reclamos válidos para nuevas rutas
- **Criterios:** 
  - No completados (estado != "Completado")
  - No en otras rutas (asignadas o no asignadas)

#### `obtenerCoordenadasReclamos($reclamos, $direccionModel)`
- **Función:** Obtiene coordenadas para lista de reclamos
- **Lógica:** Llama a obtenerCoordenadasReclamo para cada uno

#### `obtenerCoordenadasReclamo($reclamo, $direccionModel)`
- **Función:** Obtiene coordenadas de un reclamo individual
- **Prioridad:** 
  1. Busca en DireccionModel (direcciones personalizadas)
  2. Si no existe, retorna null (geocodificación se hace en frontend)

#### `seleccionarReclamosParaRuta($reclamos, $cantidad, $reclamosManuales, $primerReclamoManual)`
- **Función:** Selecciona los N reclamos para la ruta
- **Lógica:**
  1. Agrega reclamos manuales primero
  2. Separa por prioridad: Alta y Baja
  3. Completa con algoritmo de proximidad

#### `seleccionarPorPrioridad($reclamosSeleccionados, $reclamosAlta, $reclamosBaja, $cantidadNecesaria)`
- **Función:** Implementa lógica de priorización
- **Casos:**
  - Si hay >= N reclamos Alta: Selecciona N más cercanos de Alta
  - Si hay < N reclamos Alta: Incluye todos Alta + Baja cercanos
  - Si no hay Alta: Solo Baja

#### `seleccionarReclamosCercanos($reclamos, $cantidad)`
- **Función:** Algoritmo del vecino más cercano desde tanque de agua
- **Proceso:**
  1. Inicia en reclamo más cercano al tanque (-31.426516, -62.110954)
  2. Itera seleccionando el más cercano al actual
  3. Repite hasta tener N reclamos

#### `seleccionarReclamosCercanosAGrupo($reclamos, $grupoBase, $cantidad)`
- **Función:** Selecciona reclamos cercanos a un grupo ya seleccionado
- **Uso:** Completar con Baja cuando ya hay algunos Alta

#### `optimizarOrdenRuta($reclamos)`
- **Función:** Ordena reclamos para recorrido óptimo
- **Algoritmo:** Vecino más cercano iniciando desde tanque de agua
- **Retorna:** Array de reclamos en orden óptimo de visita

#### `calcularDistancia($lat1, $lng1, $lat2, $lng2)`
- **Función:** Calcula distancia entre dos coordenadas
- **Fórmula:** Haversine (considera curvatura terrestre)
- **Retorna:** Distancia en kilómetros

#### `calcularTiempoEstimado($reclamos)`
- **Función:** Estima tiempo total de la ruta
- **Cálculo:**
  - 15 minutos por reclamo (trabajo en sitio)
  - Tiempo de desplazamiento (30 km/h promedio urbano)
- **Retorna:** String formato HH:MM:SS

#### `calcularDistanciaTotal($reclamos)`
- **Función:** Suma distancias entre todos los puntos
- **Retorna:** Distancia total en km (redondeado 2 decimales)

#### `encontrarReclamoMasCercano($reclamos, $punto)`
- **Función:** Encuentra el reclamo más cercano a un punto
- **Uso:** Utilizado en algoritmos de optimización

#### `calcularDistanciaMinimaAGrupo($reclamo, $grupoReclamos)`
- **Función:** Calcula distancia mínima de un reclamo a un grupo
- **Retorna:** Menor distancia encontrada al grupo

---

## 4. rutas.php (Frontend - Vista)

**Ubicación:** `app/Views/pages/rutas.php`  
**Propósito:** Plantilla HTML de la página de gestión de rutas (Vue.js template).

### Estructura Principal

#### Sección Superior
- Título: "Gestión de Hojas de Ruta"
- **Botón "Nueva Hoja de Ruta"**: Abre modal de creación
- **Botón "Visualizar Rutas"**: Abre modal con todas las rutas en mapa

#### Tabla de Rutas (`#tabla_rutas`)
**Columnas:**
- Nombre (con color identificador)
- Cantidad de Reclamos
- Tiempo Estimado
- Asignación (Asignada/No Asignada)
- Fecha de Creación
- Acciones (Ver, Eliminar)

### Modales

#### Modal "Crear Hoja de Ruta" (`#modalCrearRuta`)
**Paso 1 - Configuración** (cuando no hay vista previa):
- Input: Nombre de la ruta
- Input: Color selector
- Input: Cantidad de reclamos (validado contra disponibles)
- Botón: "Generar Vista Previa"

**Paso 2 - Vista Previa** (cuando hay vista previa activa):
- **Panel Izquierdo (col-md-4):**
  - Lista de reclamos en la ruta
  - Cada reclamo muestra: posición, ID, motivo
  - En modo edición: botones arriba/abajo/eliminar por reclamo
- **Panel Derecho (col-md-8):**
  - Mapa interactivo (Google Maps o Mapbox)
  - Botón para alternar proveedor de mapa
- **Alert de Modo Edición:** Se muestra cuando modo edición está activo

**Footer:**
- "Cancelar": Cierra y resetea
- "Generar Vista Previa": Solo visible en paso 1
- "Editar Hoja de Ruta": Activa modo edición
- "Cancelar Edición": Solo en modo edición, restaura original
- "Crear Ruta Automática/Personalizada": Confirma y guarda

#### Modal "Ver Hoja de Ruta" (`#modalVerRuta`)
**Estructura:**
- Header: Nombre de ruta + badge de asignación + popover con info
- **Panel Izquierdo:** Lista de reclamos (click centra en mapa)
- **Panel Derecho:** Mapa con ruta visualizada
- Footer: Botón "Cerrar"

#### Modal "Visualizar Todas las Rutas" (`#modalVisualizarRutas`)
**Estructura:**
- Header: Título + contador de rutas
- **Panel Izquierdo:** Lista de todas las rutas con badges de estado
- **Panel Derecho:** Mapa mostrando todas las rutas simultáneamente
- Footer: Botón "Cerrar"

### Directivas Vue Principales
- `v-if/v-else`: Mostrar/ocultar pasos del modal
- `v-for`: Iterar sobre listas de rutas y reclamos
- `@click`: Eventos de botones
- `:disabled`: Habilitar/deshabilitar botones según estado
- `:class`: Clases dinámicas para badges y estados

---

## 5. rutas.js (Frontend - Lógica Vue.js)

**Ubicación:** `public/static/js/rutas.js`  
**Propósito:** Aplicación Vue.js que maneja toda la lógica del frontend de rutas.

### Data Properties

#### Datos Principales
- **rutas**: Array de todas las rutas del sistema
- **reclamos**: Array de todos los reclamos disponibles
- **tabla**: Instancia de DataTable
- **reclamosDisponibles**: Contador de reclamos no asignados

#### Configuración de Nueva Ruta
- **nuevaRuta**: Objeto con {nombre, color, cantidadReclamos, seleccionManual, primerReclamoManual}
- **reclamosSeleccionados**: Array de reclamos elegidos manualmente
- **primerReclamoSeleccionado**: Reclamo inicial manual

#### Modos de Operación
- **modoEdicion**: Boolean, indica si está editando ruta
- **rutaOriginal**: Backup de ruta antes de editar

#### Vista Previa
- **vistaPrevia**: Objeto con:
  - activa: Boolean
  - reclamos: Array
  - rutaOptimizada: Array ordenado
  - tiempoEstimado: Minutos
  - distanciaTotal: Km
  - marcadoresRuta: Array de marcadores Google Maps
  - marcadoresOtros: Reclamos no en ruta
  - marcadoresRutasActivas: Rutas existentes (gris)
  - polylineRuta: Línea de ruta
  - directionsRenderer: Renderer de Google Directions

#### Visualización
- **rutaVisualizando**: Ruta individual en modal
- **reclamosRutaVisualizando**: Reclamos de esa ruta
- **mapaVisualizacion**: Instancia de mapa individual
- **rutasActivas**: Todas las rutas para modal múltiple
- **mapaRutasActivas**: Instancia de mapa múltiple

#### Optimización
- **cacheCoordenadasReclamos**: Cache {reclamoId: coordenadas}
- **direccionesPersonalizadas**: Array pre-cargado de direcciones BD

#### Proveedores de Mapa
- **proveedorMapaVistaPrevia**: 'google' o 'mapbox'
- **proveedorMapaVisualizacion**: Para modal individual
- **proveedorMapaRutasActivas**: Para modal múltiple
- **mapaMapbox, mapaVisualizacionMapbox, mapaRutasActivasMapbox**: Instancias Mapbox
- **mapboxToken**: API key de Mapbox

### Computed Properties

#### `puedeGenerarRuta()`
- **Retorna:** Boolean
- **Condiciones:** Nombre válido, cantidad > 0, suficientes disponibles, vista previa activa

#### `puedeVerVistaPrevia()`
- **Retorna:** Boolean
- **Condiciones:** Configuración válida y vista previa no activa

### Métodos Principales

#### Inicialización y Carga de Datos

**`obtenerRutas()`**
- Llama a API: GET `/api/rutas`
- Actualiza data.rutas
- Inicializa/refresca DataTable

**`obtenerReclamos()`**
- Llama a API: GET `/api/reclamos`
- Filtra no completados para contar disponibles
- Pre-carga direcciones personalizadas

**`preCargarDireccionesPersonalizadas()`**
- Llama a API: GET `/api/direcciones`
- Almacena en data.direccionesPersonalizadas
- Optimiza búsquedas posteriores

**`inicializarTabla()`**
- Configura DataTables con columnas específicas
- Renderiza color, badges de asignación
- Configura eventos de botones (ver, eliminar)

#### Modal de Creación

**`abrirModalCrearRuta()`**
- Resetea datos del formulario
- Muestra modal de Bootstrap

**`resetearModal()`**
- Limpia todos los campos
- Resetea modos y arrays
- Limpia vista previa

**`mostrarVistaPrevia()`**
- Valida configuración
- Llama a API: POST `/api/rutas/vista-previa`
- Recibe ruta optimizada del backend
- Activa vista previa
- Inicializa mapa tras 300ms
- Muestra reclamos y ruta en mapa

#### Gestión de Modo Edición

**`activarModoEdicion()`**
- Activa flag modoEdicion
- Guarda copia de rutaOriginal
- Muestra mensaje de ayuda

**`cancelarEdicion()`**
- Restaura rutaOriginal
- Desactiva modoEdicion
- Actualiza mapa

**`moverReclamoArriba(index)`**
- Intercambia posición con anterior
- Fuerza actualización de Vue
- Actualiza mapa

**`moverReclamoAbajo(index)`**
- Intercambia posición con siguiente
- Actualiza visualización

**`eliminarReclamoDeRuta(index)`**
- Elimina del array rutaOptimizada
- Muestra mensaje
- Actualiza mapa

**`agregarReclamoARuta(reclamo)`**
- Solo en modo edición
- Valida: no duplicado, no en otra ruta, no completado
- Agrega al final de rutaOptimizada
- Actualiza mapa

**`verificarReclamoEnOtraRuta(reclamoId)`**
- Itera todas las rutas
- Verifica si reclamo ya está asignado
- Retorna boolean

#### Mapas y Visualización

**`inicializarMapa()`**
- Crea instancia de Google Maps
- Centro: San Francisco, Córdoba (-31.427, -62.082)
- Inicializa geocoder
- Fallback automático a Mapbox si falla

**`mostrarVistaPreviaEnMapa()`**
- Limpia marcadores previos
- **Paso 1:** Muestra rutas existentes en gris
- **Paso 2:** Agrega marcadores puntiagudos (reclamos NO en ruta)
- **Paso 3:** Agrega marcadores numerados circulares (ruta actual)
- **Paso 4:** Traza ruta por calles con Directions API

**`mostrarRutasActivasEnVistaPrevia()`**
- Obtiene todas las rutas del sistema
- Para cada ruta:
  - Crea marcadores grises discretos
  - Traza su ruta por calles
  - Configura infowindows con datos

**`actualizarMapaVistaPrevia()`**
- Limpia completamente la vista previa
- Vuelve a mostrar todo actualizado
- Usado tras ediciones

**`limpiarVistaPreviaCompleto()`**
- Elimina marcadores de ruta
- Elimina marcadores de otros reclamos
- Elimina marcadores de rutas existentes
- Limpia polyline y directions renderer

**`agregarMarcadoresVisualizacion()`**
- Para modal de ver ruta individual
- Crea marcadores numerados
- Configura infowindows con detalles completos

**`trazarRutaConDirections()`**
- Usa Google Directions Service
- Traza ruta por calles reales (no línea recta)
- Maneja waypoints para rutas de 3+ puntos
- Fallback a Mapbox si falla

**`trazarRutaSimpleVistaPrevia(origin, destination)`**
- Para rutas de 2 puntos
- Usa Directions API directamente

**`trazarRutaComplejaVistaPrevia(coordenadas)`**
- Para rutas de 3+ puntos
- Define origin, destination y waypoints
- optimizeWaypoints = false (mantiene orden)

**`trazarRutaRectaVistaPrevia()`**
- Fallback si Directions falla
- Dibuja línea recta con Polyline

#### Coordenadas y Geocodificación

**`obtenerCoordenadasReclamo(reclamo)`**
- Verifica cache primero
- Busca en direccionesPersonalizadas
- Si no existe, geocodifica con API
- Guarda en cache el resultado

**`geocodificarDireccion(reclamo)`**
- Intenta Google Geocoding API
- Fallback a Mapbox Geocoding API
- Retorna {lat, lng, esPersonalizada, fuente}

#### Marcadores y Estilos

**`crearIconoNumerado(numero, colorEstado, colorPrioridad)`**
- Si prioridad Alta: marcador con animación de pulso
- Si prioridad Baja: marcador simple
- SVG generado dinámicamente con número

**`crearContenidoInfoWindow(reclamo)`**
- HTML para info window de Google Maps
- Muestra: ID, motivo, estado, prioridad, dirección, fecha, ciudadano

**`getColorEstado(estado)`**
- Mapea estados a colores:
  - Recibido: gris
  - Asignado: rojo
  - En ejecución: dorado
  - Completado: verde
  - Error de datos: gris

**`getColorPrioridad(prioridad)`**
- Prioridad Alta: #DC3545 (rojo intenso)
- Otras: null (sin borde especial)

#### CRUD de Rutas

**`crearRutaAutomatica()`**
- Valida que haya vista previa
- Prepara datos:
  - Si modoEdicion: envía reclamos en orden manual (modoManual=true)
  - Si automático: envía configuración normal
- Llama a API: POST `/api/rutas/generar`
- Cierra modal y refresca tabla

**`verRuta(id)`**
- Llama a API: GET `/api/rutas/{id}`
- Llama a API: GET `/api/rutas/{id}/reclamos`
- Abre modal de visualización
- Inicializa mapa tras 300ms

**`eliminarRuta(id)`**
- Muestra confirmación personalizada
- Llama a API: DELETE `/api/rutas/{id}`
- Refresca tabla

#### Visualización Múltiple

**`abrirModalVisualizarRutas()`**
- Asigna todas las rutas a rutasActivas
- Abre modal
- Inicializa mapa múltiple

**`inicializarMapaRutasActivas()`**
- Crea mapa Google Maps
- Llama a mostrarTodasLasRutasActivas()
- Fallback a Mapbox

**`mostrarTodasLasRutasActivas()`**
- Itera todas las rutas
- Para cada una:
  - Obtiene sus reclamos
  - Crea marcadores con su color
  - Traza ruta por calles

**`centrarEnRutaActiva(ruta)`**
- Busca primer marcador de la ruta
- Centra mapa en ese punto
- Aplica animación de rebote

**`cerrarVisualizacionRutas()`**
- Limpia marcadores y renderers
- Cierra modal

#### Alternancia de Mapas

**`alternarProveedorVistaPrevia()`**
- Cambia entre 'google' y 'mapbox'
- Inicializa el nuevo proveedor
- Actualiza vista previa

**`alternarProveedorVisualizacion()`**
- Para modal de ver ruta individual

**`alternarProveedorRutasActivas()`**
- Para modal de múltiples rutas

#### Mapbox (Alternativo)

**`inicializarMapaMapbox()`**
- Crea instancia de Mapbox GL JS
- Centro: San Francisco

**`mostrarVistaPreviaEnMapaMapbox()`**
- Limpia capas y marcadores
- Crea marcadores HTML personalizados
- Llama a trazarRutaMapbox()

**`trazarRutaMapbox(reclamos, mapa, color)`**
- Usa Mapbox Directions API
- Construye URL con coordenadas
- Agrega source y layer con geometría

**`trazarRutaMapboxConId(reclamos, mapa, color, routeId)`**
- Similar a trazarRutaMapbox pero con ID único
- Para múltiples rutas simultáneas

#### Utilidades

**`formatearFecha(fecha)`**
- Convierte a formato local argentino
- Formato: DD/MM/YYYY HH:MM

**`mostrarMensaje(mensaje, tipo)`**
- Crea alert flotante Bootstrap
- Se auto-elimina en 5 segundos
- Tipos: success, warning, info, error

**`mostrarConfirmacion(mensaje, titulo)`**
- Modal de confirmación personalizado
- Retorna Promise con true/false
- Usado antes de eliminar rutas

#### Lifecycle Hook

**`mounted()`**
- Ejecuta al montar componente:
  - Obtiene rutas
  - Obtiene reclamos

---

## Flujo de Trabajo Completo

### Creación de Ruta Automática
1. Usuario hace clic en "Nueva Hoja de Ruta"
2. Configura: nombre, color, cantidad
3. Click en "Generar Vista Previa"
4. Frontend llama a `vistaPreviaRuta()` del backend
5. Backend ejecuta algoritmo de optimización
6. Frontend muestra mapa con ruta optimizada
7. Usuario revisa y puede editar
8. Click en "Crear Ruta Automática"
9. Frontend llama a `generarRuta()` del backend
10. Backend guarda en BD (asignada = 0)
11. Frontend cierra modal y refresca tabla

### Edición de Ruta
1. Desde vista previa, click en "Editar Hoja de Ruta"
2. Se activa modoEdicion
3. Usuario puede:
   - Reordenar con flechas
   - Eliminar reclamos
   - Agregar con click en mapa
4. Mapa se actualiza en tiempo real
5. Puede cancelar (restaura original) o guardar
6. Al guardar: se envía modoManual=true con orden exacto
7. Backend guarda sin re-optimizar

### Visualización de Ruta
1. Click en ojo de una ruta en la tabla
2. Frontend obtiene datos de la ruta
3. Obtiene reclamos con sus posiciones
4. Muestra modal con mapa y lista
5. Click en lista centra mapa con animación
6. Puede alternar entre Google Maps y Mapbox

---

## Integraciones Externas

### Google Maps API
- **Geocoding API**: Convertir direcciones a coordenadas
- **Directions API**: Trazar rutas por calles reales
- **Maps JavaScript API**: Visualización de mapas
- **Fallback**: Si falla, cambia automáticamente a Mapbox

### Mapbox
- **Geocoding API**: Alternativa para geocodificación
- **Directions API**: Alternativa para rutas
- **GL JS**: Librería de mapas
- **Uso**: Como alternativa y backup de Google Maps

---

## Base de Datos

### Tablas Principales
1. **ruta**: Almacena hojas de ruta
2. **ruta_reclamo**: Relación N:N con posiciones
3. **direccion**: Direcciones personalizadas (pre-cargadas para performance)

### Relaciones
- Una ruta tiene muchos reclamos (a través de ruta_reclamo)
- ruta_reclamo almacena el orden (campo posicion)
- Direcciones personalizadas tienen prioridad sobre geocodificación

---

## Características Técnicas

### Optimización de Performance
- **Cache de coordenadas**: Evita geocodificaciones repetidas
- **Pre-carga de direcciones**: Una consulta al inicio vs N consultas
- **Paralelización**: Obtención de coordenadas en paralelo con Promise.all()
- **Lazy loading de mapas**: Se inicializan solo cuando se muestran

### Manejo de Errores
- Try-catch en todas las operaciones asíncronas
- Fallbacks automáticos (Google Maps → Mapbox)
- Mensajes de error amigables al usuario
- Validaciones previas a operaciones

### UX/UI
- Mensajes de confirmación antes de eliminar
- Animaciones en marcadores (rebote, pulso)
- Badges de color para estados
- Interactividad completa con el mapa
- Actualización en tiempo real durante edición

---

## Algoritmos Clave

### Algoritmo de Priorización
1. Prioridad Alta primero
2. Si faltan reclamos: Completar con Baja
3. Dentro de cada prioridad: Vecino más cercano

### Algoritmo del Vecino Más Cercano
1. Inicia en punto más cercano al tanque de agua
2. Itera: selecciona el más cercano al actual
3. Repite hasta completar N reclamos

### Cálculo de Distancia
- Fórmula de Haversine
- Considera curvatura terrestre
- Radio: 6371 km

### Estimación de Tiempo
- 15 min por reclamo (trabajo en sitio)
- Tiempo de viaje: distancia / 30 km/h (promedio urbano)
- Formato final: HH:MM:SS

---

## Notas para Documentación

- El sistema mantiene coherencia entre base de datos y visualización
- Todas las operaciones son transaccionales (si falla algo, no se guarda nada)
- El campo "asignada" reemplazó a "activa" para mayor claridad semántica
- La vista previa es obligatoria antes de crear (mejora UX y validación)
- El modo edición preserva la ruta original permitiendo cancelación sin pérdida


