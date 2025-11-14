<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

class MaterialesApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'Tests\Support';
    protected $seed        = 'Tests\Support\Database\Seeds\TestSeeder';

    /**
     * HU-030 - Prueba 1: Carga Manual Exitosa
     * 
     * Objetivo: Verificar que se puede crear un material manualmente con todos los campos válidos
     * Validar:
     * - POST /api/materiales retorna 201
     * - Se genera ID único automáticamente
     * - Datos se guardan correctamente en BD
     * - Material puede ser recuperado con GET
     */
    public function testCargaManualExitosa()
    {
        // Datos del material a crear
        $materialData = [
            'nombre' => 'Lámpara LED 50W',
            'idTipo' => 1, // Lámpara LED (del seeder)
            'cantidad' => 100
        ];

        // Paso 1: Crear material via POST (enviando como JSON)
        $result = $this->withBodyFormat('json')->post('api/materiales', $materialData);

        // Validación 1: Status 201 (Created)
        $result->assertStatus(201);

        // Validación 2: Response contiene el material creado
        $responseData = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($responseData, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('id', $responseData, 'El material debe tener un ID generado automáticamente');
        
        // Validación 3: El ID es un número válido
        $this->assertIsNumeric($responseData['id'], 'El ID debe ser numérico');
        $this->assertGreaterThan(0, $responseData['id'], 'El ID debe ser mayor a 0');
        
        $materialId = $responseData['id'];

        // Validación 4: Los datos se guardaron correctamente
        $this->assertEquals($materialData['nombre'], $responseData['nombre'], 'El nombre debe coincidir');
        $this->assertEquals($materialData['idTipo'], $responseData['idTipo'], 'El tipo debe coincidir');
        $this->assertEquals($materialData['cantidad'], $responseData['cantidad'], 'La cantidad debe coincidir');

        // Paso 2: Verificar que el material se guardó en la BD consultando directamente
        $materialModel = model('MaterialModel');
        $materialEnBD = $materialModel->find($materialId);

        $this->assertNotNull($materialEnBD, 'El material debe existir en la base de datos');
        $this->assertEquals($materialData['nombre'], $materialEnBD['nombre'], 'El nombre en BD debe coincidir');
        $this->assertEquals($materialData['idTipo'], $materialEnBD['idTipo'], 'El tipo en BD debe coincidir');
        $this->assertEquals($materialData['cantidad'], $materialEnBD['cantidad'], 'La cantidad en BD debe coincidir');

        // Paso 3: Verificar que el material puede ser recuperado via GET
        $resultGet = $this->get("api/materiales");
        $resultGet->assertStatus(200);

        $materialesObtenidos = json_decode($resultGet->getJSON(), true);
        $this->assertIsArray($materialesObtenidos, 'La respuesta debe ser un array');

        // Buscar el material creado en la lista
        $materialEncontrado = null;
        foreach ($materialesObtenidos as $material) {
            if ($material['id'] == $materialId) {
                $materialEncontrado = $material;
                break;
            }
        }

        $this->assertNotNull($materialEncontrado, 'El material creado debe estar en la lista de materiales');
        $this->assertEquals($materialData['nombre'], $materialEncontrado['nombre'], 'El nombre recuperado debe coincidir');
        
        // El GET incluye el nombre del tipo (join), validar que existe
        $this->assertArrayHasKey('tipo_nombre', $materialEncontrado, 'El material debe incluir el nombre del tipo');
        $this->assertEquals('Lámpara LED', $materialEncontrado['tipo_nombre'], 'El nombre del tipo debe ser correcto');
    }

    /**
     * HU-030 - Prueba 2: Validación de Campos Obligatorios
     * 
     * Objetivo: Verificar que el sistema valida correctamente los campos requeridos
     * Validar:
     * - Nombre vacío → 400
     * - Tipo de material vacío/inválido → 400
     * - Cantidad negativa → 400
     * - Mensajes de error claros
     */
    public function testValidacionCamposObligatorios()
    {
        // Caso 1: Nombre vacío
        $dataNombreVacio = [
            'nombre' => '',
            'idTipo' => 1,
            'cantidad' => 50
        ];

        $result1 = $this->withBodyFormat('json')->post('api/materiales', $dataNombreVacio);
        $result1->assertStatus(400);
        
        $response1 = json_decode($result1->getJSON(), true);
        $this->assertArrayHasKey('messages', $response1, 'La respuesta debe contener mensajes de error');
        $this->assertStringContainsString('nombre', strtolower($result1->getJSON()), 'El mensaje de error debe mencionar el campo nombre');

        // Caso 2: Nombre null (omitido)
        $dataSinNombre = [
            'idTipo' => 1,
            'cantidad' => 50
        ];

        $result2 = $this->withBodyFormat('json')->post('api/materiales', $dataSinNombre);
        $result2->assertStatus(400);
        
        $response2 = json_decode($result2->getJSON(), true);
        $this->assertArrayHasKey('messages', $response2, 'La respuesta debe contener mensajes de error');
        $this->assertStringContainsString('obligatorio', strtolower($result2->getJSON()), 'El mensaje debe indicar que faltan datos obligatorios');

        // Caso 3: Tipo de material vacío (idTipo = 0)
        $dataTipoInvalido = [
            'nombre' => 'Material Test',
            'idTipo' => 0,
            'cantidad' => 50
        ];

        $result3 = $this->withBodyFormat('json')->post('api/materiales', $dataTipoInvalido);
        $result3->assertStatus(400);
        
        $response3 = json_decode($result3->getJSON(), true);
        $this->assertArrayHasKey('messages', $response3, 'La respuesta debe contener mensajes de error');
        $this->assertStringContainsString('tipo', strtolower($result3->getJSON()), 'El mensaje debe mencionar el tipo de material');

        // Caso 4: Tipo de material negativo (inválido)
        $dataTipoNegativo = [
            'nombre' => 'Material Test',
            'idTipo' => -1,
            'cantidad' => 50
        ];

        $result4 = $this->withBodyFormat('json')->post('api/materiales', $dataTipoNegativo);
        $result4->assertStatus(400);
        
        $response4 = json_decode($result4->getJSON(), true);
        $this->assertArrayHasKey('messages', $response4, 'La respuesta debe contener mensajes de error');

        // Caso 5: Sin idTipo (omitido)
        $dataSinTipo = [
            'nombre' => 'Material Test',
            'cantidad' => 50
        ];

        $result5 = $this->withBodyFormat('json')->post('api/materiales', $dataSinTipo);
        $result5->assertStatus(400);
        
        $response5 = json_decode($result5->getJSON(), true);
        $this->assertArrayHasKey('messages', $response5, 'La respuesta debe contener mensajes de error');
        $this->assertStringContainsString('obligatorio', strtolower($result5->getJSON()), 'El mensaje debe indicar que faltan datos obligatorios');

        // Caso 6: Cantidad negativa
        $dataCantidadNegativa = [
            'nombre' => 'Material Test',
            'idTipo' => 1,
            'cantidad' => -10
        ];

        $result6 = $this->withBodyFormat('json')->post('api/materiales', $dataCantidadNegativa);
        $result6->assertStatus(400);
        
        $response6 = json_decode($result6->getJSON(), true);
        $this->assertArrayHasKey('messages', $response6, 'La respuesta debe contener mensajes de error');
        $this->assertStringContainsString('cantidad', strtolower($result6->getJSON()), 'El mensaje debe mencionar la cantidad');

        // Caso 7: Sin cantidad (omitida)
        $dataSinCantidad = [
            'nombre' => 'Material Test',
            'idTipo' => 1
        ];

        $result7 = $this->withBodyFormat('json')->post('api/materiales', $dataSinCantidad);
        $result7->assertStatus(400);
        
        $response7 = json_decode($result7->getJSON(), true);
        $this->assertArrayHasKey('messages', $response7, 'La respuesta debe contener mensajes de error');
        $this->assertStringContainsString('obligatorio', strtolower($result7->getJSON()), 'El mensaje debe indicar que faltan datos obligatorios');

        // Caso 8: Todos los campos vacíos/inválidos
        $dataTodoInvalido = [
            'nombre' => '',
            'idTipo' => 0,
            'cantidad' => -5
        ];

        $result8 = $this->withBodyFormat('json')->post('api/materiales', $dataTodoInvalido);
        $result8->assertStatus(400);
        
        $response8 = json_decode($result8->getJSON(), true);
        $this->assertArrayHasKey('messages', $response8, 'La respuesta debe contener mensajes de error');

        // Verificar que NO se creó ningún material inválido en la BD
        $materialModel = model('MaterialModel');
        $todosLosMateriales = $materialModel->findAll();
        
        // Solo debe haber 0 materiales (ninguno de los inválidos se guardó)
        $this->assertCount(0, $todosLosMateriales, 'No debe haber materiales creados con datos inválidos');
    }

    /**
     * HU-030 - Prueba 3: Obtención del Catálogo Completo
     * 
     * Objetivo: Verificar que se puede obtener la lista completa de materiales disponibles
     * Validar:
     * - GET /api/materiales retorna 200
     * - Retorna array de materiales
     * - Incluye todos los campos necesarios
     * - Incluye el nombre del tipo asociado (join)
     */
    public function testObtenerCatalogoCompleto()
    {
        // Paso 1: Crear varios materiales de diferentes tipos para tener un catálogo variado
        $materialesACrear = [
            [
                'nombre' => 'Lámpara LED 50W',
                'idTipo' => 1, // Lámpara LED
                'cantidad' => 100
            ],
            [
                'nombre' => 'Lámpara LED 100W',
                'idTipo' => 1, // Lámpara LED
                'cantidad' => 50
            ],
            [
                'nombre' => 'Lámpara de Sodio 150W',
                'idTipo' => 2, // Lámpara de Sodio
                'cantidad' => 75
            ],
            [
                'nombre' => 'Cable Eléctrico 2x1.5mm',
                'idTipo' => 3, // Cable Eléctrico
                'cantidad' => 200
            ],
            [
                'nombre' => 'Poste de Concreto 8m',
                'idTipo' => 4, // Poste
                'cantidad' => 25
            ]
        ];

        // Crear los materiales
        $idsCreados = [];
        foreach ($materialesACrear as $materialData) {
            $result = $this->withBodyFormat('json')->post('api/materiales', $materialData);
            $result->assertStatus(201);
            
            $responseData = json_decode($result->getJSON(), true);
            $idsCreados[] = $responseData['id'];
        }

        // Paso 2: Obtener el catálogo completo via GET
        $resultGet = $this->get('api/materiales');

        // Validación 1: Status 200 (OK)
        $resultGet->assertStatus(200);

        // Validación 2: La respuesta es un array
        $catalogoCompleto = json_decode($resultGet->getJSON(), true);
        $this->assertIsArray($catalogoCompleto, 'La respuesta debe ser un array');

        // Validación 3: El array contiene los 5 materiales creados
        $this->assertCount(5, $catalogoCompleto, 'El catálogo debe contener los 5 materiales creados');

        // Validación 4: Cada material incluye TODOS los campos necesarios
        $camposRequeridos = ['id', 'nombre', 'idTipo', 'cantidad', 'tipo_nombre'];
        
        foreach ($catalogoCompleto as $material) {
            // Verificar que es un array (no null u otro tipo)
            $this->assertIsArray($material, 'Cada material debe ser un array');
            
            // Verificar que contiene todos los campos requeridos
            foreach ($camposRequeridos as $campo) {
                $this->assertArrayHasKey($campo, $material, "El material debe incluir el campo '{$campo}'");
            }
            
            // Validaciones adicionales de tipo de datos
            $this->assertIsNumeric($material['id'], 'El ID debe ser numérico');
            $this->assertIsString($material['nombre'], 'El nombre debe ser string');
            $this->assertIsNumeric($material['idTipo'], 'El idTipo debe ser numérico');
            $this->assertIsNumeric($material['cantidad'], 'La cantidad debe ser numérica');
            $this->assertIsString($material['tipo_nombre'], 'El tipo_nombre debe ser string');
            
            // Validar que los valores no están vacíos
            $this->assertNotEmpty($material['nombre'], 'El nombre no debe estar vacío');
            $this->assertNotEmpty($material['tipo_nombre'], 'El tipo_nombre no debe estar vacío');
            $this->assertGreaterThanOrEqual(0, $material['cantidad'], 'La cantidad debe ser >= 0');
        }

        // Validación 5: Verificar que todos los IDs creados están en el catálogo
        $idsCatalogo = array_column($catalogoCompleto, 'id');
        
        foreach ($idsCreados as $idCreado) {
            $this->assertContains(
                $idCreado, 
                $idsCatalogo, 
                "El material con ID {$idCreado} debe estar en el catálogo"
            );
        }

        // Validación 6: Verificar que los tipos de materiales están correctamente asociados
        $tiposEncontrados = array_unique(array_column($catalogoCompleto, 'tipo_nombre'));
        
        // Debe haber al menos los 4 tipos que usamos
        $this->assertGreaterThanOrEqual(4, count($tiposEncontrados), 'Debe haber al menos 4 tipos diferentes');
        
        // Verificar que los tipos específicos están presentes
        $nombresTiposEncontrados = array_column($catalogoCompleto, 'tipo_nombre');
        $this->assertContains('Lámpara LED', $nombresTiposEncontrados, 'Debe incluir el tipo Lámpara LED');
        $this->assertContains('Lámpara de Sodio', $nombresTiposEncontrados, 'Debe incluir el tipo Lámpara de Sodio');
        $this->assertContains('Cable Eléctrico', $nombresTiposEncontrados, 'Debe incluir el tipo Cable Eléctrico');
        $this->assertContains('Poste', $nombresTiposEncontrados, 'Debe incluir el tipo Poste');

        // Validación 7: Verificar que los materiales del mismo tipo tienen el mismo tipo_nombre
        $materialesLED = array_filter($catalogoCompleto, function($m) {
            return $m['idTipo'] == 1;
        });
        
        foreach ($materialesLED as $materialLED) {
            $this->assertEquals('Lámpara LED', $materialLED['tipo_nombre'], 'Todos los materiales con idTipo=1 deben tener tipo_nombre="Lámpara LED"');
        }
    }

    /**
     * HU-030 - Prueba 4: Actualización de Material Existente
     * 
     * Objetivo: Verificar que se puede modificar un material existente
     * Validar:
     * - PUT /api/materiales/{id} retorna 200
     * - Campos modificados se actualizan correctamente
     * - Campos no modificados permanecen sin cambios
     * - ID no cambia después de la actualización
     */
    public function testActualizacionMaterialExistente()
    {
        // Paso 1: Crear un material inicial
        $materialInicial = [
            'nombre' => 'Lámpara LED Original 50W',
            'idTipo' => 1, // Lámpara LED
            'cantidad' => 100
        ];

        $resultCreacion = $this->withBodyFormat('json')->post('api/materiales', $materialInicial);
        $resultCreacion->assertStatus(201);

        $materialCreado = json_decode($resultCreacion->getJSON(), true);
        $materialId = $materialCreado['id'];

        // Verificar datos iniciales
        $this->assertEquals($materialInicial['nombre'], $materialCreado['nombre']);
        $this->assertEquals($materialInicial['idTipo'], $materialCreado['idTipo']);
        $this->assertEquals($materialInicial['cantidad'], $materialCreado['cantidad']);

        // Paso 2: Actualizar solo algunos campos (nombre y cantidad)
        $datosActualizacion = [
            'nombre' => 'Lámpara LED Actualizada 100W',
            'cantidad' => 150
            // NO se envía idTipo, debe permanecer sin cambios
        ];

        $resultActualizacion = $this->withBodyFormat('json')->put("api/materiales/{$materialId}", $datosActualizacion);

        // Validación 1: Status 200 (OK)
        $resultActualizacion->assertStatus(200);

        // Validación 2: La respuesta contiene el material actualizado
        $materialActualizado = json_decode($resultActualizacion->getJSON(), true);
        
        $this->assertIsArray($materialActualizado, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('id', $materialActualizado);
        $this->assertArrayHasKey('nombre', $materialActualizado);
        $this->assertArrayHasKey('cantidad', $materialActualizado);
        $this->assertArrayHasKey('idTipo', $materialActualizado);

        // Validación 3: El ID NO cambió
        $this->assertEquals($materialId, $materialActualizado['id'], 'El ID no debe cambiar después de la actualización');

        // Validación 4: Los campos modificados se actualizaron correctamente
        $this->assertEquals($datosActualizacion['nombre'], $materialActualizado['nombre'], 'El nombre debe estar actualizado');
        $this->assertEquals($datosActualizacion['cantidad'], $materialActualizado['cantidad'], 'La cantidad debe estar actualizada');

        // Validación 5: Los campos NO modificados permanecen sin cambios
        $this->assertEquals($materialInicial['idTipo'], $materialActualizado['idTipo'], 'El idTipo no debe haber cambiado');

        // Paso 3: Verificar en la BD que los cambios persisten
        $materialModel = model('MaterialModel');
        $materialEnBD = $materialModel->find($materialId);

        $this->assertNotNull($materialEnBD, 'El material debe existir en la BD');
        $this->assertEquals($materialId, $materialEnBD['id'], 'El ID en BD debe coincidir');
        $this->assertEquals($datosActualizacion['nombre'], $materialEnBD['nombre'], 'El nombre en BD debe estar actualizado');
        $this->assertEquals($datosActualizacion['cantidad'], $materialEnBD['cantidad'], 'La cantidad en BD debe estar actualizada');
        $this->assertEquals($materialInicial['idTipo'], $materialEnBD['idTipo'], 'El idTipo en BD no debe haber cambiado');

        // Paso 4: Actualizar otro conjunto de campos (tipo y cantidad, no nombre)
        $segundaActualizacion = [
            'idTipo' => 2, // Cambiar a Lámpara de Sodio
            'cantidad' => 200
            // NO se envía nombre, debe permanecer como "Lámpara LED Actualizada 100W"
        ];

        $resultSegundaActualizacion = $this->withBodyFormat('json')->put("api/materiales/{$materialId}", $segundaActualizacion);
        $resultSegundaActualizacion->assertStatus(200);

        $materialDosVecesActualizado = json_decode($resultSegundaActualizacion->getJSON(), true);

        // Validación 6: El ID sigue sin cambiar
        $this->assertEquals($materialId, $materialDosVecesActualizado['id'], 'El ID no debe cambiar en la segunda actualización');

        // Validación 7: Los campos de la segunda actualización se aplicaron
        $this->assertEquals($segundaActualizacion['idTipo'], $materialDosVecesActualizado['idTipo'], 'El idTipo debe estar actualizado');
        $this->assertEquals($segundaActualizacion['cantidad'], $materialDosVecesActualizado['cantidad'], 'La cantidad debe estar actualizada nuevamente');

        // Validación 8: El nombre de la primera actualización se mantiene
        $this->assertEquals($datosActualizacion['nombre'], $materialDosVecesActualizado['nombre'], 'El nombre de la primera actualización debe mantenerse');

        // Paso 5: Actualizar todos los campos a la vez
        $actualizacionCompleta = [
            'nombre' => 'Material Completamente Actualizado',
            'idTipo' => 3, // Cable Eléctrico
            'cantidad' => 300
        ];

        $resultActualizacionCompleta = $this->withBodyFormat('json')->put("api/materiales/{$materialId}", $actualizacionCompleta);
        $resultActualizacionCompleta->assertStatus(200);

        $materialFinal = json_decode($resultActualizacionCompleta->getJSON(), true);

        // Validación 9: El ID permanece constante
        $this->assertEquals($materialId, $materialFinal['id'], 'El ID debe permanecer constante a través de todas las actualizaciones');

        // Validación 10: Todos los campos se actualizaron correctamente
        $this->assertEquals($actualizacionCompleta['nombre'], $materialFinal['nombre']);
        $this->assertEquals($actualizacionCompleta['idTipo'], $materialFinal['idTipo']);
        $this->assertEquals($actualizacionCompleta['cantidad'], $materialFinal['cantidad']);

        // Paso 6: Verificar via GET que el material tiene los datos finales
        $resultGet = $this->get("api/materiales");
        $resultGet->assertStatus(200);

        $catalogo = json_decode($resultGet->getJSON(), true);
        $materialEnCatalogo = null;
        
        foreach ($catalogo as $mat) {
            if ($mat['id'] == $materialId) {
                $materialEnCatalogo = $mat;
                break;
            }
        }

        $this->assertNotNull($materialEnCatalogo, 'El material debe estar en el catálogo');
        $this->assertEquals($actualizacionCompleta['nombre'], $materialEnCatalogo['nombre']);
        $this->assertEquals($actualizacionCompleta['idTipo'], $materialEnCatalogo['idTipo']);
        $this->assertEquals($actualizacionCompleta['cantidad'], $materialEnCatalogo['cantidad']);
        $this->assertEquals('Cable Eléctrico', $materialEnCatalogo['tipo_nombre'], 'El tipo_nombre debe reflejar el tipo actualizado');
    }

    /**
     * HU-030 - Prueba 5: Importación CSV/Masiva Exitosa
     * 
     * Objetivo: Verificar que se pueden importar múltiples materiales desde un formato tipo CSV
     * Validar:
     * - POST /api/materiales/import con datos válidos
     * - Status 200
     * - Todos los materiales se crean correctamente
     * - Retorna resumen con cantidad de materiales importados
     * - Los tipos se mapean correctamente por nombre
     */
    public function testImportacionCSVExitosa()
    {
        // Paso 1: Preparar datos de importación (simulando un CSV parseado)
        // El formato esperado es un array de items con: nombre, cantidad, tipo (nombre del tipo, no ID)
        $datosImportacion = [
            'items' => [
                [
                    'nombre' => 'Lámpara LED 60W Importada',
                    'cantidad' => 50,
                    'tipo' => 'Lámpara LED' // Se usa el nombre del tipo, no el ID
                ],
                [
                    'nombre' => 'Lámpara LED 100W Importada',
                    'cantidad' => 30,
                    'tipo' => 'Lámpara LED'
                ],
                [
                    'nombre' => 'Lámpara Sodio 250W Importada',
                    'cantidad' => 40,
                    'tipo' => 'Lámpara de Sodio'
                ],
                [
                    'nombre' => 'Cable 3x2.5mm Importado',
                    'cantidad' => 500,
                    'tipo' => 'Cable Eléctrico'
                ],
                [
                    'nombre' => 'Poste Metálico 10m Importado',
                    'cantidad' => 15,
                    'tipo' => 'Poste'
                ],
                [
                    'nombre' => 'Lámpara LED 150W Importada',
                    'cantidad' => 25,
                    'tipo' => 'Lámpara LED'
                ]
            ]
        ];

        // Paso 2: Realizar la importación
        $result = $this->withBodyFormat('json')->post('api/materiales/import', $datosImportacion);

        // Validación 1: Status 200 (OK) - La importación fue procesada
        $result->assertStatus(200);

        // Validación 2: La respuesta contiene un resumen de importación
        $responseData = json_decode($result->getJSON(), true);
        
        $this->assertIsArray($responseData, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('mensaje', $responseData, 'La respuesta debe contener un mensaje');
        $this->assertArrayHasKey('insertados', $responseData, 'La respuesta debe indicar cuántos materiales se insertaron');
        $this->assertArrayHasKey('errores', $responseData, 'La respuesta debe contener el array de errores');

        // Validación 3: Se importaron todos los materiales (6 en total)
        $this->assertEquals(6, $responseData['insertados'], 'Deben haberse importado 6 materiales');

        // Validación 4: No debe haber errores en esta importación
        $this->assertIsArray($responseData['errores'], 'Los errores deben ser un array');
        $this->assertEmpty($responseData['errores'], 'No debe haber errores en una importación válida');

        // Paso 3: Verificar que todos los materiales están en la BD
        $materialModel = model('MaterialModel');
        $todosLosMateriales = $materialModel->findAll();

        // Debe haber exactamente 6 materiales
        $this->assertCount(6, $todosLosMateriales, 'Deben existir 6 materiales en la BD');

        // Paso 4: Verificar que cada material importado se guardó correctamente
        foreach ($datosImportacion['items'] as $itemEsperado) {
            // Buscar el material en la BD por nombre
            $materialEnBD = null;
            foreach ($todosLosMateriales as $material) {
                if ($material['nombre'] === $itemEsperado['nombre']) {
                    $materialEnBD = $material;
                    break;
                }
            }

            $this->assertNotNull($materialEnBD, "El material '{$itemEsperado['nombre']}' debe existir en la BD");
            $this->assertEquals($itemEsperado['cantidad'], $materialEnBD['cantidad'], "La cantidad de '{$itemEsperado['nombre']}' debe coincidir");
            
            // Verificar que el idTipo se mapeó correctamente según el nombre del tipo
            $this->assertNotNull($materialEnBD['idTipo'], "El material '{$itemEsperado['nombre']}' debe tener un idTipo asignado");
            $this->assertIsNumeric($materialEnBD['idTipo'], "El idTipo debe ser numérico");
            $this->assertGreaterThan(0, $materialEnBD['idTipo'], "El idTipo debe ser mayor a 0");
        }

        // Paso 5: Verificar que los materiales están disponibles via GET
        $resultGet = $this->get('api/materiales');
        $resultGet->assertStatus(200);

        $catalogoCompleto = json_decode($resultGet->getJSON(), true);
        $this->assertCount(6, $catalogoCompleto, 'El catálogo debe contener los 6 materiales importados');

        // Validación 5: Verificar que los tipos se mapearon correctamente consultando el catálogo (que incluye tipo_nombre)
        $materialesLED = array_filter($catalogoCompleto, function($m) {
            return $m['tipo_nombre'] === 'Lámpara LED';
        });

        $materialesSodio = array_filter($catalogoCompleto, function($m) {
            return $m['tipo_nombre'] === 'Lámpara de Sodio';
        });

        $materialesCable = array_filter($catalogoCompleto, function($m) {
            return $m['tipo_nombre'] === 'Cable Eléctrico';
        });

        $materialesPoste = array_filter($catalogoCompleto, function($m) {
            return $m['tipo_nombre'] === 'Poste';
        });

        // Deben haber 3 materiales de tipo "Lámpara LED"
        $this->assertCount(3, $materialesLED, 'Deben haber 3 materiales de tipo Lámpara LED');

        // Debe haber 1 material de tipo "Lámpara de Sodio"
        $this->assertCount(1, $materialesSodio, 'Debe haber 1 material de tipo Lámpara de Sodio');

        // Debe haber 1 material de tipo "Cable Eléctrico"
        $this->assertCount(1, $materialesCable, 'Debe haber 1 material de tipo Cable Eléctrico');

        // Debe haber 1 material de tipo "Poste"
        $this->assertCount(1, $materialesPoste, 'Debe haber 1 material de tipo Poste');

        // Validación 6: Verificar distribución de cantidades importadas
        $cantidadTotal = 0;
        foreach ($catalogoCompleto as $material) {
            $cantidadTotal += $material['cantidad'];
        }

        // Suma de cantidades: 50 + 30 + 40 + 500 + 15 + 25 = 660
        $this->assertEquals(660, $cantidadTotal, 'La suma total de cantidades debe ser 660');

        // Validación 7: Verificar que todos los materiales tienen IDs únicos
        $ids = array_column($catalogoCompleto, 'id');
        $idsUnicos = array_unique($ids);
        $this->assertCount(count($ids), $idsUnicos, 'Todos los IDs deben ser únicos');
    }

    /**
     * HU-030 - Prueba 6: Validación de Formato CSV Incorrecto
     * 
     * Objetivo: Verificar que se rechaza importación con formato incorrecto
     * Casos a probar:
     * - Request sin el campo "items" → 400
     * - Campo "items" que no es un array → 400
     * - Array vacío de items → 400
     * - Items con campos faltantes → 400 y mensaje descriptivo
     * - Items con valores inválidos → 400 y mensaje descriptivo
     */
    public function testValidacionFormatoCSVIncorrecto()
    {
        // ===== CASO 1: Request sin el campo "items" =====
        $datosInvalidos1 = [
            'data' => [] // Campo incorrecto, debería ser "items"
        ];

        $result1 = $this->withBodyFormat('json')->post('api/materiales/import', $datosInvalidos1);

        // Debe retornar 400 (Bad Request)
        $result1->assertStatus(400);

        $response1 = json_decode($result1->getJSON(), true);
        // CodeIgniter usa 'messages' para errores de validación, no 'message'
        $this->assertTrue(
            isset($response1['message']) || isset($response1['messages']) || isset($response1['error']), 
            'La respuesta debe contener un mensaje de error (message, messages o error)'
        );

        // ===== CASO 2: Campo "items" que no es un array =====
        $datosInvalidos2 = [
            'items' => 'esto no es un array' // String en lugar de array
        ];

        $result2 = $this->withBodyFormat('json')->post('api/materiales/import', $datosInvalidos2);
        $result2->assertStatus(400);

        $response2 = json_decode($result2->getJSON(), true);
        $this->assertTrue(
            isset($response2['message']) || isset($response2['messages']) || isset($response2['error']), 
            'La respuesta debe contener un mensaje de error'
        );

        // ===== CASO 3: Array vacío de items =====
        $datosInvalidos3 = [
            'items' => [] // Array vacío
        ];

        $result3 = $this->withBodyFormat('json')->post('api/materiales/import', $datosInvalidos3);
        $result3->assertStatus(400);

        $response3 = json_decode($result3->getJSON(), true);
        $this->assertTrue(
            isset($response3['message']) || isset($response3['messages']) || isset($response3['error']), 
            'La respuesta debe contener un mensaje de error'
        );
        
        // Verificar que el mensaje menciona que no hay materiales válidos
        $mensajeError3 = $response3['message'] ?? $response3['messages'] ?? $response3['error'] ?? '';
        if (is_array($mensajeError3)) {
            $mensajeError3 = implode(' ', $mensajeError3);
        }
        $this->assertStringContainsString('válid', strtolower($mensajeError3), 'El mensaje debe indicar que no hay materiales válidos');

        // ===== CASO 4: Items con campos faltantes =====
        
        // 4a. Item sin campo "nombre"
        $datosInvalidos4a = [
            'items' => [
                [
                    // 'nombre' => 'Falta el nombre', // Campo faltante
                    'cantidad' => 10,
                    'tipo' => 'Lámpara LED'
                ]
            ]
        ];

        $result4a = $this->withBodyFormat('json')->post('api/materiales/import', $datosInvalidos4a);
        // Cuando NO hay materiales válidos, retorna 400
        $result4a->assertStatus(400);

        $response4a = json_decode($result4a->getJSON(), true);
        $mensajeError4a = $response4a['message'] ?? $response4a['messages'] ?? '';
        if (is_array($mensajeError4a)) {
            $mensajeError4a = implode(' ', $mensajeError4a);
        }
        // Verificar que el mensaje menciona que falta el nombre
        $this->assertStringContainsString('nombre', strtolower($mensajeError4a), 'El error debe mencionar que falta el nombre');

        // 4b. Item sin campo "tipo"
        $datosInvalidos4b = [
            'items' => [
                [
                    'nombre' => 'Material sin tipo',
                    'cantidad' => 10
                    // 'tipo' => 'Falta el tipo' // Campo faltante
                ]
            ]
        ];

        $result4b = $this->withBodyFormat('json')->post('api/materiales/import', $datosInvalidos4b);
        $result4b->assertStatus(400);

        $response4b = json_decode($result4b->getJSON(), true);
        $mensajeError4b = $response4b['message'] ?? $response4b['messages'] ?? '';
        if (is_array($mensajeError4b)) {
            $mensajeError4b = implode(' ', $mensajeError4b);
        }
        $this->assertStringContainsString('tipo', strtolower($mensajeError4b), 'El error debe mencionar que falta el tipo');

        // 4c. Item sin campo "cantidad"
        $datosInvalidos4c = [
            'items' => [
                [
                    'nombre' => 'Material sin cantidad',
                    'tipo' => 'Lámpara LED'
                    // 'cantidad' => 10 // Campo faltante
                ]
            ]
        ];

        $result4c = $this->withBodyFormat('json')->post('api/materiales/import', $datosInvalidos4c);
        $result4c->assertStatus(400);

        $response4c = json_decode($result4c->getJSON(), true);
        $mensajeError4c = $response4c['message'] ?? $response4c['messages'] ?? '';
        if (is_array($mensajeError4c)) {
            $mensajeError4c = implode(' ', $mensajeError4c);
        }
        $this->assertStringContainsString('cantidad', strtolower($mensajeError4c), 'El error debe mencionar que falta la cantidad');

        // ===== CASO 5: Items con valores inválidos =====

        // 5a. Nombre vacío
        $datosInvalidos5a = [
            'items' => [
                [
                    'nombre' => '', // Nombre vacío
                    'cantidad' => 10,
                    'tipo' => 'Lámpara LED'
                ]
            ]
        ];

        $result5a = $this->withBodyFormat('json')->post('api/materiales/import', $datosInvalidos5a);
        $result5a->assertStatus(400); // Cuando NO hay materiales válidos, retorna 400

        $response5a = json_decode($result5a->getJSON(), true);
        $mensajeError5a = $response5a['message'] ?? $response5a['messages'] ?? '';
        if (is_array($mensajeError5a)) {
            $mensajeError5a = implode(' ', $mensajeError5a);
        }
        $this->assertStringContainsString('nombre', strtolower($mensajeError5a), 'El error debe mencionar el nombre');

        // 5b. Cantidad negativa
        $datosInvalidos5b = [
            'items' => [
                [
                    'nombre' => 'Material con cantidad negativa',
                    'cantidad' => -10, // Cantidad negativa
                    'tipo' => 'Lámpara LED'
                ]
            ]
        ];

        $result5b = $this->withBodyFormat('json')->post('api/materiales/import', $datosInvalidos5b);
        $result5b->assertStatus(400);

        $response5b = json_decode($result5b->getJSON(), true);
        $mensajeError5b = $response5b['message'] ?? $response5b['messages'] ?? '';
        if (is_array($mensajeError5b)) {
            $mensajeError5b = implode(' ', $mensajeError5b);
        }
        $this->assertStringContainsString('cantidad', strtolower($mensajeError5b), 'El error debe mencionar la cantidad');

        // 5c. Tipo inexistente
        $datosInvalidos5c = [
            'items' => [
                [
                    'nombre' => 'Material con tipo inexistente',
                    'cantidad' => 10,
                    'tipo' => 'Tipo Que No Existe En La BD'
                ]
            ]
        ];

        $result5c = $this->withBodyFormat('json')->post('api/materiales/import', $datosInvalidos5c);
        $result5c->assertStatus(400);

        $response5c = json_decode($result5c->getJSON(), true);
        $mensajeError5c = $response5c['message'] ?? $response5c['messages'] ?? '';
        if (is_array($mensajeError5c)) {
            $mensajeError5c = implode(' ', $mensajeError5c);
        }
        $this->assertStringContainsString('tipo', strtolower($mensajeError5c), 'El error debe mencionar el tipo');
        $this->assertStringContainsString('no existe', strtolower($mensajeError5c), 'El error debe indicar que el tipo no existe');

        // ===== CASO 6: Importación mixta (algunos válidos, algunos inválidos) =====
        $datosMixtos = [
            'items' => [
                [
                    'nombre' => 'Material Válido 1',
                    'cantidad' => 10,
                    'tipo' => 'Lámpara LED'
                ],
                [
                    'nombre' => '', // Inválido: nombre vacío
                    'cantidad' => 20,
                    'tipo' => 'Cable Eléctrico'
                ],
                [
                    'nombre' => 'Material Válido 2',
                    'cantidad' => 30,
                    'tipo' => 'Poste'
                ],
                [
                    'nombre' => 'Material con tipo inválido',
                    'cantidad' => 40,
                    'tipo' => 'Tipo Inventado'
                ]
            ]
        ];

        $resultMixto = $this->withBodyFormat('json')->post('api/materiales/import', $datosMixtos);
        $resultMixto->assertStatus(200);

        $responseMixto = json_decode($resultMixto->getJSON(), true);

        // Deben insertarse solo los 2 válidos
        $this->assertEquals(2, $responseMixto['insertados'], 'Deben insertarse solo los 2 materiales válidos');

        // Deben haber 2 errores (los 2 inválidos)
        $this->assertCount(2, $responseMixto['errores'], 'Debe haber 2 errores (1 por nombre vacío, 1 por tipo inexistente)');

        // Verificar que los errores identifican correctamente las filas
        $primerErrorMixto = $responseMixto['errores'][0];
        $this->assertStringContainsString('fila', strtolower($primerErrorMixto), 'El error debe indicar el número de fila');

        // Verificar que los materiales válidos se insertaron en BD
        $materialModel = model('MaterialModel');
        $materialValido1 = $materialModel->where('nombre', 'Material Válido 1')->first();
        $materialValido2 = $materialModel->where('nombre', 'Material Válido 2')->first();

        $this->assertNotNull($materialValido1, 'El primer material válido debe estar en la BD');
        $this->assertNotNull($materialValido2, 'El segundo material válido debe estar en la BD');

        // Verificar que los materiales inválidos NO se insertaron
        $materialInvalido1 = $materialModel->where('nombre', '')->first();
        $materialInvalido2 = $materialModel->where('nombre', 'Material con tipo inválido')->first();

        $this->assertNull($materialInvalido1, 'El material con nombre vacío NO debe estar en la BD');
        $this->assertNull($materialInvalido2, 'El material con tipo inválido NO debe estar en la BD');
    }
}

