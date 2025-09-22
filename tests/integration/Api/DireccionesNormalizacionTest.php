<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;

class DireccionesNormalizacionTest extends CIUnitTestCase
{
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar base de datos de prueba
        $this->db = \Config\Database::connect('tests');
        
        // Limpiar tabla antes de cada test
        $this->db->table('direccion')->truncate();
    }

    protected function tearDown(): void
    {
        // Limpiar después de cada test
        $this->db->table('direccion')->truncate();
        parent::tearDown();
    }

    /**
     * Test: Normalización de texto con tildes
     * Verificar que "José de San Martín" se normaliza correctamente
     */
    public function testNormalizacionTextoConTildes()
    {
        // Crear una dirección con tildes
        $direccionOriginal = [
            'domicilio' => 'José de San Martín',
            'numero_domicilio' => '1234',
            'latitud' => -31.4280,
            'longitud' => -62.0826
        ];

        // Insertar la dirección
        $this->db->table('direccion')->insert($direccionOriginal);

        // Simular la normalización que hace el controlador
        $domicilioNormalizado = $this->normalizarTexto('José de San Martín');
        $numeroNormalizado = $this->normalizarNumero('1234');

        // Verificar que la normalización funciona correctamente
        $this->assertEquals('JOSE DE SAN MARTIN', $domicilioNormalizado);
        $this->assertEquals('1234', $numeroNormalizado);

        // Buscar en la base de datos usando la normalización
        $direccionEncontrada = $this->db->table('direccion')
            ->where('TRIM(UPPER(domicilio))', $domicilioNormalizado)
            ->where('TRIM(numero_domicilio)', $numeroNormalizado)
            ->get()
            ->getRowArray();

        $this->assertNotNull($direccionEncontrada);
        $this->assertEquals('José de San Martín', $direccionEncontrada['domicilio']);
        $this->assertEquals('1234', $direccionEncontrada['numero_domicilio']);
    }

    /**
     * Test: Normalización de texto con espacios extra
     * Verificar que se manejan correctamente los espacios al inicio y final
     */
    public function testNormalizacionTextoConEspacios()
    {
        // Crear una dirección
        $direccionOriginal = [
            'domicilio' => 'Av. Maipú',
            'numero_domicilio' => '567',
            'latitud' => -31.4300,
            'longitud' => -62.0850
        ];

        $this->db->table('direccion')->insert($direccionOriginal);

        // Probar normalización con espacios extra
        $textoConEspacios = '  Av. Maipú  ';
        $textoNormalizado = $this->normalizarTexto($textoConEspacios);

        $this->assertEquals('AV. MAIPU', $textoNormalizado);

        // Buscar en la base de datos
        $direccionEncontrada = $this->db->table('direccion')
            ->where('TRIM(UPPER(domicilio))', $textoNormalizado)
            ->where('TRIM(numero_domicilio)', '567')
            ->get()
            ->getRowArray();

        $this->assertNotNull($direccionEncontrada);
        $this->assertEquals('Av. Maipú', $direccionEncontrada['domicilio']);
    }

    /**
     * Test: Normalización de texto con caracteres especiales
     * Verificar que Ñ se convierte a N
     */
    public function testNormalizacionTextoConEnie()
    {
        // Crear una dirección con Ñ
        $direccionOriginal = [
            'domicilio' => 'Ñuñorco',
            'numero_domicilio' => '890',
            'latitud' => -31.4250,
            'longitud' => -62.0800
        ];

        $this->db->table('direccion')->insert($direccionOriginal);

        // Probar normalización con Ñ
        $textoConEnie = 'Ñuñorco';
        $textoNormalizado = $this->normalizarTexto($textoConEnie);

        $this->assertEquals('NUNORCO', $textoNormalizado);

        // Buscar en la base de datos
        $direccionEncontrada = $this->db->table('direccion')
            ->where('TRIM(UPPER(domicilio))', $textoNormalizado)
            ->where('TRIM(numero_domicilio)', '890')
            ->get()
            ->getRowArray();

        $this->assertNotNull($direccionEncontrada);
        $this->assertEquals('Ñuñorco', $direccionEncontrada['domicilio']);
    }

    /**
     * Test: Normalización de números de domicilio
     * Verificar que se manejan correctamente los números con espacios
     */
    public function testNormalizacionNumeroDomicilio()
    {
        // Crear una dirección
        $direccionOriginal = [
            'domicilio' => 'Calle Mitre',
            'numero_domicilio' => '1234',
            'latitud' => -31.4300,
            'longitud' => -62.0850
        ];

        $this->db->table('direccion')->insert($direccionOriginal);

        // Probar normalización de números con espacios
        $numeroConEspacios = '  1234  ';
        $numeroNormalizado = $this->normalizarNumero($numeroConEspacios);

        $this->assertEquals('1234', $numeroNormalizado);

        // Buscar en la base de datos
        $direccionEncontrada = $this->db->table('direccion')
            ->where('TRIM(UPPER(domicilio))', 'CALLE MITRE')
            ->where('TRIM(numero_domicilio)', $numeroNormalizado)
            ->get()
            ->getRowArray();

        $this->assertNotNull($direccionEncontrada);
        $this->assertEquals('1234', $direccionEncontrada['numero_domicilio']);
    }

    /**
     * Test: Normalización de números no numéricos
     * Verificar que se mantienen números con letras (ej: "1234A")
     */
    public function testNormalizacionNumeroConLetras()
    {
        // Crear una dirección con número que contiene letras
        $direccionOriginal = [
            'domicilio' => 'Boulevard Pellegrini',
            'numero_domicilio' => '1234A',
            'latitud' => -31.4250,
            'longitud' => -62.0800
        ];

        $this->db->table('direccion')->insert($direccionOriginal);

        // Probar normalización de números con letras
        $numeroConLetras = '  1234A  ';
        $numeroNormalizado = $this->normalizarNumero($numeroConLetras);

        $this->assertEquals('1234A', $numeroNormalizado);

        // Buscar en la base de datos
        $direccionEncontrada = $this->db->table('direccion')
            ->where('TRIM(UPPER(domicilio))', 'BOULEVARD PELLEGRINI')
            ->where('TRIM(numero_domicilio)', $numeroNormalizado)
            ->get()
            ->getRowArray();

        $this->assertNotNull($direccionEncontrada);
        $this->assertEquals('1234A', $direccionEncontrada['numero_domicilio']);
    }

    /**
     * Test: Normalización con texto vacío
     * Verificar que se manejan correctamente los campos vacíos
     */
    public function testNormalizacionTextoVacio()
    {
        // Probar normalización con texto vacío
        $textoVacio = '';
        $textoNormalizado = $this->normalizarTexto($textoVacio);

        $this->assertEquals('', $textoNormalizado);

        // Probar normalización con solo espacios
        $textoSoloEspacios = '   ';
        $textoNormalizadoEspacios = $this->normalizarTexto($textoSoloEspacios);

        $this->assertEquals('', $textoNormalizadoEspacios);
    }

    /**
     * Test: Normalización con números vacíos
     * Verificar que se manejan correctamente los números vacíos
     */
    public function testNormalizacionNumeroVacio()
    {
        // Probar normalización con número vacío
        $numeroVacio = '';
        $numeroNormalizado = $this->normalizarNumero($numeroVacio);

        $this->assertEquals('', $numeroNormalizado);

        // Probar normalización con solo espacios
        $numeroSoloEspacios = '   ';
        $numeroNormalizadoEspacios = $this->normalizarNumero($numeroSoloEspacios);

        $this->assertEquals('', $numeroNormalizadoEspacios);
    }

    /**
     * Test: Normalización con caracteres especiales complejos
     * Verificar que se normalizan correctamente múltiples caracteres especiales
     */
    public function testNormalizacionCaracteresEspecialesComplejos()
    {
        // Crear una dirección con múltiples caracteres especiales
        $direccionOriginal = [
            'domicilio' => 'José María de Ávila y Ñuñorco',
            'numero_domicilio' => '1234',
            'latitud' => -31.4280,
            'longitud' => -62.0826
        ];

        $this->db->table('direccion')->insert($direccionOriginal);

        // Probar normalización con múltiples caracteres especiales
        $textoComplejo = 'José María de Ávila y Ñuñorco';
        $textoNormalizado = $this->normalizarTexto($textoComplejo);

        $this->assertEquals('JOSE MARIA DE AVILA Y NUNORCO', $textoNormalizado);

        // Buscar en la base de datos
        $direccionEncontrada = $this->db->table('direccion')
            ->where('TRIM(UPPER(domicilio))', $textoNormalizado)
            ->where('TRIM(numero_domicilio)', '1234')
            ->get()
            ->getRowArray();

        $this->assertNotNull($direccionEncontrada);
        $this->assertEquals('José María de Ávila y Ñuñorco', $direccionEncontrada['domicilio']);
    }

    /**
     * Test: Normalización con mayúsculas y minúsculas mixtas
     * Verificar que se normalizan correctamente las mayúsculas y minúsculas
     */
    public function testNormalizacionMayusculasMinusculas()
    {
        // Crear una dirección
        $direccionOriginal = [
            'domicilio' => 'Av. San Martín',
            'numero_domicilio' => '567',
            'latitud' => -31.4300,
            'longitud' => -62.0850
        ];

        $this->db->table('direccion')->insert($direccionOriginal);

        // Probar normalización con mayúsculas y minúsculas mixtas
        $textoMixto = 'Av. sAn MaRtÍn';
        $textoNormalizado = $this->normalizarTexto($textoMixto);

        $this->assertEquals('AV. SAN MARTIN', $textoNormalizado);

        // Buscar en la base de datos
        $direccionEncontrada = $this->db->table('direccion')
            ->where('TRIM(UPPER(domicilio))', $textoNormalizado)
            ->where('TRIM(numero_domicilio)', '567')
            ->get()
            ->getRowArray();

        $this->assertNotNull($direccionEncontrada);
        $this->assertEquals('Av. San Martín', $direccionEncontrada['domicilio']);
    }

    /**
     * Test: Verificar que la normalización es consistente
     * Diferentes variaciones del mismo texto deben normalizarse igual
     */
    public function testNormalizacionConsistencia()
    {
        $variaciones = [
            'José de San Martín',
            'JOSE DE SAN MARTIN',
            'jose de san martin',
            'JOSÉ DE SAN MARTÍN',
            '  José de San Martín  ',
            'José de San Martín'
        ];

        $resultados = [];
        foreach ($variaciones as $variacion) {
            $resultados[] = $this->normalizarTexto($variacion);
        }

        // Todos los resultados deben ser iguales
        $primerResultado = $resultados[0];
        foreach ($resultados as $resultado) {
            $this->assertEquals($primerResultado, $resultado, 'Todas las variaciones deben normalizarse igual');
        }

        $this->assertEquals('JOSE DE SAN MARTIN', $primerResultado);
    }

    /**
     * Método auxiliar para normalizar texto (copiado del controlador)
     */
    private function normalizarTexto($texto)
    {
        if (empty($texto)) return '';
        
        // Eliminar espacios al inicio y final
        $texto = trim($texto);
        
        // Convertir a mayúsculas (usando mb_strtoupper para UTF-8)
        $texto = mb_strtoupper($texto, 'UTF-8');
        
        // Normalizar caracteres especiales comunes
        $texto = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'N'], $texto);
        
        return $texto;
    }

    /**
     * Método auxiliar para normalizar número (copiado del controlador)
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