<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\DireccionModel;

class Direcciones extends ResourceController
{
    protected $modelName = 'App\Models\DireccionModel';
    protected $format = 'json';

    public function index()
    {
        return $this->respond($this->model->findAll());
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        // Validar datos obligatorios
        if (empty($data['domicilio']) || empty($data['numero_domicilio']) || empty($data['latitud']) || empty($data['longitud'])) {
            return $this->failValidationErrors('Faltan datos obligatorios (domicilio, numero_domicilio, latitud, longitud).');
        }

        // Normalizar los datos para la búsqueda
        $domicilioNormalizado = $this->normalizarTexto($data['domicilio']);
        $numeroNormalizado = $this->normalizarNumero($data['numero_domicilio']);

        // Verificar si ya existe una dirección con el mismo domicilio y número
        $direccionExistente = $this->model->where('TRIM(UPPER(domicilio))', $domicilioNormalizado)
                                        ->where('TRIM(numero_domicilio)', $numeroNormalizado)
                                        ->first();

        if ($direccionExistente) {
            // Si existe, actualizar las coordenadas y marcar como personalizada
            $actualizado = $this->model->update($direccionExistente['id'], [
                'latitud' => $data['latitud'],
                'longitud' => $data['longitud'],
                'personalizada' => 1 // Marcar como personalizada cuando se actualiza desde el mapa
            ]);

            if ($actualizado === false) {
                return $this->failServerError('Error al actualizar la dirección.');
            }

            $direccionActualizada = $this->model->find($direccionExistente['id']);
            return $this->respond($direccionActualizada);
        } else {
            // Si no existe, crear nueva dirección
            // Si viene el campo 'personalizada', usarlo; si no, por defecto es 1 (desde mapa)
            if (!isset($data['personalizada'])) {
                $data['personalizada'] = 1; // Por defecto, desde el mapa es personalizada
            }
            
            $direccionId = $this->model->insert($data);

            if ($direccionId === false) {
                return $this->failServerError('Error al guardar la dirección.');
            }

            $direccionCreada = $this->model->find($direccionId);
            return $this->respondCreated($direccionCreada);
        }
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);

        if (!$id || empty($data)) {
            return $this->failValidationErrors('Faltan datos obligatorios.');
        }

        $actualizado = $this->model->update($id, $data);

        if ($actualizado === false) {
            return $this->failServerError('Error al actualizar la dirección.');
        }

        $direccionActualizada = $this->model->find($id);
        return $this->respond($direccionActualizada);
    }

    public function delete($id = null)
    {
        if (!$id || !$this->model->find($id)) {
            return $this->failNotFound('Dirección no encontrada.');
        }

        $this->model->delete($id);
        return $this->respondDeleted(['mensaje' => 'Dirección eliminada con éxito.']);
    }

    /**
     * Busca una dirección por domicilio y número
     */
    public function buscarPorDomicilio()
    {
        try {
            $domicilio = $this->request->getGet('domicilio');
            $numeroDomicilio = $this->request->getGet('numero_domicilio');

            log_message('debug', "Método buscarPorDomicilio llamado con: domicilio='{$domicilio}', numero='{$numeroDomicilio}'");

            if (empty($domicilio) || empty($numeroDomicilio)) {
                log_message('debug', "Parámetros faltantes");
                return $this->failValidationErrors('Faltan parámetros obligatorios (domicilio, numero_domicilio).');
            }

            // Normalizar los datos de entrada
            $domicilioNormalizado = $this->normalizarTexto($domicilio);
            $numeroNormalizado = $this->normalizarNumero($numeroDomicilio);

            // Log para debugging
            log_message('debug', "Buscando dirección - Original: '{$domicilio}' '{$numeroDomicilio}' | Normalizado: '{$domicilioNormalizado}' '{$numeroNormalizado}'");

            // Buscar con comparación normalizada
            $direccion = $this->model->where('TRIM(UPPER(domicilio))', $domicilioNormalizado)
                                    ->where('TRIM(numero_domicilio)', $numeroNormalizado)
                                    ->first();

            if ($direccion) {
                // Asegurar que las coordenadas sean números válidos
                $direccion['latitud'] = (float) $direccion['latitud'];
                $direccion['longitud'] = (float) $direccion['longitud'];
                
                // Log para debugging
                log_message('debug', "Dirección encontrada: " . json_encode($direccion));
                log_message('debug', "Coordenadas convertidas - Lat: {$direccion['latitud']}, Lng: {$direccion['longitud']}");
                
                return $this->respond([$direccion]); // Siempre retornar como array
            } else {
                log_message('debug', "No se encontró dirección para: {$domicilio} {$numeroDomicilio}");
                return $this->respond([]);
            }
        } catch (Exception $e) {
            log_message('error', "Error en buscarPorDomicilio: " . $e->getMessage());
            return $this->failServerError('Error interno del servidor: ' . $e->getMessage());
        }
    }

    /**
     * Normaliza texto para comparación (elimina espacios, convierte a mayúsculas, etc.)
     */
    private function normalizarTexto($texto)
    {
        if (empty($texto)) return '';
        
        // Eliminar espacios al inicio y final
        $texto = trim($texto);
        
        // Convertir a mayúsculas
        $texto = strtoupper($texto);
        
        // Normalizar caracteres especiales comunes
        $texto = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'N'], $texto);
        
        return $texto;
    }

    /**
     * Normaliza número de domicilio (elimina espacios, caracteres no numéricos)
     */
    private function normalizarNumero($numero)
    {
        if (empty($numero)) return '';
        
        // Eliminar espacios al inicio y final
        $numero = trim($numero);
        
        // Si es solo números, mantenerlo así
        if (is_numeric($numero)) {
            return $numero;
        }
        
        // Si tiene letras o caracteres especiales, mantenerlo tal como está
        // pero sin espacios al inicio y final
        return $numero;
    }
}
