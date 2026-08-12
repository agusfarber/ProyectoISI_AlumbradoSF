<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\MaterialModel;
use App\Models\Tipo_materialModel;

class Materiales extends ResourceController
{
    protected $modelName = 'App\Models\MaterialModel';
    protected $format = 'json';

    public function __construct()
    {
        // Se carga el modelo de tipos de material en el constructor
        $this->tipoMaterialModel = new Tipo_materialModel();
    }

    // Métodos para Materiales (CRUD)
    // ------------------------------------

    public function index()
    {
        return $this->respond($this->model->findAllWithTipo());
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        // Solo el nombre es obligatorio; el tipo es opcional
        if (empty($data['nombre'])) {
            return $this->failValidationErrors('El nombre del material es obligatorio.');
        }

        $data['nombre'] = trim((string) $data['nombre']);
        
        if ($data['nombre'] === '') {
            return $this->failValidationErrors('El nombre del material no puede estar vacío.');
        }

        // Verificar si el material ya existe (case-insensitive)
        $materiales = $this->model->findAll();
        foreach ($materiales as $mat) {
            if (strtolower(trim($mat['nombre'])) === strtolower($data['nombre'])) {
                return $this->failValidationErrors('El material "' . $data['nombre'] . '" ya existe.');
            }
        }

        // Manejar tipo (opcional)
        if (isset($data['idTipo']) && $data['idTipo'] !== '' && $data['idTipo'] !== null) {
            $data['idTipo'] = (int) $data['idTipo'];
            if ($data['idTipo'] < 0) {
                return $this->failValidationErrors('El tipo de material es inválido.');
            }
            // Si es 0, establecer como null
            if ($data['idTipo'] === 0) {
                $data['idTipo'] = null;
            }
        } else {
            $data['idTipo'] = null; // Tipo opcional
        }

        unset($data['foto'], $data['cantidad']); // foto por endpoint dedicado; cantidad ya no existe

        $id = $this->model->insert($data);
        if ($id === false) {
            return $this->failServerError('Error al guardar material.');
        }

        $created = $this->model->find($id);
        return $this->respondCreated($created);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        if (!$id || empty($data)) {
            return $this->failValidationErrors('Faltan datos obligatorios.');
        }

        if (isset($data['nombre'])) {
            $data['nombre'] = trim((string) $data['nombre']);
            if ($data['nombre'] === '') {
                return $this->failValidationErrors('El nombre no puede estar vacío.');
            }
            
            // Verificar si el nuevo nombre ya existe en otro material (case-insensitive)
            $materiales = $this->model->findAll();
            foreach ($materiales as $mat) {
                // Excluir el material actual de la verificación
                if ($mat['id'] != $id && strtolower(trim($mat['nombre'])) === strtolower($data['nombre'])) {
                    return $this->failValidationErrors('El material "' . $data['nombre'] . '" ya existe.');
                }
            }
        }

        if (isset($data['idTipo'])) {
            $data['idTipo'] = (int) $data['idTipo'];
            // Permite un valor de 0 (o nulo) para idTipo
            if ($data['idTipo'] < 0) {
                return $this->failValidationErrors('El tipo de material es inválido.');
            }
        }
        
        // Asigna el valor NULL si el idTipo es 0
        if (isset($data['idTipo']) && $data['idTipo'] === 0) {
            $data['idTipo'] = null;
        }

        unset($data['cantidad']); // Columna eliminada del catálogo

        if (array_key_exists('foto', $data) && ($data['foto'] === null || $data['foto'] === '')) {
            $actual = $this->model->find($id);
            $this->eliminarArchivoFoto($actual['foto'] ?? null);
            $data['foto'] = null;
        } else {
            // Evitar sobrescribir foto por payload JSON accidental
            unset($data['foto']);
        }

        $ok = $this->model->update($id, $data);
        if ($ok === false) {
            return $this->failServerError('Error al actualizar el material.');
        }

        $updated = $this->model->find($id);
        return $this->respond($updated);
    }

    public function delete($id = null)
    {
        $material = $id ? $this->model->find($id) : null;
        if (!$id || !$material) {
            return $this->failNotFound('Material no encontrado.');
        }

        $this->eliminarArchivoFoto($material['foto'] ?? null);
        $this->model->delete($id);
        return $this->respondDeleted(['mensaje' => 'Material eliminado con éxito.']);
    }

