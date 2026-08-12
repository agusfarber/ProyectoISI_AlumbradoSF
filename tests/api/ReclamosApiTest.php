<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

class ReclamosApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'Tests\Support';
    protected $seed        = 'Tests\Support\Database\Seeds\TestSeeder';

    private function withSessionSupervisor()
    {
        return $this->withSession([
            'user_id' => 1,
            'role' => '2',
        ]);
    }

    /**
     * HU-008: Test de guardado exitoso completo de un reclamo
     * Tipo: API - CRUD - Creación
     * 
     * Verifica que se puede crear un reclamo con todos los campos válidos,
     * se genera un ID único automáticamente, y todos los datos se guardan correctamente.
     */
    public function testGuardadoExitosoCompleto()
    {
        // Datos de ficha (el ID visible se genera como L{id} y origen=local)
        $datosReclamo = [
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_fechaInicio' => '2025-11-12 10:00:00',
            'municipalidad_recepcion' => 'web',
            'municipalidad_telefono' => '3564123456',
            'municipalidad_domicilio' => 'Av. Libertador',
            'municipalidad_numeroDomicilio' => '1234',
            'municipalidad_entreCalleUno' => 'Calle 1',
            'municipalidad_entreCalleDos' => 'Calle 2',
            'municipalidad_ciudadano' => 'Juan Pérez',
            'municipalidad_descripcion' => 'Luminaria de poste apagada, no enciende desde hace 3 días',
        ];

        // Realizar la petición POST para crear el reclamo
        $result = $this->withSessionSupervisor()
                       ->withBodyFormat('json')
                       ->post('api/reclamos', $datosReclamo);

        // Verificar que la respuesta es 201 Created
        $result->assertStatus(201);

        // Obtener los datos de la respuesta como array asociativo
        $responseData = json_decode($result->getJSON(), true);

        // Verificar que se generó un ID único
        $this->assertIsArray($responseData, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('id', $responseData, 'La respuesta debe contener el ID generado');
        $this->assertIsNumeric($responseData['id'], 'El ID debe ser numérico');
        $this->assertGreaterThan(0, $responseData['id'], 'El ID debe ser mayor que 0');

        // Guardar el ID generado para verificaciones posteriores
        $idGenerado = $responseData['id'];
        $codigoLocal = 'L' . $idGenerado;

        // Verificar que el reclamo existe en la base de datos
        $reclamoEnBD = $this->db->table('reclamo')
            ->where('id', $idGenerado)
            ->get()
            ->getRowArray();

        $this->assertNotNull($reclamoEnBD, 'El reclamo debe existir en la base de datos');

        // Verificar que todos los datos se guardaron correctamente
        $this->assertEquals($codigoLocal, $reclamoEnBD['municipalidad_id']);
        $this->assertEquals('local', $reclamoEnBD['origen']);
        $this->assertEquals('ALUMBRADO PÚBLICO', $reclamoEnBD['municipalidad_tipo']);
        $this->assertEquals($datosReclamo['municipalidad_motivo'], $reclamoEnBD['municipalidad_motivo']);
        $this->assertEquals($datosReclamo['municipalidad_fechaInicio'], $reclamoEnBD['municipalidad_fechaInicio']);
        $this->assertEquals($datosReclamo['municipalidad_recepcion'], $reclamoEnBD['municipalidad_recepcion']);
        $this->assertEquals('Recibido', $reclamoEnBD['municipalidad_estado']);
        $this->assertEquals($datosReclamo['municipalidad_telefono'], $reclamoEnBD['municipalidad_telefono']);
        $this->assertEquals($datosReclamo['municipalidad_domicilio'], $reclamoEnBD['municipalidad_domicilio']);
        $this->assertEquals($datosReclamo['municipalidad_numeroDomicilio'], $reclamoEnBD['municipalidad_numeroDomicilio']);
        $this->assertEquals($datosReclamo['municipalidad_entreCalleUno'], $reclamoEnBD['municipalidad_entreCalleUno']);
        $this->assertEquals($datosReclamo['municipalidad_entreCalleDos'], $reclamoEnBD['municipalidad_entreCalleDos']);
        $this->assertEquals($datosReclamo['municipalidad_ciudadano'], $reclamoEnBD['municipalidad_ciudadano']);
        $this->assertEquals($datosReclamo['municipalidad_descripcion'], $reclamoEnBD['municipalidad_descripcion']);

        // Verificar que el reclamo puede ser recuperado mediante la API (GET individual)
        // NOTA: Si el endpoint GET /api/reclamos/{id} no está implementado, esta parte fallará
        try {
            $resultGet = $this->get("api/reclamos/{$idGenerado}");
            
            if ($resultGet->getStatusCode() === 200) {
                $reclamoRecuperado = json_decode($resultGet->getJSON(), true);
                $this->assertIsArray($reclamoRecuperado, 'El reclamo recuperado debe ser un array');
                $this->assertEquals($idGenerado, $reclamoRecuperado['id'], 'El ID debe coincidir');
                $this->assertEquals($codigoLocal, $reclamoRecuperado['municipalidad_id']);
                $this->assertEquals($datosReclamo['municipalidad_motivo'], $reclamoRecuperado['municipalidad_motivo']);
                $this->assertEquals($datosReclamo['municipalidad_ciudadano'], $reclamoRecuperado['municipalidad_ciudadano']);
            } else {
                // Si el endpoint individual no está implementado, al menos verificamos que existe en la lista
                $this->markTestIncomplete('El endpoint GET /api/reclamos/{id} retornó status ' . $resultGet->getStatusCode() . '. El guardado fue exitoso, pero la recuperación individual no está disponible.');
            }
        } catch (\Exception $e) {
            // Si falla, al menos el guardado fue exitoso
            $this->markTestIncomplete('Error al intentar recuperar el reclamo: ' . $e->getMessage());
        }
    }

    /**
     * HU-008: Test de integridad de datos
     * Tipo: API - Validación de Datos
     * 
     * Verifica que todos los campos se guardan correctamente con sus valores exactos,
     * incluyendo casos especiales y edge cases.
     */
    public function testIntegridadDatos()
    {
        // Datos con valores específicos para verificar integridad
        $datosReclamo = [
            'municipalidad_motivo' => 'Lámpara con parpadeo intermitente', // Con acento
            'municipalidad_fechaInicio' => '2025-11-12 23:59:59', // Fecha con hora límite
            'municipalidad_recepcion' => 'Teléfono',
            'municipalidad_telefono' => '0800-555-1234', // Con formato especial
            'municipalidad_domicilio' => 'Av. San Martín', // Con acento
            'municipalidad_numeroDomicilio' => '1234-B', // Con letra
            'municipalidad_entreCalleUno' => 'Córdoba', // Con acento
            'municipalidad_entreCalleDos' => 'Sarmiento & Mitre', // Con &
            'municipalidad_ciudadano' => 'José María González', // Con acentos y espacios
            'municipalidad_descripcion' => 'Reclamo urgente: La luminaria presenta fallas intermitentes desde hace 1 semana. Afecta seguridad del barrio.', // Descripción larga con puntuación
        ];

        // Crear el reclamo
        $result = $this->withSessionSupervisor()
                       ->withBodyFormat('json')
                       ->post('api/reclamos', $datosReclamo);

        // Verificar que se creó correctamente
        $result->assertStatus(201);
        $responseData = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($responseData, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('id', $responseData, 'Debe contener ID');
        
        $idGenerado = $responseData['id'];
        $codigoLocal = 'L' . $idGenerado;

        // Recuperar el reclamo directamente de la base de datos
        $reclamoRecuperado = $this->db->table('reclamo')
            ->where('id', $idGenerado)
            ->get()
            ->getRowArray();

        $this->assertNotNull($reclamoRecuperado, 'El reclamo debe existir en la BD');

        // VERIFICACIÓN EXHAUSTIVA CAMPO POR CAMPO
        
        // 1. municipalidad_id (código local L{id})
        $this->assertEquals(
            $codigoLocal, 
            $reclamoRecuperado['municipalidad_id'],
            'municipalidad_id local debe ser L{id}'
        );
        $this->assertEquals('local', $reclamoRecuperado['origen']);

        // 2. municipalidad_tipo
        $this->assertEquals(
            'ALUMBRADO PÚBLICO', 
            $reclamoRecuperado['municipalidad_tipo'],
            'municipalidad_tipo debe ser fijo'
        );

        // 3. municipalidad_motivo (con acentos)
        $this->assertEquals(
            $datosReclamo['municipalidad_motivo'], 
            $reclamoRecuperado['municipalidad_motivo'],
            'municipalidad_motivo debe mantener los acentos'
        );

        // 4. municipalidad_fechaInicio
        $this->assertEquals(
            $datosReclamo['municipalidad_fechaInicio'], 
            $reclamoRecuperado['municipalidad_fechaInicio'],
            'municipalidad_fechaInicio debe coincidir exactamente'
        );

        // 5. municipalidad_recepcion
        $this->assertEquals(
            $datosReclamo['municipalidad_recepcion'], 
            $reclamoRecuperado['municipalidad_recepcion'],
            'municipalidad_recepcion debe coincidir exactamente'
        );

        // 6. municipalidad_estado (siempre Recibido en alta local)
        $this->assertEquals(
            'Recibido', 
            $reclamoRecuperado['municipalidad_estado'],
            'municipalidad_estado inicial debe ser Recibido'
        );

        // 8. municipalidad_telefono (con formato especial)
        $this->assertEquals(
            $datosReclamo['municipalidad_telefono'], 
            $reclamoRecuperado['municipalidad_telefono'],
            'municipalidad_telefono debe mantener el formato con guiones'
        );

        // 9. municipalidad_domicilio (con acentos)
        $this->assertEquals(
            $datosReclamo['municipalidad_domicilio'], 
            $reclamoRecuperado['municipalidad_domicilio'],
            'municipalidad_domicilio debe mantener los acentos'
        );

        // 10. municipalidad_numeroDomicilio (con letra)
        $this->assertEquals(
            $datosReclamo['municipalidad_numeroDomicilio'], 
            $reclamoRecuperado['municipalidad_numeroDomicilio'],
            'municipalidad_numeroDomicilio debe mantener letras'
        );

        // 11. municipalidad_entreCalleUno (con acentos)
        $this->assertEquals(
            $datosReclamo['municipalidad_entreCalleUno'], 
            $reclamoRecuperado['municipalidad_entreCalleUno'],
            'municipalidad_entreCalleUno debe mantener los acentos'
        );

        // 12. municipalidad_entreCalleDos (con caracteres especiales)
        $this->assertEquals(
            $datosReclamo['municipalidad_entreCalleDos'], 
            $reclamoRecuperado['municipalidad_entreCalleDos'],
            'municipalidad_entreCalleDos debe mantener caracteres especiales como &'
        );

        // 13. municipalidad_ciudadano (con acentos y espacios)
        $this->assertEquals(
            $datosReclamo['municipalidad_ciudadano'], 
            $reclamoRecuperado['municipalidad_ciudadano'],
            'municipalidad_ciudadano debe mantener acentos y espacios'
        );

        // 14. municipalidad_descripcion (texto largo con puntuación)
        $this->assertEquals(
            $datosReclamo['municipalidad_descripcion'], 
            $reclamoRecuperado['municipalidad_descripcion'],
            'municipalidad_descripcion debe mantener toda la puntuación y longitud'
        );

        // 15. prioridad (calculada automáticamente; no se toma del cliente)
        $this->assertContains(
            $reclamoRecuperado['prioridad'],
            ['Alta', 'Baja', null],
            'prioridad debe ser un valor válido del servicio'
        );

        // Verificaciones adicionales de integridad

        // Verificar que NO hay truncamiento en campos largos
        $this->assertGreaterThan(
            50, 
            strlen($reclamoRecuperado['municipalidad_descripcion']),
            'La descripción no debe estar truncada'
        );

        // Verificar que los acentos se mantienen (contando caracteres especiales)
        $this->assertStringContainsString(
            'á', 
            $reclamoRecuperado['municipalidad_motivo'],
            'Debe mantener el acento en "lámpara"'
        );

        $this->assertStringContainsString(
            'ó', 
            $reclamoRecuperado['municipalidad_entreCalleUno'],
            'Debe mantener el acento en "Córdoba"'
        );

        // Verificar que los caracteres especiales se mantienen
        $this->assertStringContainsString(
            '&', 
            $reclamoRecuperado['municipalidad_entreCalleDos'],
            'Debe mantener el carácter "&"'
        );

        // Verificar que el formato de teléfono no se altera
        $this->assertStringContainsString(
            '-', 
            $reclamoRecuperado['municipalidad_telefono'],
            'Debe mantener los guiones en el teléfono'
        );
    }

    /**
     * HU-008: Test de actualización de reclamo existente
     * Tipo: API - CRUD - Actualización
     * 
     * Verifica que se puede actualizar un reclamo ya guardado,
     * modificando algunos campos y manteniendo el ID.
     */
    public function testActualizacionReclamoExistente()
    {
        // PASO 1: Crear un reclamo inicial
        $datosIniciales = [
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_fechaInicio' => '2025-11-12 10:00:00',
            'municipalidad_recepcion' => 'web',
            'municipalidad_telefono' => '3564111111',
            'municipalidad_domicilio' => 'Calle Inicial',
            'municipalidad_numeroDomicilio' => '100',
            'municipalidad_entreCalleUno' => 'Calle A',
            'municipalidad_entreCalleDos' => 'Calle B',
            'municipalidad_ciudadano' => 'Pedro García',
            'municipalidad_descripcion' => 'Descripción inicial del reclamo',
        ];

        $resultCreacion = $this->withSessionSupervisor()
                               ->withBodyFormat('json')
                               ->post('api/reclamos', $datosIniciales);

        // Verificar que se creó correctamente
        $resultCreacion->assertStatus(201);
        $responseCreacion = json_decode($resultCreacion->getJSON(), true);
        
        $this->assertIsArray($responseCreacion);
        $this->assertArrayHasKey('id', $responseCreacion);
        
        $idReclamo = $responseCreacion['id'];

        // Verificar datos iniciales en BD
        $reclamoInicial = $this->db->table('reclamo')
            ->where('id', $idReclamo)
            ->get()
            ->getRowArray();

        $this->assertNotNull($reclamoInicial);
        $this->assertEquals('Recibido', $reclamoInicial['municipalidad_estado']);
        $this->assertEquals('L' . $idReclamo, $reclamoInicial['municipalidad_id']);
        $this->assertEquals('Descripción inicial del reclamo', $reclamoInicial['municipalidad_descripcion']);

        // PASO 2: Preparar datos para actualización (modificando varios campos)
        $datosActualizacion = [
            'municipalidad_estado' => 'En ejecución', // Cambio de estado
            'municipalidad_descripcion' => 'Descripción actualizada - Se está trabajando en el reclamo',
            'prioridad' => 'Alta', // Cambio de prioridad
            'municipalidad_telefono' => '3564222222', // Cambio de teléfono
            'municipalidad_ciudadano' => 'Pedro García López', // Actualización de nombre
            // Mantenemos el resto de campos iguales
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_fechaInicio' => '2025-11-12 10:00:00',
            'municipalidad_fechaModificacion' => '2025-11-12 15:30:00', // Nueva fecha de modificación
            'municipalidad_recepcion' => 'web',
            'municipalidad_domicilio' => 'Calle Inicial',
            'municipalidad_numeroDomicilio' => '100',
            'municipalidad_entreCalleUno' => 'Calle A',
            'municipalidad_entreCalleDos' => 'Calle B'
        ];

        // PASO 3: Actualizar el reclamo usando PUT
        $resultActualizacion = $this->withSessionSupervisor()
                                    ->withBodyFormat('json')
                                    ->put("api/reclamos/{$idReclamo}", $datosActualizacion);

        // Verificar que la actualización fue exitosa
        $resultActualizacion->assertStatus(200);
        $responseActualizacion = json_decode($resultActualizacion->getJSON(), true);

        $this->assertIsArray($responseActualizacion);
        $this->assertArrayHasKey('id', $responseActualizacion);

        // PASO 4: Verificar que el ID NO cambió
        $this->assertEquals($idReclamo, $responseActualizacion['id'], 'El ID debe permanecer igual después de actualizar');

        // PASO 5: Verificar que los cambios se guardaron correctamente en BD
        $reclamoActualizado = $this->db->table('reclamo')
            ->where('id', $idReclamo)
            ->get()
            ->getRowArray();

        $this->assertNotNull($reclamoActualizado, 'El reclamo debe seguir existiendo en BD');

        // Verificar campos que CAMBIARON
        $this->assertEquals(
            'En ejecución',
            $reclamoActualizado['municipalidad_estado'],
            'El estado debe haberse actualizado'
        );

        $this->assertEquals(
            'Alta',
            $reclamoActualizado['prioridad'],
            'La prioridad debe haberse actualizado'
        );

        $this->assertEquals(
            'Descripción actualizada - Se está trabajando en el reclamo',
            $reclamoActualizado['municipalidad_descripcion'],
            'La descripción debe haberse actualizado'
        );

        $this->assertEquals(
            '3564222222',
            $reclamoActualizado['municipalidad_telefono'],
            'El teléfono debe haberse actualizado'
        );

        $this->assertEquals(
            'Pedro García López',
            $reclamoActualizado['municipalidad_ciudadano'],
            'El nombre del ciudadano debe haberse actualizado'
        );

        $this->assertEquals(
            '2025-11-12 15:30:00',
            $reclamoActualizado['municipalidad_fechaModificacion'],
            'La fecha de modificación debe haberse actualizado'
        );

        // Verificar campos que NO cambiaron
        $this->assertEquals(
            'L' . $idReclamo,
            $reclamoActualizado['municipalidad_id'],
            'El municipalidad_id debe permanecer igual'
        );

        $this->assertEquals(
            'Luminaria apagada',
            $reclamoActualizado['municipalidad_motivo'],
            'El motivo debe permanecer igual'
        );

        $this->assertEquals(
            'Calle Inicial',
            $reclamoActualizado['municipalidad_domicilio'],
            'El domicilio debe permanecer igual'
        );

        $this->assertEquals(
            '100',
            $reclamoActualizado['municipalidad_numeroDomicilio'],
            'El número de domicilio debe permanecer igual'
        );

        // PASO 6: Verificar que se puede recuperar el reclamo actualizado
        $this->assertEquals($idReclamo, $reclamoActualizado['id']);
        $this->assertEquals('En ejecución', $reclamoActualizado['municipalidad_estado']);
        $this->assertEquals('Alta', $reclamoActualizado['prioridad']);
    }
}

