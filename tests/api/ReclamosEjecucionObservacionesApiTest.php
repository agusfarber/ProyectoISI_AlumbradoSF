<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

class ReclamosEjecucionObservacionesApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'Tests\Support';
    protected $seed        = 'Tests\Support\Database\Seeds\TestSeeder';

class ReclamosEjecucionObservacionesApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'Tests\Support';
    protected $seed        = 'Tests\Support\Database\Seeds\TestSeeder';

    /**
     * @return array{cuadrillaId: int, rutaId: int, reclamoId: int, ejecucionId: int}
     */
    private function crearContextoEjecucionConObraActiva(): array
    {
        $db = \Config\Database::connect();

        $db->table('cuadrilla')->insert([
            'nombre'      => 'Cuadrilla Test Obs',
            'descripcion' => 'Test',
        ]);
        $cuadrillaId = (int) $db->insertID();

        $operarioId = 2;
        $db->table('cuadrilla_operarios')->insert([
            'cuadrilla_id' => $cuadrillaId,
            'usuario_id'   => $operarioId,
            'es_jefe'      => 1,
        ]);

        $rutaInsert = [
            'nombre'           => 'Ruta test obs',
            'color'            => '#FF0000',
            'cantidadReclamos' => 1,
            'asignada'         => 1,
            'cuadrilla_id'     => $cuadrillaId,
            'tiempoEstimado'   => '02:00:00',
            'fecha'            => date('Y-m-d H:i:s'),
        ];
        if ($db->fieldExists('estado_ejecucion', 'ruta')) {
            $rutaInsert['estado_ejecucion'] = 'en ejecución';
        }
        $db->table('ruta')->insert($rutaInsert);
        $rutaId = (int) $db->insertID();

        $db->table('reclamo')->insert([
            'municipalidad_id'                => 'OBS-' . uniqid(),
            'municipalidad_tipo'              => 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo'            => 'Test observación ejecución',
            'municipalidad_fechaInicio'       => '2025-01-15 10:00:00',
            'municipalidad_fechaModificacion' => '2025-01-15 10:00:00',
            'municipalidad_recepcion'         => 'Web',
            'municipalidad_estado'            => 'En ejecución',
            'prioridad'                       => 'Media',
        ]);
        $reclamoId = (int) $db->insertID();

        $db->table('ruta_reclamo')->insert([
            'ruta_id'    => $rutaId,
            'reclamo_id' => $reclamoId,
            'posicion'   => 1,
        ]);

        $db->table('ruta_ejecucion')->insert([
            'ruta_id'      => $rutaId,
            'cuadrilla_id' => $cuadrillaId,
            'inicio_at'    => date('Y-m-d H:i:s'),
            'fin_at'       => null,
        ]);
        $ejecucionId = (int) $db->insertID();

        $db->table('ruta_ejecucion_evento')->insert([
            'ruta_ejecucion_id' => $ejecucionId,
            'tipo'              => 'ejecucion_reclamo_inicio',
            'reclamo_id'        => $reclamoId,
            'usuario_id'        => $operarioId,
            'ocurrido_at'       => date('Y-m-d H:i:s'),
        ]);

        return compact('cuadrillaId', 'rutaId', 'reclamoId', 'ejecucionId');
    }

    private function withSessionOperarioTest()
    {
        return $this->withSession([
            'user_id' => 2,
            'role'    => '3',
            'nombre'  => 'Operario Test',
        ]);
    }

    public function testGuardarYListarObservacionEjecucionReclamo()
    {
        $ctx = $this->crearContextoEjecucionConObraActiva();
        extract($ctx);

        $payload = [
            'texto'             => 'Cableado revisado en caja derivación.',
            'ruta_ejecucion_id' => $ejecucionId,
        ];

        $result = $this->withSessionOperarioTest()
            ->withBodyFormat('json')
            ->post("api/reclamos/{$reclamoId}/ejecucion-observaciones", $payload);

        $result->assertStatus(201);
        $responseData = json_decode($result->getJSON(), true);
        $this->assertIsArray($responseData);
        $this->assertEquals($payload['texto'], $responseData['texto']);
        $this->assertEquals('texto', $responseData['tipo'] ?? 'texto');
        $this->assertEquals($ejecucionId, (int) $responseData['ruta_ejecucion_id']);
        $this->assertEquals($rutaId, (int) $responseData['ruta_id']);
        $this->assertEquals($reclamoId, (int) $responseData['reclamo_id']);

        $list = $this->withSessionOperarioTest()
            ->get("api/reclamos/{$reclamoId}/ejecucion-observaciones?ruta_ejecucion_id={$ejecucionId}");
        $list->assertStatus(200);
        $rows = json_decode($list->getJSON(), true);
        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);
        $this->assertEquals($payload['texto'], $rows[0]['texto']);
    }

    public function testObservacionRechazaEjecucionIdIncorrecto()
    {
        $ctx = $this->crearContextoEjecucionConObraActiva();
        extract($ctx);

        $result = $this->withSessionOperarioTest()
            ->withBodyFormat('json')
            ->post("api/reclamos/{$reclamoId}/ejecucion-observaciones", [
                'texto'             => 'Texto',
                'ruta_ejecucion_id' => $ejecucionId + 999,
            ]);

        $result->assertStatus(403);
    }

    public function testListarObservacionesIncluyeEjecucionesAnteriores()
    {
        $db = \Config\Database::connect();

        $db->table('cuadrilla')->insert([
            'nombre'      => 'Cuadrilla multi ejec',
            'descripcion' => 'Test',
        ]);
        $cuadrillaId = (int) $db->insertID();

        $db->table('reclamo')->insert([
            'municipalidad_id'                => 'OBS-MULTI',
            'municipalidad_tipo'              => 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo'            => 'Reclamo en varias hojas',
            'municipalidad_fechaInicio'       => '2025-01-15 10:00:00',
            'municipalidad_fechaModificacion' => '2025-01-15 10:00:00',
            'municipalidad_recepcion'         => 'Web',
            'municipalidad_estado'            => 'Pendiente',
            'prioridad'                       => 'Media',
        ]);
        $reclamoId = (int) $db->insertID();

        $db->table('ruta')->insert([
            'nombre'           => 'Hoja día anterior',
            'color'            => '#FF0000',
            'cantidadReclamos' => 1,
            'asignada'         => 1,
            'cuadrilla_id'     => $cuadrillaId,
            'tiempoEstimado'   => '02:00:00',
            'fecha'            => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);
        $rutaAnteriorId = (int) $db->insertID();

        $db->table('ruta_ejecucion')->insert([
            'ruta_id'      => $rutaAnteriorId,
            'cuadrilla_id' => $cuadrillaId,
            'inicio_at'    => date('Y-m-d H:i:s', strtotime('-1 day')),
            'fin_at'       => date('Y-m-d H:i:s', strtotime('-1 day +2 hours')),
        ]);
        $ejecucionAnteriorId = (int) $db->insertID();

        $db->table('ruta_ejecucion_reclamo_observacion')->insert([
            'ruta_ejecucion_id' => $ejecucionAnteriorId,
            'ruta_id'           => $rutaAnteriorId,
            'reclamo_id'        => $reclamoId,
            'tipo'              => 'texto',
            'usuario_id'        => 1,
            'texto'             => 'Observación en hoja anterior',
            'created_at'        => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);

        $db->table('ruta')->insert([
            'nombre'           => 'Hoja hoy',
            'color'            => '#00FF00',
            'cantidadReclamos' => 1,
            'asignada'         => 1,
            'cuadrilla_id'     => $cuadrillaId,
            'tiempoEstimado'   => '02:00:00',
            'fecha'            => date('Y-m-d H:i:s'),
        ]);
        $rutaNuevaId = (int) $db->insertID();

        $db->table('ruta_reclamo')->insert([
            'ruta_id'    => $rutaNuevaId,
            'reclamo_id' => $reclamoId,
            'posicion'   => 1,
        ]);

        $db->table('ruta_ejecucion')->insert([
            'ruta_id'      => $rutaNuevaId,
            'cuadrilla_id' => $cuadrillaId,
            'inicio_at'    => date('Y-m-d H:i:s'),
            'fin_at'       => null,
        ]);
        $ejecucionNuevaId = (int) $db->insertID();

        $list = $this->withSession([
            'user_id' => 1,
            'role'    => '2',
        ])->get("api/reclamos/{$reclamoId}/ejecucion-observaciones?ruta_ejecucion_id={$ejecucionNuevaId}");
        $list->assertStatus(200);
        $rows = json_decode($list->getJSON(), true);
        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);
        $this->assertEquals('Observación en hoja anterior', $rows[0]['texto']);
        $this->assertEquals('Hoja día anterior', $rows[0]['ruta_nombre']);
        $this->assertEquals('#FF0000', $rows[0]['ruta_color']);
        $this->assertEquals($ejecucionAnteriorId, (int) $rows[0]['ruta_ejecucion_id']);
    }
}
