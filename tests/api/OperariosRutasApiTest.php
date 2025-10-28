<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\RutaModel;
use App\Models\Ruta_reclamoModel;
use App\Models\ReclamoModel;
use App\Models\CuadrillaModel;
use App\Models\CuadrillaOperariosModel;
use App\Models\UsuarioModel;
use App\Models\RolModel;

/**
 * Tests para HU-024: Visualización de lista de reclamos asignados a cuadrilla
 * y HU-025: Visualización de hoja de ruta asignada a cuadrilla
 * 
 * Tests de las funcionalidades que permiten a los operarios visualizar
 * sus reclamos asignados y sus hojas de ruta en el panel de trabajo.
 */
class OperariosRutasApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $migrateOnce = false;
    protected $refresh = true;
    protected $namespace = 'Tests\Support';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear datos de prueba necesarios
        $this->createTestData();
    }

    /**
     * Crea los datos de prueba necesarios para los tests
     */
    private function createTestData()
    {
        $db = \Config\Database::connect();
        
        // Crear roles
        $db->table('rol')->insert([
            'id' => 1,
            'nombre' => 'Operario',
            'descripcion' => 'Rol de operario'
        ]);

        // Crear operarios de prueba
        for ($i = 1; $i <= 10; $i++) {
            $db->table('usuario')->insert([
                'nombre' => "Operario $i",
                'email' => "operario$i@test.com",
                'legajo' => "OP$i",
                'rol_id' => 1
            ]);
        }

        // Crear cuadrillas
        $db->table('cuadrilla')->insert([
            'nombre' => 'Cuadrilla Norte',
            'descripcion' => 'Cuadrilla de zona norte'
        ]);

        // Asignar operarios a cuadrillas
        for ($i = 1; $i <= 3; $i++) {
            $db->table('cuadrilla_operarios')->insert([
                'cuadrilla_id' => 1,
                'usuario_id' => $i
            ]);
        }

        // Crear reclamos
        for ($i = 1; $i <= 20; $i++) {
            $db->table('reclamo')->insert([
                'municipalidad_id' => (string)(1000 + $i),
                'municipalidad_domicilio' => "Calle $i",
                'municipalidad_numeroDomicilio' => (string)$i,
                'municipalidad_estado' => ($i <= 10 ? 'Asignado' : 'Recibido'),
                'prioridad' => ($i <= 10 ? 'Alta' : 'Baja'),
                'municipalidad_motivo' => 'Motivo de prueba',
                'municipalidad_tipo' => 'Luminaria',
                'municipalidad_fechaInicio' => date('Y-m-d H:i:s'),
                'municipalidad_fechaModificacion' => date('Y-m-d H:i:s')
            ]);
        }

        // Crear ruta asignada con reclamos
        $rutaId = $db->table('ruta')->insert([
            'nombre' => 'Ruta Operario Test',
            'color' => '#FF6B35',
            'cantidadReclamos' => 5,
            'asignada' => 1,
            'cuadrilla_id' => 1,
            'tiempoEstimado' => '02:30:00',
            'fecha' => date('Y-m-d H:i:s')
        ]);

        // Asignar reclamos a la ruta
        for ($i = 1; $i <= 5; $i++) {
            $db->table('ruta_reclamo')->insert([
                'ruta_id' => $rutaId,
                'reclamo_id' => $i,
                'posicion' => $i
            ]);
        }
    }

    /**
     * Test 1: HU-024
     * Nombre: Obtener reclamos asignados a la cuadrilla del operario
     * Ubicación: tests/api/OperariosRutasApiTest.php::testObtenerReclamosAsignados
     * Objetivo: Verificar que el operario puede obtener la lista de reclamos asignados a su cuadrilla
     * Tipo de Prueba: API
     */
    public function testObtenerReclamosAsignados()
    {
        // Este test requiere autenticación (sesión) que no está implementada en tests
        // En su lugar, verificamos la funcionalidad probando directamente los modelos
        // y documentamos el comportamiento esperado

        // Simular que el usuario ID=1 está autenticado
        // En un entorno real, esto se haría con una sesión activa
        $usuarioId = 1;
        
        // Obtener las cuadrillas del operario
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $usuarioId)->findAll();
        
        $this->assertNotEmpty($asignaciones, 'El operario debe tener cuadrillas asignadas');
        
        // Obtener los IDs de cuadrillas
        $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');
        
        // Obtener las rutas asignadas a esas cuadrillas
        $rutaModel = new RutaModel();
        $rutas = $rutaModel->whereIn('cuadrilla_id', $cuadrillaIds)
                          ->where('asignada', 1)
                          ->findAll();
        
        $this->assertNotEmpty($rutas, 'Debe haber rutas asignadas a la cuadrilla');
        
        // Obtener los reclamos de esas rutas
        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamoModel = new ReclamoModel();
        
        $todosReclamos = [];
        foreach ($rutas as $ruta) {
            $reclamosRuta = $rutaReclamoModel->where('ruta_id', $ruta['id'])->findAll();
            foreach ($reclamosRuta as $rutaReclamo) {
                $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
                if ($reclamo) {
                    $reclamo['ruta_id'] = $ruta['id'];
                    $reclamo['ruta_nombre'] = $ruta['nombre'];
                    $reclamo['ruta_color'] = $ruta['color'];
                    $reclamo['posicion'] = $rutaReclamo['posicion'];
                    $todosReclamos[] = $reclamo;
                }
            }
        }
        
        // Verificar respuesta
        $this->assertGreaterThanOrEqual(5, count($todosReclamos), 
            'Debe haber al menos 5 reclamos asignados');
        
        // Verificar estructura de cada reclamo
        if (!empty($todosReclamos)) {
            $reclamo = $todosReclamos[0];
            $this->assertArrayHasKey('id', $reclamo);
            $this->assertArrayHasKey('municipalidad_id', $reclamo);
            $this->assertArrayHasKey('municipalidad_domicilio', $reclamo);
            $this->assertArrayHasKey('municipalidad_estado', $reclamo);
            $this->assertArrayHasKey('prioridad', $reclamo);
            $this->assertArrayHasKey('posicion', $reclamo);
            $this->assertArrayHasKey('ruta_id', $reclamo);
            $this->assertArrayHasKey('ruta_nombre', $reclamo);
            $this->assertArrayHasKey('ruta_color', $reclamo);
        }
    }

    /**
     * Test 2: HU-024
     * Nombre: Visualización de reclamos ordenados por posición en ruta
     * Ubicación: tests/api/OperariosRutasApiTest.php::testOrdenamientoReclamosPorPosicion
     * Objetivo: Verificar que los reclamos asignados se visualizan ordenados por su posición en la ruta
     * Tipo de Prueba: Base de Datos - Integración (HU-024)
     */
    public function testOrdenamientoReclamosPorPosicion()
    {
        $usuarioId = 1;
        
        // Obtener reclamos asignados a la cuadrilla del operario
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $usuarioId)->findAll();
        $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');
        
        $rutaModel = new RutaModel();
        $rutas = $rutaModel->whereIn('cuadrilla_id', $cuadrillaIds)
                          ->where('asignada', 1)
                          ->findAll();
        
        $this->assertNotEmpty($rutas, 'Debe haber rutas asignadas');
        
        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamoModel = new ReclamoModel();
        
        // Para cada ruta, verificar que los reclamos están ordenados por posición
        foreach ($rutas as $ruta) {
            $reclamosRuta = $rutaReclamoModel->where('ruta_id', $ruta['id'])
                                            ->orderBy('posicion', 'ASC')
                                            ->findAll();
            
            $posiciones = array_column($reclamosRuta, 'posicion');
            $posicionesEsperadas = range(1, count($posiciones));
            
            // Verificar que las posiciones son secuenciales desde 1
            $this->assertEquals($posicionesEsperadas, $posiciones, 
                "Las posiciones de los reclamos deben ser secuenciales 1, 2, 3, ... en la ruta {$ruta['id']}");
        }
    }

    /**
     * Test 3: HU-024
     * Nombre: Verificación de campos requeridos en reclamos asignados
     * Ubicación: tests/api/OperariosRutasApiTest.php::testCamposRequeridosReclamos
     * Objetivo: Verificar que cada reclamo asignado contiene todos los campos requeridos por HU-024
     * Tipo de Prueba: Base de Datos - Integración (HU-024)
     */
    public function testCamposRequeridosReclamos()
    {
        $usuarioId = 1;
        
        // Obtener reclamos asignados
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $usuarioId)->findAll();
        $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');
        
        $rutaModel = new RutaModel();
        $rutas = $rutaModel->whereIn('cuadrilla_id', $cuadrillaIds)
                          ->where('asignada', 1)
                          ->findAll();
        
        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamoModel = new ReclamoModel();
        
        foreach ($rutas as $ruta) {
            $reclamosRuta = $rutaReclamoModel->where('ruta_id', $ruta['id'])->findAll();
            
            foreach ($reclamosRuta as $rutaReclamo) {
                $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
                
                // Verificar campos requeridos según HU-024
                $this->assertArrayHasKey('municipalidad_id', $reclamo, 'ID municipal requerido');
                $this->assertArrayHasKey('municipalidad_motivo', $reclamo, 'Motivo requerido');
                $this->assertArrayHasKey('municipalidad_domicilio', $reclamo, 'Domicilio requerido');
                $this->assertArrayHasKey('prioridad', $reclamo, 'Prioridad requerida');
                $this->assertArrayHasKey('municipalidad_estado', $reclamo, 'Estado requerido');
            }
        }
    }

    /**
     * Test 4: HU-024
     * Nombre: Verificación de prioridad en reclamos asignados
     * Ubicación: tests/api/OperariosRutasApiTest.php::testVerificacionPrioridadReclamos
     * Objetivo: Verificar que los reclamos asignados tienen una prioridad válida (Alta/Media/Baja)
     * Tipo de Prueba: Base de Datos - Integración (HU-024)
     */
    public function testVerificacionPrioridadReclamos()
    {
        $usuarioId = 1;
        
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $usuarioId)->findAll();
        $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');
        
        $rutaModel = new RutaModel();
        $rutas = $rutaModel->whereIn('cuadrilla_id', $cuadrillaIds)
                          ->where('asignada', 1)
                          ->findAll();
        
        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamoModel = new ReclamoModel();
        
        $prioridadesValidas = ['Alta', 'Media', 'Baja'];
        
        foreach ($rutas as $ruta) {
            $reclamosRuta = $rutaReclamoModel->where('ruta_id', $ruta['id'])->findAll();
            
            foreach ($reclamosRuta as $rutaReclamo) {
                $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
                
                $this->assertNotNull($reclamo['prioridad'], 'La prioridad no debe ser null');
                $this->assertContains($reclamo['prioridad'], $prioridadesValidas, 
                    "La prioridad debe ser Alta, Media o Baja, pero es: {$reclamo['prioridad']}");
            }
        }
    }

    /**
     * Test 5: HU-025
     * Nombre: Obtener hojas de ruta asignadas a cuadrilla del operario
     * Ubicación: tests/api/OperariosRutasApiTest.php::testObtenerHojasRutaAsignadas
     * Objetivo: Verificar que el operario puede visualizar las hojas de ruta asignadas a su cuadrilla
     * Tipo de Prueba: Base de Datos - Integración (HU-025)
     */
    public function testObtenerHojasRutaAsignadas()
    {
        $usuarioId = 1;
        
        // Obtener las cuadrillas del operario
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $usuarioId)->findAll();
        
        $this->assertNotEmpty($asignaciones, 'El operario debe tener cuadrillas asignadas');
        
        // Obtener las rutas asignadas a esas cuadrillas
        $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');
        $rutaModel = new RutaModel();
        
        $rutas = $rutaModel->whereIn('cuadrilla_id', $cuadrillaIds)
                          ->where('asignada', 1)
                          ->findAll();
        
        $this->assertNotEmpty($rutas, 'Debe haber rutas asignadas a la cuadrilla');
        
        // Verificar estructura de las rutas
        foreach ($rutas as $ruta) {
            $this->assertArrayHasKey('id', $ruta);
            $this->assertArrayHasKey('nombre', $ruta);
            $this->assertArrayHasKey('color', $ruta);
            $this->assertArrayHasKey('cantidadReclamos', $ruta);
            $this->assertArrayHasKey('asignada', $ruta);
            $this->assertEquals(1, $ruta['asignada'], 'La ruta debe estar asignada');
        }
    }

    /**
     * Test 6: HU-025
     * Nombre: Verificar estructura completa de hoja de ruta
     * Ubicación: tests/api/OperariosRutasApiTest.php::testEstructuraCompletaHojaRuta
     * Objetivo: Verificar que las hojas de ruta contienen todos los campos necesarios para visualización
     * Tipo de Prueba: Base de Datos - Integración (HU-025)
     */
    public function testEstructuraCompletaHojaRuta()
    {
        $usuarioId = 1;
        
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $usuarioId)->findAll();
        $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');
        
        $rutaModel = new RutaModel();
        $rutas = $rutaModel->whereIn('cuadrilla_id', $cuadrillaIds)
                          ->where('asignada', 1)
                          ->findAll();
        
        foreach ($rutas as $ruta) {
            // Verificar campos esenciales según HU-025
            $this->assertArrayHasKey('id', $ruta);
            $this->assertArrayHasKey('nombre', $ruta);
            $this->assertArrayHasKey('color', $ruta);
            $this->assertArrayHasKey('cantidadReclamos', $ruta);
            $this->assertArrayHasKey('asignada', $ruta);
            $this->assertArrayHasKey('cuadrilla_id', $ruta);
            $this->assertArrayHasKey('tiempoEstimado', $ruta);
            
            // Verificar que la cantidad de reclamos coincide
            $this->assertGreaterThan(0, $ruta['cantidadReclamos'], 
                'La ruta debe tener reclamos asignados');
        }
    }

    /**
     * Test 7: HU-025
     * Nombre: Verificar relación ruta-cuadrilla-operario
     * Ubicación: tests/api/OperariosRutasApiTest.php::testRelacionRutaCuadrillaOperario
     * Objetivo: Verificar que la relación entre ruta, cuadrilla y operario es correcta
     * Tipo de Prueba: Base de Datos - Integración (HU-025)
     */
    public function testRelacionRutaCuadrillaOperario()
    {
        $usuarioId = 1;
        
        // Obtener cuadrillas del operario
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $usuarioId)->findAll();
        $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');
        
        // Obtener rutas asignadas a esas cuadrillas
        $rutaModel = new RutaModel();
        $rutas = $rutaModel->whereIn('cuadrilla_id', $cuadrillaIds)
                          ->where('asignada', 1)
                          ->findAll();
        
        foreach ($rutas as $ruta) {
            // Verificar que el cuadrilla_id de la ruta está en las cuadrillas del operario
            $this->assertContains($ruta['cuadrilla_id'], $cuadrillaIds, 
                'La ruta debe pertenecer a una cuadrilla del operario');
            
            // Verificar que la ruta está asignada
            $this->assertEquals(1, $ruta['asignada'], 
                'La ruta debe estar marcada como asignada');
        }
    }

    /**
     * Test 8: HU-025
     * Nombre: Verificar reclamos de una hoja de ruta específica
     * Ubicación: tests/api/OperariosRutasApiTest.php::testReclamosHojaRutaEspecifica
     * Objetivo: Verificar que se pueden obtener los reclamos de una hoja de ruta específica
     * Tipo de Prueba: Base de Datos - Integración (HU-025)
     */
    public function testReclamosHojaRutaEspecifica()
    {
        $usuarioId = 1;
        
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $usuarioId)->findAll();
        $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');
        
        $rutaModel = new RutaModel();
        $rutas = $rutaModel->whereIn('cuadrilla_id', $cuadrillaIds)
                          ->where('asignada', 1)
                          ->findAll();
        
        foreach ($rutas as $ruta) {
            // Obtener reclamos de esta ruta
            $rutaReclamoModel = new Ruta_reclamoModel();
            $reclamosRuta = $rutaReclamoModel->where('ruta_id', $ruta['id'])->findAll();
            
            // Verificar que la cantidad coincide
            $this->assertCount($ruta['cantidadReclamos'], $reclamosRuta, 
                "La cantidad de reclamos en BD debe coincidir con cantidadReclamos de la ruta");
            
            // Verificar que cada reclamo existe
            $reclamoModel = new ReclamoModel();
            foreach ($reclamosRuta as $rutaReclamo) {
                $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
                $this->assertNotNull($reclamo, 
                    "El reclamo ID {$rutaReclamo['reclamo_id']} debe existir");
            }
        }
    }

    /**
     * Test 9: HU-025
     * Nombre: Verificar orden de reclamos en hoja de ruta
     * Ubicación: tests/api/OperariosRutasApiTest.php::testOrdenReclamosHojaRuta
     * Objetivo: Verificar que los reclamos en una hoja de ruta mantienen su orden de visita
     * Tipo de Prueba: Base de Datos - Integración (HU-025)
     */
    public function testOrdenReclamosHojaRuta()
    {
        $usuarioId = 1;
        
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $usuarioId)->findAll();
        $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');
        
        $rutaModel = new RutaModel();
        $rutas = $rutaModel->whereIn('cuadrilla_id', $cuadrillaIds)
                          ->where('asignada', 1)
                          ->findAll();
        
        foreach ($rutas as $ruta) {
            $rutaReclamoModel = new Ruta_reclamoModel();
            $reclamosRuta = $rutaReclamoModel->where('ruta_id', $ruta['id'])
                                            ->orderBy('posicion', 'ASC')
                                            ->findAll();
            
            // Verificar que están ordenados
            $posiciones = array_column($reclamosRuta, 'posicion');
            $posicionesOrdenadas = array_values($posiciones);
            
            $this->assertEquals(range(1, count($posiciones)), $posicionesOrdenadas, 
                'Las posiciones deben estar en orden ascendente desde 1');
        }
    }

    /**
     * Test 10: HU-024
     * Nombre: Verificar estados válidos de reclamos asignados
     * Ubicación: tests/api/OperariosRutasApiTest.php::testEstadosValidosReclamos
     * Objetivo: Verificar que los reclamos asignados tienen estados válidos según HU-024
     * Tipo de Prueba: Base de Datos - Integración (HU-024)
     */
    public function testEstadosValidosReclamos()
    {
        $usuarioId = 1;
        
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $usuarioId)->findAll();
        $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');
        
        $rutaModel = new RutaModel();
        $rutas = $rutaModel->whereIn('cuadrilla_id', $cuadrillaIds)
                          ->where('asignada', 1)
                          ->findAll();
        
        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamoModel = new ReclamoModel();
        
        $estadosValidos = ['Recibido', 'Asignado', 'En ejecución', 'Completado'];
        
        foreach ($rutas as $ruta) {
            $reclamosRuta = $rutaReclamoModel->where('ruta_id', $ruta['id'])->findAll();
            
            foreach ($reclamosRuta as $rutaReclamo) {
                $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
                
                $this->assertArrayHasKey('municipalidad_estado', $reclamo);
                $this->assertNotNull($reclamo['municipalidad_estado']);
                $this->assertContains($reclamo['municipalidad_estado'], $estadosValidos, 
                    "El estado debe ser uno de: " . implode(', ', $estadosValidos));
            }
        }
    }
}