    /**
     * Sube/actualiza la foto de un material.
     * Recibe el archivo en el campo 'foto' (multipart/form-data).
     */
    public function subirFoto($id = null)
    {
        $material = $id ? $this->model->find($id) : null;
        if (!$material) {
            return $this->failNotFound('Material no encontrado.');
        }

        $archivo = $this->request->getFile('foto');
        if (!$archivo || !$archivo->isValid()) {
            return $this->failValidationErrors('No se recibió un archivo válido.');
        }

        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($archivo->getMimeType(), $tiposPermitidos, true)) {
            return $this->failValidationErrors('Formato no permitido. Use JPG, PNG o WEBP.');
        }

        if ($archivo->getSize() > 2 * 1024 * 1024) {
            return $this->failValidationErrors('La imagen no debe superar los 2 MB.');
        }

        $directorio = FCPATH . 'static/uploads/materiales';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $extension = $archivo->getExtension() ?: 'jpg';
        $nombreArchivo = 'mat' . $id . '_' . bin2hex(random_bytes(6)) . '.' . $extension;

        if (!$archivo->move($directorio, $nombreArchivo)) {
            return $this->failServerError('No se pudo guardar la imagen.');
        }

        $this->eliminarArchivoFoto($material['foto'] ?? null);
        $this->model->update($id, ['foto' => $nombreArchivo]);

