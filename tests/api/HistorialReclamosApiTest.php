<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

class HistorialReclamosApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'Tests\Support';
    protected $seed        = 'Tests\Support\Database\Seeds\TestSeeder';

    /**
     * HU-010: Test de obtención del historial completo
     * Tipo: API - Consulta - Listado Completo
     * 
     * Verifica que se puede recuperar la lista completa de reclamos
     * con todas sus características.
     */
    public function testObtenerHistorialCompleto()
    {
        // PASO 1: Crear varios reclamos de prueba con diferentes características
        $reclamosTest = [
            [
                'municipalidad_id' => '40001',
                'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
                'municipalidad_motivo' => 'Luminaria apagada',
                'municipalidad_fechaInicio' => '2025-11-01 08:00:00',
                'municipalidad_fechaModificacion' => '2025-11-01 08:00:00',
                'municipalidad_recepcion' => 'Web',
                'municipalidad_estado' => 'Recibido',
                'municipalidad_telefono' => '3564100001',
                'municipalidad_domicilio' => 'Calle Primera',
                'municipalidad_numeroDomicilio' => '100',
                'municipalidad_entreCalleUno' => 'Calle A',
                'municipalidad_entreCalleDos' => 'Calle B',
                'municipalidad_ciudadano' => 'Juan Pérez',
                'municipalidad_descripcion' => 'Luminaria no enciende por la noche',
                'prioridad' => 'Alta'
            ],
            [
                'municipalidad_id' => '40002',
                'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
                'municipalidad_motivo' => 'Poste inclinado',
                'municipalidad_fechaInicio' => '2025-11-02 10:30:00',
                'municipalidad_fechaModificacion' => '2025-11-02 10:30:00',
                'municipalidad_recepcion' => 'Teléfono',
                'municipalidad_estado' => 'En ejecución',
                'municipalidad_telefono' => '3564100002',
                'municipalidad_domicilio' => 'Calle Segunda',
                'municipalidad_numeroDomicilio' => '200',
                'municipalidad_entreCalleUno' => 'Calle C',
                'municipalidad_entreCalleDos' => 'Calle D',
                'municipalidad_ciudadano' => 'María González',
                'municipalidad_descripcion' => 'Poste con riesgo de caída',
                'prioridad' => 'Alta'
            ],
            [
                'municipalidad_id' => '40003',
                'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
                'municipalidad_motivo' => 'Cable suelto',
                'municipalidad_fechaInicio' => '2025-11-03 14:00:00',
                'municipalidad_fechaModificacion' => '2025-11-03 14:00:00',
                'municipalidad_recepcion' => 'Sistema 103',
                'municipalidad_estado' => 'Completado',
                'municipalidad_telefono' => '3564100003',
                'municipalidad_domicilio' => 'Calle Tercera',
                'municipalidad_numeroDomicilio' => '300',
                'municipalidad_entreCalleUno' => 'Calle E',
                'municipalidad_entreCalleDos' => 'Calle F',
                'municipalidad_ciudadano' => 'Carlos López',
                'municipalidad_descripcion' => 'Cable colgando del poste',
                'prioridad' => 'Baja'
            ]
        ];

        // Insertar los reclamos en la base de datos
        foreach ($reclamosTest as $reclamo) {
            $this->db->table('reclamo')->insert($reclamo);
        }

        // PASO 2: Hacer GET al endpoint de historial
        $result = $this->get('api/reclamos');

        // PASO 3: Verificar status HTTP 200
        $result->assertStatus(200);

        // PASO 4: Obtener y parsear la respuesta
        $responseBody = $result->getJSON();
        $reclamos = json_decode($responseBody, true);

        // PASO 5: Verificar que retorna un array
        $this->assertIsArray(
            $reclamos,
            'La respuesta debe ser un array de reclamos'
        );

        // PASO 6: Verificar que hay al menos los reclamos que insertamos
        $this->assertGreaterThanOrEqual(
            3,
            count($reclamos),
            'Debe retornar al menos los 3 reclamos insertados'
        );

        // PASO 7: Verificar que cada reclamo tiene TODAS las características requeridas
        $camposRequeridos = [
            'id',
            'municipalidad_id',
            'municipalidad_tipo',
            'municipalidad_motivo',
            'municipalidad_fechaInicio',
            'municipalidad_fechaModificacion',
            'municipalidad_recepcion',
            'municipalidad_estado',
            'municipalidad_telefono',
            'municipalidad_domicilio',
            'municipalidad_numeroDomicilio',
            'municipalidad_entreCalleUno',
            'municipalidad_entreCalleDos',
            'municipalidad_ciudadano',
            'municipalidad_descripcion',
            'prioridad'
        ];

        foreach ($reclamos as $index => $reclamo) {
            foreach ($camposRequeridos as $campo) {
                $this->assertArrayHasKey(
                    $campo,
                    $reclamo,
                    "El reclamo #{$index} debe tener el campo '{$campo}'"
                );
            }
        }

        // PASO 8: Verificar estructura y tipos de datos de un reclamo específico
        // Buscar el reclamo con municipalidad_id = 40001
        $reclamoEspecifico = null;
        foreach ($reclamos as $reclamo) {
            if ($reclamo['municipalidad_id'] === '40001') {
                $reclamoEspecifico = $reclamo;
                break;
            }
        }

        $this->assertNotNull(
            $reclamoEspecifico,
            'Debe existir el reclamo con municipalidad_id 40001'
        );

        // PASO 9: Verificar valores específicos del reclamo
        $this->assertEquals('40001', $reclamoEspecifico['municipalidad_id']);
        $this->assertEquals('ALUMBRADO PÚBLICO', $reclamoEspecifico['municipalidad_tipo']);
        $this->assertEquals('Luminaria apagada', $reclamoEspecifico['municipalidad_motivo']);
        $this->assertEquals('Recibido', $reclamoEspecifico['municipalidad_estado']);
        $this->assertEquals('Alta', $reclamoEspecifico['prioridad']);
        $this->assertEquals('Web', $reclamoEspecifico['municipalidad_recepcion']);
        $this->assertEquals('3564100001', $reclamoEspecifico['municipalidad_telefono']);
        $this->assertEquals('Calle Primera', $reclamoEspecifico['municipalidad_domicilio']);
        $this->assertEquals('100', $reclamoEspecifico['municipalidad_numeroDomicilio']);
        $this->assertEquals('Juan Pérez', $reclamoEspecifico['municipalidad_ciudadano']);
        $this->assertEquals('Luminaria no enciende por la noche', $reclamoEspecifico['municipalidad_descripcion']);

        // PASO 10: Verificar que hay reclamos con diferentes estados
        $estados = array_column($reclamos, 'municipalidad_estado');
        $estadosUnicos = array_unique($estados);
        
        $this->assertContains('Recibido', $estadosUnicos, 'Debe haber al menos un reclamo en estado Recibido');
        $this->assertContains('En ejecución', $estadosUnicos, 'Debe haber al menos un reclamo en estado En ejecución');
        $this->assertContains('Completado', $estadosUnicos, 'Debe haber al menos un reclamo en estado Completado');

        // PASO 11: Verificar que hay reclamos con diferentes prioridades (Alta y Baja)
        $prioridades = array_column($reclamos, 'prioridad');
        $prioridadesUnicas = array_unique($prioridades);
        
        $this->assertContains('Alta', $prioridadesUnicas, 'Debe haber al menos un reclamo con prioridad Alta');
        $this->assertContains('Baja', $prioridadesUnicas, 'Debe haber al menos un reclamo con prioridad Baja');
        
        // Verificar que solo existen las prioridades válidas (Alta o Baja)
        foreach ($prioridadesUnicas as $prioridad) {
            $this->assertContains(
                $prioridad,
                ['Alta', 'Baja'],
                "La prioridad '{$prioridad}' no es válida. Solo se permiten 'Alta' o 'Baja'"
            );
        }
    }

    /**
     * HU-010: Test de filtro por estado
     * Tipo: API - Consulta - Filtro por Estado
     * 
     * Verifica que se pueden filtrar reclamos por estado:
     * - Recibido
     * - En ejecución
     * - Completado
     */
    public function testFiltrarPorEstado()
    {
        $this->markTestIncomplete(
            '❌ FUNCIONALIDAD NO IMPLEMENTADA: El endpoint GET /api/reclamos NO soporta filtrado por estado mediante query parameters. ' .
            '\n\n📍 Ubicación: App\\Controllers\\Api\\Reclamos::index()' .
            '\n\n📝 Estado Actual: El método solo ejecuta `$this->model->findAll()`, retornando TODOS los reclamos sin aplicar filtros.' .
            '\n\n✅ Solución Requerida: Implementar lógica para procesar los query parameters: ?estado=X' .
            '\n   Ejemplo: `if ($estado = $this->request->getGet(\'estado\')) { $builder->where(\'municipalidad_estado\', $estado); }`'
        );
        
        // Crear reclamos con diferentes estados
        $reclamosTest = [
            ['municipalidad_id' => '50001', 'municipalidad_estado' => 'Recibido', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test 1', 'municipalidad_fechaInicio' => '2025-11-01 08:00:00', 'municipalidad_fechaModificacion' => '2025-11-01 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000001', 'municipalidad_domicilio' => 'Calle A', 'municipalidad_numeroDomicilio' => '100', 'municipalidad_entreCalleUno' => 'Calle 1', 'municipalidad_entreCalleDos' => 'Calle 2', 'municipalidad_ciudadano' => 'Test User 1', 'municipalidad_descripcion' => 'Desc 1', 'prioridad' => 'Alta'],
            ['municipalidad_id' => '50002', 'municipalidad_estado' => 'Recibido', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test 2', 'municipalidad_fechaInicio' => '2025-11-02 08:00:00', 'municipalidad_fechaModificacion' => '2025-11-02 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000002', 'municipalidad_domicilio' => 'Calle B', 'municipalidad_numeroDomicilio' => '200', 'municipalidad_entreCalleUno' => 'Calle 3', 'municipalidad_entreCalleDos' => 'Calle 4', 'municipalidad_ciudadano' => 'Test User 2', 'municipalidad_descripcion' => 'Desc 2', 'prioridad' => 'Baja'],
            ['municipalidad_id' => '50003', 'municipalidad_estado' => 'En ejecución', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test 3', 'municipalidad_fechaInicio' => '2025-11-03 08:00:00', 'municipalidad_fechaModificacion' => '2025-11-03 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000003', 'municipalidad_domicilio' => 'Calle C', 'municipalidad_numeroDomicilio' => '300', 'municipalidad_entreCalleUno' => 'Calle 5', 'municipalidad_entreCalleDos' => 'Calle 6', 'municipalidad_ciudadano' => 'Test User 3', 'municipalidad_descripcion' => 'Desc 3', 'prioridad' => 'Alta'],
            ['municipalidad_id' => '50004', 'municipalidad_estado' => 'En ejecución', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test 4', 'municipalidad_fechaInicio' => '2025-11-04 08:00:00', 'municipalidad_fechaModificacion' => '2025-11-04 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000004', 'municipalidad_domicilio' => 'Calle D', 'municipalidad_numeroDomicilio' => '400', 'municipalidad_entreCalleUno' => 'Calle 7', 'municipalidad_entreCalleDos' => 'Calle 8', 'municipalidad_ciudadano' => 'Test User 4', 'municipalidad_descripcion' => 'Desc 4', 'prioridad' => 'Baja'],
            ['municipalidad_id' => '50005', 'municipalidad_estado' => 'Completado', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test 5', 'municipalidad_fechaInicio' => '2025-11-05 08:00:00', 'municipalidad_fechaModificacion' => '2025-11-05 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000005', 'municipalidad_domicilio' => 'Calle E', 'municipalidad_numeroDomicilio' => '500', 'municipalidad_entreCalleUno' => 'Calle 9', 'municipalidad_entreCalleDos' => 'Calle 10', 'municipalidad_ciudadano' => 'Test User 5', 'municipalidad_descripcion' => 'Desc 5', 'prioridad' => 'Alta'],
            ['municipalidad_id' => '50006', 'municipalidad_estado' => 'Completado', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test 6', 'municipalidad_fechaInicio' => '2025-11-06 08:00:00', 'municipalidad_fechaModificacion' => '2025-11-06 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000006', 'municipalidad_domicilio' => 'Calle F', 'municipalidad_numeroDomicilio' => '600', 'municipalidad_entreCalleUno' => 'Calle 11', 'municipalidad_entreCalleDos' => 'Calle 12', 'municipalidad_ciudadano' => 'Test User 6', 'municipalidad_descripcion' => 'Desc 6', 'prioridad' => 'Baja'],
        ];

        foreach ($reclamosTest as $reclamo) {
            $this->db->table('reclamo')->insert($reclamo);
        }

        // CASO 1: Filtrar por estado "Recibido"
        $result = $this->get('api/reclamos?estado=Recibido');
        $result->assertStatus(200);
        $reclamos = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($reclamos);
        $this->assertGreaterThanOrEqual(2, count($reclamos), 'Debe haber al menos 2 reclamos con estado Recibido');
        
        foreach ($reclamos as $reclamo) {
            $this->assertEquals('Recibido', $reclamo['municipalidad_estado'], 
                'Todos los reclamos filtrados deben tener estado Recibido');
        }

        // CASO 2: Filtrar por estado "En ejecución"
        $result = $this->get('api/reclamos?estado=En ejecución');
        $result->assertStatus(200);
        $reclamos = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($reclamos);
        $this->assertGreaterThanOrEqual(2, count($reclamos), 'Debe haber al menos 2 reclamos con estado En ejecución');
        
        foreach ($reclamos as $reclamo) {
            $this->assertEquals('En ejecución', $reclamo['municipalidad_estado'], 
                'Todos los reclamos filtrados deben tener estado En ejecución');
        }

        // CASO 3: Filtrar por estado "Completado"
        $result = $this->get('api/reclamos?estado=Completado');
        $result->assertStatus(200);
        $reclamos = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($reclamos);
        $this->assertGreaterThanOrEqual(2, count($reclamos), 'Debe haber al menos 2 reclamos con estado Completado');
        
        foreach ($reclamos as $reclamo) {
            $this->assertEquals('Completado', $reclamo['municipalidad_estado'], 
                'Todos los reclamos filtrados deben tener estado Completado');
        }
    }

    /**
     * HU-010: Test de filtro por prioridad
     * Tipo: API - Consulta - Filtro por Prioridad
     * 
     * Verifica que se pueden filtrar reclamos por prioridad:
     * - Alta
     * - Baja
     */
    public function testFiltrarPorPrioridad()
    {
        $this->markTestIncomplete(
            '❌ FUNCIONALIDAD NO IMPLEMENTADA: El endpoint GET /api/reclamos NO soporta filtrado por prioridad mediante query parameters. ' .
            '\n\n📍 Ubicación: App\\Controllers\\Api\\Reclamos::index()' .
            '\n\n📝 Estado Actual: El método solo ejecuta `$this->model->findAll()`, retornando TODOS los reclamos sin aplicar filtros.' .
            '\n\n✅ Solución Requerida: Implementar lógica para procesar los query parameters: ?prioridad=X' .
            '\n   Ejemplo: `if ($prioridad = $this->request->getGet(\'prioridad\')) { $builder->where(\'prioridad\', $prioridad); }`' .
            '\n\n⚠️  Nota: Solo existen 2 niveles de prioridad: "Alta" y "Baja"'
        );
        
        // Crear reclamos con diferentes prioridades
        $reclamosTest = [
            ['municipalidad_id' => '60001', 'municipalidad_estado' => 'Recibido', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test Alta 1', 'municipalidad_fechaInicio' => '2025-11-01 08:00:00', 'municipalidad_fechaModificacion' => '2025-11-01 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000001', 'municipalidad_domicilio' => 'Calle A', 'municipalidad_numeroDomicilio' => '100', 'municipalidad_entreCalleUno' => 'Calle 1', 'municipalidad_entreCalleDos' => 'Calle 2', 'municipalidad_ciudadano' => 'User Alta 1', 'municipalidad_descripcion' => 'Desc 1', 'prioridad' => 'Alta'],
            ['municipalidad_id' => '60002', 'municipalidad_estado' => 'Recibido', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test Alta 2', 'municipalidad_fechaInicio' => '2025-11-02 08:00:00', 'municipalidad_fechaModificacion' => '2025-11-02 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000002', 'municipalidad_domicilio' => 'Calle B', 'municipalidad_numeroDomicilio' => '200', 'municipalidad_entreCalleUno' => 'Calle 3', 'municipalidad_entreCalleDos' => 'Calle 4', 'municipalidad_ciudadano' => 'User Alta 2', 'municipalidad_descripcion' => 'Desc 2', 'prioridad' => 'Alta'],
            ['municipalidad_id' => '60003', 'municipalidad_estado' => 'En ejecución', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test Alta 3', 'municipalidad_fechaInicio' => '2025-11-03 08:00:00', 'municipalidad_fechaModificacion' => '2025-11-03 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000003', 'municipalidad_domicilio' => 'Calle C', 'municipalidad_numeroDomicilio' => '300', 'municipalidad_entreCalleUno' => 'Calle 5', 'municipalidad_entreCalleDos' => 'Calle 6', 'municipalidad_ciudadano' => 'User Alta 3', 'municipalidad_descripcion' => 'Desc 3', 'prioridad' => 'Alta'],
            ['municipalidad_id' => '60004', 'municipalidad_estado' => 'Recibido', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test Baja 1', 'municipalidad_fechaInicio' => '2025-11-04 08:00:00', 'municipalidad_fechaModificacion' => '2025-11-04 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000004', 'municipalidad_domicilio' => 'Calle D', 'municipalidad_numeroDomicilio' => '400', 'municipalidad_entreCalleUno' => 'Calle 7', 'municipalidad_entreCalleDos' => 'Calle 8', 'municipalidad_ciudadano' => 'User Baja 1', 'municipalidad_descripcion' => 'Desc 4', 'prioridad' => 'Baja'],
            ['municipalidad_id' => '60005', 'municipalidad_estado' => 'Completado', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test Baja 2', 'municipalidad_fechaInicio' => '2025-11-05 08:00:00', 'municipalidad_fechaModificacion' => '2025-11-05 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000005', 'municipalidad_domicilio' => 'Calle E', 'municipalidad_numeroDomicilio' => '500', 'municipalidad_entreCalleUno' => 'Calle 9', 'municipalidad_entreCalleDos' => 'Calle 10', 'municipalidad_ciudadano' => 'User Baja 2', 'municipalidad_descripcion' => 'Desc 5', 'prioridad' => 'Baja'],
        ];

        foreach ($reclamosTest as $reclamo) {
            $this->db->table('reclamo')->insert($reclamo);
        }

        // CASO 1: Filtrar por prioridad "Alta"
        $result = $this->get('api/reclamos?prioridad=Alta');
        $result->assertStatus(200);
        $reclamos = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($reclamos);
        $this->assertGreaterThanOrEqual(3, count($reclamos), 'Debe haber al menos 3 reclamos con prioridad Alta');
        
        foreach ($reclamos as $reclamo) {
            $this->assertEquals('Alta', $reclamo['prioridad'], 
                'Todos los reclamos filtrados deben tener prioridad Alta');
        }

        // CASO 2: Filtrar por prioridad "Baja"
        $result = $this->get('api/reclamos?prioridad=Baja');
        $result->assertStatus(200);
        $reclamos = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($reclamos);
        $this->assertGreaterThanOrEqual(2, count($reclamos), 'Debe haber al menos 2 reclamos con prioridad Baja');
        
        foreach ($reclamos as $reclamo) {
            $this->assertEquals('Baja', $reclamo['prioridad'], 
                'Todos los reclamos filtrados deben tener prioridad Baja');
        }
    }

    /**
     * HU-010: Test de filtro por rango de fechas
     * Tipo: API - Consulta - Filtro por Fechas
     * 
     * Verifica que se pueden filtrar reclamos por rango de fechas
     */
    public function testFiltrarPorRangoFechas()
    {
        $this->markTestIncomplete(
            '❌ FUNCIONALIDAD NO IMPLEMENTADA: El endpoint GET /api/reclamos NO soporta filtrado por rango de fechas mediante query parameters. ' .
            '\n\n📍 Ubicación: App\\Controllers\\Api\\Reclamos::index()' .
            '\n\n📝 Estado Actual: El método solo ejecuta `$this->model->findAll()`, retornando TODOS los reclamos sin aplicar filtros.' .
            '\n\n✅ Solución Requerida: Implementar lógica para procesar los query parameters: ?fecha_desde=Y-m-d&fecha_hasta=Y-m-d' .
            '\n   Ejemplo:' .
            '\n   ```php' .
            '\n   if ($fechaDesde = $this->request->getGet(\'fecha_desde\')) {' .
            '\n       $builder->where(\'municipalidad_fechaInicio >=\', $fechaDesde);' .
            '\n   }' .
            '\n   if ($fechaHasta = $this->request->getGet(\'fecha_hasta\')) {' .
            '\n       $builder->where(\'municipalidad_fechaInicio <=\', $fechaHasta . \' 23:59:59\');' .
            '\n   }' .
            '\n   ```'
        );
        
        // Crear reclamos con diferentes fechas
        $reclamosTest = [
            ['municipalidad_id' => '70001', 'municipalidad_estado' => 'Recibido', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test Enero', 'municipalidad_fechaInicio' => '2025-01-15 08:00:00', 'municipalidad_fechaModificacion' => '2025-01-15 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000001', 'municipalidad_domicilio' => 'Calle A', 'municipalidad_numeroDomicilio' => '100', 'municipalidad_entreCalleUno' => 'Calle 1', 'municipalidad_entreCalleDos' => 'Calle 2', 'municipalidad_ciudadano' => 'User 1', 'municipalidad_descripcion' => 'Desc 1', 'prioridad' => 'Alta'],
            ['municipalidad_id' => '70002', 'municipalidad_estado' => 'Recibido', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test Marzo', 'municipalidad_fechaInicio' => '2025-03-20 08:00:00', 'municipalidad_fechaModificacion' => '2025-03-20 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000002', 'municipalidad_domicilio' => 'Calle B', 'municipalidad_numeroDomicilio' => '200', 'municipalidad_entreCalleUno' => 'Calle 3', 'municipalidad_entreCalleDos' => 'Calle 4', 'municipalidad_ciudadano' => 'User 2', 'municipalidad_descripcion' => 'Desc 2', 'prioridad' => 'Baja'],
            ['municipalidad_id' => '70003', 'municipalidad_estado' => 'En ejecución', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test Junio', 'municipalidad_fechaInicio' => '2025-06-10 08:00:00', 'municipalidad_fechaModificacion' => '2025-06-10 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000003', 'municipalidad_domicilio' => 'Calle C', 'municipalidad_numeroDomicilio' => '300', 'municipalidad_entreCalleUno' => 'Calle 5', 'municipalidad_entreCalleDos' => 'Calle 6', 'municipalidad_ciudadano' => 'User 3', 'municipalidad_descripcion' => 'Desc 3', 'prioridad' => 'Alta'],
            ['municipalidad_id' => '70004', 'municipalidad_estado' => 'Completado', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test Diciembre', 'municipalidad_fechaInicio' => '2024-12-25 08:00:00', 'municipalidad_fechaModificacion' => '2024-12-25 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000004', 'municipalidad_domicilio' => 'Calle D', 'municipalidad_numeroDomicilio' => '400', 'municipalidad_entreCalleUno' => 'Calle 7', 'municipalidad_entreCalleDos' => 'Calle 8', 'municipalidad_ciudadano' => 'User 4', 'municipalidad_descripcion' => 'Desc 4', 'prioridad' => 'Baja'],
        ];

        foreach ($reclamosTest as $reclamo) {
            $this->db->table('reclamo')->insert($reclamo);
        }

        // CASO 1: Filtrar reclamos del año 2025 (debe incluir 3 reclamos)
        $result = $this->get('api/reclamos?fecha_desde=2025-01-01&fecha_hasta=2025-12-31');
        $result->assertStatus(200);
        $reclamos = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($reclamos);
        $this->assertGreaterThanOrEqual(3, count($reclamos), 'Debe haber al menos 3 reclamos en el año 2025');
        
        // Verificar que todos los reclamos están dentro del rango
        foreach ($reclamos as $reclamo) {
            $fechaInicio = strtotime($reclamo['municipalidad_fechaInicio']);
            $fechaDesde = strtotime('2025-01-01');
            $fechaHasta = strtotime('2025-12-31 23:59:59');
            
            $this->assertGreaterThanOrEqual($fechaDesde, $fechaInicio,
                "Reclamo {$reclamo['municipalidad_id']} debe tener fecha >= 2025-01-01");
            $this->assertLessThanOrEqual($fechaHasta, $fechaInicio,
                "Reclamo {$reclamo['municipalidad_id']} debe tener fecha <= 2025-12-31");
        }

        // CASO 2: Filtrar reclamos de primer trimestre 2025 (debe incluir 2 reclamos)
        $result = $this->get('api/reclamos?fecha_desde=2025-01-01&fecha_hasta=2025-03-31');
        $result->assertStatus(200);
        $reclamos = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($reclamos);
        $this->assertGreaterThanOrEqual(2, count($reclamos), 'Debe haber al menos 2 reclamos en el primer trimestre 2025');

        // CASO 3: Verificar que NO incluye reclamos fuera del rango
        $result = $this->get('api/reclamos?fecha_desde=2025-01-01&fecha_hasta=2025-12-31');
        $reclamos = json_decode($result->getJSON(), true);
        
        $tieneReclamoDiciembre2024 = false;
        foreach ($reclamos as $reclamo) {
            if ($reclamo['municipalidad_id'] === '70004') {
                $tieneReclamoDiciembre2024 = true;
                break;
            }
        }
        
        $this->assertFalse($tieneReclamoDiciembre2024, 
            'No debe incluir reclamos fuera del rango (2024-12-25)');
    }

    /**
     * HU-010: Test de filtros combinados
     * Tipo: API - Consulta - Filtros Múltiples
     * 
     * Verifica que se pueden aplicar múltiples filtros simultáneamente
     */
    public function testFiltrosCombinados()
    {
        $this->markTestIncomplete(
            '❌ FUNCIONALIDAD NO IMPLEMENTADA: El endpoint GET /api/reclamos NO soporta filtros combinados mediante query parameters. ' .
            '\n\n📍 Ubicación: App\\Controllers\\Api\\Reclamos::index()' .
            '\n\n📝 Estado Actual: El método solo ejecuta `$this->model->findAll()`, retornando TODOS los reclamos sin aplicar filtros.' .
            '\n\n✅ Solución Requerida: Implementar lógica para procesar múltiples query parameters simultáneamente' .
            '\n   Ejemplos de combinaciones a soportar:' .
            '\n   - ?estado=Recibido&prioridad=Alta' .
            '\n   - ?estado=En ejecución&fecha_desde=2025-01-01&fecha_hasta=2025-12-31' .
            '\n   - ?prioridad=Alta&fecha_desde=2025-01-01' .
            '\n   - ?estado=Recibido&prioridad=Alta&fecha_desde=2025-01-01&fecha_hasta=2025-12-31' .
            '\n\n   Código sugerido:' .
            '\n   ```php' .
            '\n   $builder = $this->model->builder();' .
            '\n   if ($estado = $this->request->getGet(\'estado\')) {' .
            '\n       $builder->where(\'municipalidad_estado\', $estado);' .
            '\n   }' .
            '\n   if ($prioridad = $this->request->getGet(\'prioridad\')) {' .
            '\n       $builder->where(\'prioridad\', $prioridad);' .
            '\n   }' .
            '\n   if ($fechaDesde = $this->request->getGet(\'fecha_desde\')) {' .
            '\n       $builder->where(\'municipalidad_fechaInicio >=\', $fechaDesde);' .
            '\n   }' .
            '\n   if ($fechaHasta = $this->request->getGet(\'fecha_hasta\')) {' .
            '\n       $builder->where(\'municipalidad_fechaInicio <=\', $fechaHasta . \' 23:59:59\');' .
            '\n   }' .
            '\n   return $this->respond($builder->get()->getResultArray());' .
            '\n   ```'
        );
        
        // Crear reclamos variados para probar combinaciones
        $reclamosTest = [
            ['municipalidad_id' => '80001', 'municipalidad_estado' => 'Recibido', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test 1', 'municipalidad_fechaInicio' => '2025-01-10 08:00:00', 'municipalidad_fechaModificacion' => '2025-01-10 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000001', 'municipalidad_domicilio' => 'Calle A', 'municipalidad_numeroDomicilio' => '100', 'municipalidad_entreCalleUno' => 'Calle 1', 'municipalidad_entreCalleDos' => 'Calle 2', 'municipalidad_ciudadano' => 'User 1', 'municipalidad_descripcion' => 'Desc 1', 'prioridad' => 'Alta'],
            ['municipalidad_id' => '80002', 'municipalidad_estado' => 'Recibido', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test 2', 'municipalidad_fechaInicio' => '2025-01-15 08:00:00', 'municipalidad_fechaModificacion' => '2025-01-15 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000002', 'municipalidad_domicilio' => 'Calle B', 'municipalidad_numeroDomicilio' => '200', 'municipalidad_entreCalleUno' => 'Calle 3', 'municipalidad_entreCalleDos' => 'Calle 4', 'municipalidad_ciudadano' => 'User 2', 'municipalidad_descripcion' => 'Desc 2', 'prioridad' => 'Baja'],
            ['municipalidad_id' => '80003', 'municipalidad_estado' => 'En ejecución', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test 3', 'municipalidad_fechaInicio' => '2025-02-05 08:00:00', 'municipalidad_fechaModificacion' => '2025-02-05 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000003', 'municipalidad_domicilio' => 'Calle C', 'municipalidad_numeroDomicilio' => '300', 'municipalidad_entreCalleUno' => 'Calle 5', 'municipalidad_entreCalleDos' => 'Calle 6', 'municipalidad_ciudadano' => 'User 3', 'municipalidad_descripcion' => 'Desc 3', 'prioridad' => 'Alta'],
            ['municipalidad_id' => '80004', 'municipalidad_estado' => 'En ejecución', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test 4', 'municipalidad_fechaInicio' => '2025-06-20 08:00:00', 'municipalidad_fechaModificacion' => '2025-06-20 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000004', 'municipalidad_domicilio' => 'Calle D', 'municipalidad_numeroDomicilio' => '400', 'municipalidad_entreCalleUno' => 'Calle 7', 'municipalidad_entreCalleDos' => 'Calle 8', 'municipalidad_ciudadano' => 'User 4', 'municipalidad_descripcion' => 'Desc 4', 'prioridad' => 'Baja'],
            ['municipalidad_id' => '80005', 'municipalidad_estado' => 'Completado', 'municipalidad_tipo' => 'ALUMBRADO PÚBLICO', 'municipalidad_motivo' => 'Test 5', 'municipalidad_fechaInicio' => '2025-01-25 08:00:00', 'municipalidad_fechaModificacion' => '2025-01-25 08:00:00', 'municipalidad_recepcion' => 'Web', 'municipalidad_telefono' => '3564000005', 'municipalidad_domicilio' => 'Calle E', 'municipalidad_numeroDomicilio' => '500', 'municipalidad_entreCalleUno' => 'Calle 9', 'municipalidad_entreCalleDos' => 'Calle 10', 'municipalidad_ciudadano' => 'User 5', 'municipalidad_descripcion' => 'Desc 5', 'prioridad' => 'Alta'],
        ];

        foreach ($reclamosTest as $reclamo) {
            $this->db->table('reclamo')->insert($reclamo);
        }

        // CASO 1: Estado + Prioridad (Recibido + Alta)
        $result = $this->get('api/reclamos?estado=Recibido&prioridad=Alta');
        $result->assertStatus(200);
        $reclamos = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($reclamos);
        $this->assertGreaterThanOrEqual(1, count($reclamos), 'Debe haber al menos 1 reclamo Recibido con prioridad Alta');
        
        foreach ($reclamos as $reclamo) {
            $this->assertEquals('Recibido', $reclamo['municipalidad_estado']);
            $this->assertEquals('Alta', $reclamo['prioridad']);
        }

        // CASO 2: Estado + Fecha (En ejecución + Primer trimestre 2025)
        $result = $this->get('api/reclamos?estado=En ejecución&fecha_desde=2025-01-01&fecha_hasta=2025-03-31');
        $result->assertStatus(200);
        $reclamos = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($reclamos);
        $this->assertGreaterThanOrEqual(1, count($reclamos), 'Debe haber al menos 1 reclamo En ejecución en Q1 2025');
        
        foreach ($reclamos as $reclamo) {
            $this->assertEquals('En ejecución', $reclamo['municipalidad_estado']);
            $fechaInicio = strtotime($reclamo['municipalidad_fechaInicio']);
            $this->assertGreaterThanOrEqual(strtotime('2025-01-01'), $fechaInicio);
            $this->assertLessThanOrEqual(strtotime('2025-03-31 23:59:59'), $fechaInicio);
        }

        // CASO 3: Prioridad + Fecha (Alta + Enero 2025)
        $result = $this->get('api/reclamos?prioridad=Alta&fecha_desde=2025-01-01&fecha_hasta=2025-01-31');
        $result->assertStatus(200);
        $reclamos = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($reclamos);
        $this->assertGreaterThanOrEqual(1, count($reclamos), 'Debe haber al menos 1 reclamo Alta en enero 2025');
        
        foreach ($reclamos as $reclamo) {
            $this->assertEquals('Alta', $reclamo['prioridad']);
            $fechaInicio = strtotime($reclamo['municipalidad_fechaInicio']);
            $this->assertGreaterThanOrEqual(strtotime('2025-01-01'), $fechaInicio);
            $this->assertLessThanOrEqual(strtotime('2025-01-31 23:59:59'), $fechaInicio);
        }

        // CASO 4: Estado + Prioridad + Fecha (Recibido + Alta + Enero 2025)
        $result = $this->get('api/reclamos?estado=Recibido&prioridad=Alta&fecha_desde=2025-01-01&fecha_hasta=2025-01-31');
        $result->assertStatus(200);
        $reclamos = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($reclamos);
        $this->assertGreaterThanOrEqual(1, count($reclamos), 'Debe haber al menos 1 reclamo Recibido + Alta en enero 2025');
        
        foreach ($reclamos as $reclamo) {
            $this->assertEquals('Recibido', $reclamo['municipalidad_estado']);
            $this->assertEquals('Alta', $reclamo['prioridad']);
            $fechaInicio = strtotime($reclamo['municipalidad_fechaInicio']);
            $this->assertGreaterThanOrEqual(strtotime('2025-01-01'), $fechaInicio);
            $this->assertLessThanOrEqual(strtotime('2025-01-31 23:59:59'), $fechaInicio);
        }
    }
}

