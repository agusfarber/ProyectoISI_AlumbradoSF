<?php

namespace Tests\Unit;

use App\Libraries\ReclamoPrioridadService;
use CodeIgniter\Test\CIUnitTestCase;

class ReclamoPrioridadServiceTest extends CIUnitTestCase
{
    public function testMotivoEspecialEsPrioridadAlta(): void
    {
        $reclamo = [
            'municipalidad_motivo' => ReclamoPrioridadService::MOTIVO_PRIORIDAD_ALTA,
            'municipalidad_estado' => 'Recibido',
            'cerrado' => 0,
            'prioridad' => 'Baja',
        ];

        $this->assertEquals('Alta', ReclamoPrioridadService::evaluarPrioridad($reclamo));
    }

    public function testMotivoSemaforosEsPrioridadAlta(): void
    {
        $reclamo = [
            'municipalidad_motivo' => 'Semáforos - Arreglo y sincronización',
            'municipalidad_estado' => 'Recibido',
            'cerrado' => 0,
            'prioridad' => 'Baja',
        ];

        $this->assertEquals('Alta', ReclamoPrioridadService::evaluarPrioridad($reclamo));
    }

    public function testPendienteEsPrioridadAlta(): void
    {
        $reclamo = [
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_estado' => 'Pendiente',
            'cerrado' => 0,
            'prioridad' => 'Baja',
        ];

        $this->assertEquals('Alta', ReclamoPrioridadService::evaluarPrioridad($reclamo));
    }

    public function testEnEjecucionNoSubeSoloPorEstado(): void
    {
        $reclamo = [
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_estado' => 'En ejecución',
            'municipalidad_fechaInicio' => date('Y-m-d H:i:s'),
            'cerrado' => 0,
            'prioridad' => 'Baja',
        ];

        $this->assertEquals('Baja', ReclamoPrioridadService::evaluarPrioridad($reclamo));
    }

    public function testCompletadoTienePrioridadNula(): void
    {
        $reclamo = [
            'municipalidad_motivo' => ReclamoPrioridadService::MOTIVO_PRIORIDAD_ALTA,
            'municipalidad_estado' => 'Completado',
            'cerrado' => 0,
            'prioridad' => 'Alta',
        ];

        $this->assertNull(ReclamoPrioridadService::evaluarPrioridad($reclamo));
    }

    public function testCerradoTienePrioridadNula(): void
    {
        $reclamo = [
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_estado' => 'Recibido',
            'cerrado' => 1,
            'prioridad' => 'Alta',
        ];

        $this->assertNull(ReclamoPrioridadService::evaluarPrioridad($reclamo));
    }

    public function testAntiguedadMayorADiezDiasEsAlta(): void
    {
        $reclamo = [
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_estado' => 'Recibido',
            'municipalidad_fechaInicio' => date('Y-m-d H:i:s', strtotime('-11 days')),
            'cerrado' => 0,
            'prioridad' => 'Baja',
        ];

        $this->assertEquals('Alta', ReclamoPrioridadService::evaluarPrioridad($reclamo));
    }

    public function testAntiguedadExactaDiezDiasEsAlta(): void
    {
        $reclamo = [
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_estado' => 'Recibido',
            'municipalidad_fechaInicio' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'cerrado' => 0,
            'prioridad' => 'Baja',
        ];

        $this->assertEquals('Alta', ReclamoPrioridadService::evaluarPrioridad($reclamo));
    }

    public function testAntiguedadMenorADiezDiasNoEsAlta(): void
    {
        $reclamo = [
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_estado' => 'Recibido',
            'municipalidad_fechaInicio' => date('Y-m-d H:i:s', strtotime('-9 days')),
            'cerrado' => 0,
            'prioridad' => 'Baja',
        ];

        $this->assertEquals('Baja', ReclamoPrioridadService::evaluarPrioridad($reclamo));
    }
}