        return $this->respond([
            'mensaje' => 'Foto actualizada correctamente.',
            'foto' => $nombreArchivo,
            'url' => base_url('static/uploads/materiales/' . $nombreArchivo),
        ]);
    }

    private function eliminarArchivoFoto(?string $nombreArchivo): void
    {
        if (empty($nombreArchivo)) {
            return;
        }
        $ruta = FCPATH . 'static/uploads/materiales' . DIRECTORY_SEPARATOR . $nombreArchivo;
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }

    /**
     * Verifica si un material existe por nombre (case-insensitive)
     */
    public function verificarExistencia()
    {
        $nombre = $this->request->getGet('nombre');
        
        if (empty($nombre)) {
            return $this->failValidationErrors('El nombre del material es requerido.');
        }
        
        $nombre = trim((string) $nombre);
        
        // Buscar material por nombre (case-insensitive)
        // Obtener todos los materiales y comparar en PHP (más compatible)
        $materiales = $this->model->findAll();
        $material = null;
        
        foreach ($materiales as $mat) {
            if (strtolower(trim($mat['nombre'])) === strtolower($nombre)) {
                $material = $mat;
                break;
            }
        }
        
        return $this->respond([
            'existe' => $material !== null,
            'material' => $material
        ]);
    }

    public function import()
    {
        try {
            $payload = $this->request->getJSON(true);
            
            // Log para debugging
            log_message('debug', 'Import payload recibido: ' . json_encode($payload));
            
            if (!$payload || !isset($payload['items']) || !is_array($payload['items'])) {
                log_message('error', 'Formato inválido en import: ' . json_encode($payload));
                return $this->failValidationErrors('Formato inválido. Se espera un objeto con el campo "items" (array).');
            }

            $items = $payload['items'];
            $errores = [];
            $validados = [];
            
            // Obtener tipos existentes
            $tiposExistentes = $this->tipoMaterialModel->findAll();
            log_message('debug', 'Tipos existentes: ' . json_encode($tiposExistentes));
            
            // Crear mapa de tipos (case insensitive)
            $tiposMap = [];
            foreach ($tiposExistentes as $tipo) {
                $tiposMap[strtolower(trim($tipo['nombre']))] = $tipo['id'];
            }
            log_message('debug', 'Mapa de tipos: ' . json_encode($tiposMap));
            
            foreach ($items as $index => $item) {
                log_message('debug', "Procesando item {$index}: " . json_encode($item));
                
                $nombre = isset($item['nombre']) ? trim((string) $item['nombre']) : '';
                $tipoNombre = isset($item['tipo']) ? trim((string) $item['tipo']) : '';
                
                // Si el tipo está vacío, asignar null. Si no, buscar en el mapa de tipos
                if ($tipoNombre === '') {
                    $idTipo = null;
                } else {
                    $idTipo = $tiposMap[strtolower($tipoNombre)] ?? 0;
                }
                
                log_message('debug', "Item {$index} procesado - Nombre: '{$nombre}', Tipo: '{$tipoNombre}', IDTipo: " . ($idTipo === null ? 'null' : $idTipo));
                
                // Validaciones más detalladas
                if ($nombre === '') {
                    $errores[] = "Fila " . ($index + 1) . ": El nombre no puede estar vacío.";
                    continue;
                }
                
                // Solo validar que el tipo existe si se proporcionó un tipo (no está vacío)
                if ($tipoNombre !== '' && $idTipo === 0) {
                    $errores[] = "Fila " . ($index + 1) . ": El tipo '{$tipoNombre}' no existe. Tipos disponibles: " . implode(', ', array_keys($tiposMap));
                    continue;
                }

                // Catálogo: solo nombre y categoría
                $validados[] = [
                    'nombre' => $nombre,
                    'idTipo' => $idTipo,
                ];
            }

            log_message('debug', 'Items validados: ' . count($validados) . ', Errores: ' . count($errores));

            if (empty($validados)) {
                $mensajeError = 'No hay materiales válidos para importar.';
                if (!empty($errores)) {
                    $mensajeError .= ' Detalles: ' . implode(' | ', $errores);
                }
                log_message('error', $mensajeError);
                return $this->failValidationErrors($mensajeError);
            }

            // Obtener todos los materiales existentes para comparar por nombre
            $materialesExistentes = $this->model->findAll();
            $materialesMap = [];
            foreach ($materialesExistentes as $mat) {
                $materialesMap[strtolower(trim($mat['nombre']))] = $mat;
            }

            // Procesar cada material: actualizar si existe, crear si no existe
            $insertados = 0;
            $actualizados = 0;
            $erroresProcesamiento = [];

            foreach ($validados as $item) {
                $nombreLower = strtolower(trim($item['nombre']));
                
                // Verificar si el material ya existe
                if (isset($materialesMap[$nombreLower])) {
                    // Material existe: actualizar tipo/categoría
                    $materialExistente = $materialesMap[$nombreLower];
                    $idMaterial = $materialExistente['id'];
                    
                    $datosActualizar = [
                        'idTipo' => $item['idTipo']
                    ];
                    
                    // Solo actualizar si hay cambios
                    $hayCambios = false;
                    if ($materialExistente['idTipo'] != $item['idTipo']) {
                        $hayCambios = true;
                    }
                    
                    if ($hayCambios) {
                        $resultado = $this->model->update($idMaterial, $datosActualizar);
                        if ($resultado === false) {
                            $erroresProcesamiento[] = "Error al actualizar material '{$item['nombre']}'";
                            log_message('error', "Error al actualizar material ID {$idMaterial}: " . json_encode($datosActualizar));
                        } else {
                            $actualizados++;
                            log_message('debug', "Material '{$item['nombre']}' actualizado (ID: {$idMaterial})");
                        }
                    } else {
                        log_message('debug', "Material '{$item['nombre']}' sin cambios, se omite");
                    }
                } else {
                    // Material no existe: crear nuevo
                    $resultado = $this->model->insert($item);
            if ($resultado === false) {
                        $erroresProcesamiento[] = "Error al crear material '{$item['nombre']}'";
                        log_message('error', "Error al crear material: " . json_encode($item));
                    } else {
                        $insertados++;
                        // Actualizar el mapa para evitar duplicados en la misma importación
                        $materialesMap[$nombreLower] = ['id' => $resultado, 'nombre' => $item['nombre']];
                        log_message('debug', "Material '{$item['nombre']}' creado (ID: {$resultado})");
                    }
                }
            }

            log_message('info', "Importación completada: {$insertados} materiales insertados, {$actualizados} materiales actualizados");

            return $this->respond([
                'mensaje' => 'Importación completada.',
                'insertados' => $insertados,
                'actualizados' => $actualizados,
                'errores' => array_merge($errores, $erroresProcesamiento),
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Error en import: ' . $e->getMessage());
            return $this->failServerError('Error interno del servidor: ' . $e->getMessage());
        }
    }

    // Métodos para Tipos de Materiales
    // ------------------------------------

    public function getTipos()
    {
        return $this->respond($this->tipoMaterialModel->findAll());
    }

    public function createTipo()
    {
        $data = $this->request->getJSON(true);
        if (empty($data['nombre'])) {
            return $this->failValidationErrors('El nombre del tipo de material es obligatorio.');
        }

        $nombre = trim((string) $data['nombre']);
        if ($nombre === '') {
            return $this->failValidationErrors('El nombre del tipo de material no puede estar vacío.');
        }

        $payload = ['nombre' => $nombre];

        if (array_key_exists('icono', $data) && $data['icono'] !== null && $data['icono'] !== '') {
            $icono = $this->validarIconoTipo($data['icono']);
            if ($icono === false) {
                return $this->failValidationErrors('El icono seleccionado no es válido.');
            }
            $payload['icono'] = $icono;
        }

        if (array_key_exists('color', $data) && $data['color'] !== null && $data['color'] !== '') {
            $color = $this->validarColorTipo($data['color']);
            if ($color === false) {
                return $this->failValidationErrors('El color seleccionado no es válido.');
            }
            $payload['color'] = $color;
        }

        $id = $this->tipoMaterialModel->insert($payload);
        if ($id === false) {
            return $this->failServerError('Error al guardar tipo de material.');
        }

        $created = $this->tipoMaterialModel->find($id);
        return $this->respondCreated($created);
    }

    public function updateTipo($id = null)
    {
        if (!$id || !$this->tipoMaterialModel->find($id)) {
            return $this->failNotFound('Tipo de material no encontrado.');
        }

        $data = $this->request->getJSON(true) ?? [];
        $payload = [];

        if (array_key_exists('nombre', $data)) {
            $nombre = trim((string) $data['nombre']);
            if ($nombre === '') {
                return $this->failValidationErrors('El nombre del tipo de material no puede estar vacío.');
            }
            $payload['nombre'] = $nombre;
        }

        if (array_key_exists('icono', $data)) {
            if ($data['icono'] === null || $data['icono'] === '') {
                $payload['icono'] = null;
            } else {
                $icono = $this->validarIconoTipo($data['icono']);
                if ($icono === false) {
                    return $this->failValidationErrors('El icono seleccionado no es válido.');
                }
                $payload['icono'] = $icono;
            }
        }

        if (array_key_exists('color', $data)) {
            if ($data['color'] === null || $data['color'] === '') {
                $payload['color'] = null;
            } else {
                $color = $this->validarColorTipo($data['color']);
                if ($color === false) {
                    return $this->failValidationErrors('El color seleccionado no es válido.');
                }
                $payload['color'] = $color;
            }
        }

        if ($payload === []) {
            return $this->failValidationErrors('No hay datos para actualizar.');
        }

        if ($this->tipoMaterialModel->update($id, $payload) === false) {
            return $this->failServerError('Error al actualizar tipo de material.');
        }

        return $this->respond($this->tipoMaterialModel->find($id));
    }

    /**
     * Valida clase Bootstrap Icons. Devuelve el string limpio o false.
     */
    private function validarIconoTipo($icono)
    {
        $icono = trim((string) $icono);
        if (!preg_match('/^bi bi-[a-z0-9-]+$/', $icono)) {
            return false;
        }
        return $icono;
    }

    /**
     * Valida color hex (#RGB o #RRGGBB). Devuelve normalizado a #RRGGBB o false.
     */
    private function validarColorTipo($color)
    {
        $color = trim((string) $color);
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return false;
        }
        if (strlen($color) === 4) {
            $color = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
        }
        return strtolower($color);
    }


    public function deleteTipo($id = null)
    {
        if (!$id || !$this->tipoMaterialModel->find($id)) {
            return $this->failNotFound('Tipo de material no encontrado.');
        }

        // Verifica si hay materiales asociados a este tipo antes de borrar
        $materialesAsociados = $this->model->where('idTipo', $id)->first();
        if ($materialesAsociados) {
            return $this->failConflict('No se puede eliminar el tipo porque tiene materiales asociados.');
        }

        $this->tipoMaterialModel->delete($id);
        return $this->respondDeleted(['mensaje' => 'Tipo de material eliminado con éxito.']);
    }
}