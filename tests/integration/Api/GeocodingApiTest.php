<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class GeocodingApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /**
     * Test: Google Maps - Coordenadas Juan José Paso Sur
     */
    public function testGoogleMapsJuanJosePaso()
    {
        $latitud = -31.420207;
        $longitud = -62.108582;
        
        $apiKey = 'AIzaSyAOCwr8_hWX4aBE2JTHxREP7gUrYLadCgg';
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitud},{$longitud}&key={$apiKey}&language=es";
        
        $response = $this->makeRequest($url);
        $data = json_decode($response, true);
        
        $this->assertEquals('OK', $data['status']);
        $formattedAddress = $data['results'][0]['formatted_address'];
        
        $this->assertTrue(
            strpos($formattedAddress, 'Juan José Paso') !== false || 
            strpos($formattedAddress, 'Juan Jose Paso') !== false,
            'Google Maps debe devolver "Juan José Paso" o "Juan Jose Paso"'
        );
        
        echo "\n📍 Google Maps: " . $formattedAddress;
    }

    /**
     * Test: Mapbox - Coordenadas Juan José Paso Sur
     */
    public function testMapboxJuanJosePaso()
    {
        $latitud = -31.420207;
        $longitud = -62.108582;
        
        $mapboxToken = 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ejJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw';
        $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/{$longitud},{$latitud}.json?access_token={$mapboxToken}&language=es";
        
        $response = $this->makeRequest($url);
        $data = json_decode($response, true);
        
        $formattedAddress = $data['features'][0]['place_name'];
        
        $this->assertTrue(
            strpos($formattedAddress, 'Juan José Paso') !== false || 
            strpos($formattedAddress, 'Juan Jose Paso') !== false,
            'Mapbox debe devolver "Juan José Paso" o "Juan Jose Paso"'
        );
        
        echo "\n🗺️ Mapbox: " . $formattedAddress;
    }

    /**
     * Test: Google Maps - Coordenadas Av Maipú
     */
    public function testGoogleMapsAvMaipu()
    {
        $latitud = -31.414374;
        $longitud = -62.094000;
        
        $apiKey = 'AIzaSyAOCwr8_hWX4aBE2JTHxREP7gUrYLadCgg';
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitud},{$longitud}&key={$apiKey}&language=es";
        
        $response = $this->makeRequest($url);
        $data = json_decode($response, true);
        
        $this->assertEquals('OK', $data['status']);
        $formattedAddress = $data['results'][0]['formatted_address'];
        
        $this->assertTrue(
            strpos($formattedAddress, 'Maipú') !== false || 
            strpos($formattedAddress, 'Maipu') !== false,
            'Google Maps debe devolver "Maipú" o "Maipu"'
        );
        
        echo "\n📍 Google Maps Av Maipú: " . $formattedAddress;
    }

    /**
     * Test: Mapbox - Coordenadas Av Maipú
     */
    public function testMapboxAvMaipu()
    {
        $latitud = -31.414374;
        $longitud = -62.094000;
        
        $mapboxToken = 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ejJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw';
        $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/{$longitud},{$latitud}.json?access_token={$mapboxToken}&language=es";
        
        $response = $this->makeRequest($url);
        $data = json_decode($response, true);
        
        $formattedAddress = $data['features'][0]['place_name'];
        
        $this->assertTrue(
            strpos($formattedAddress, 'Maipú') !== false || 
            strpos($formattedAddress, 'Maipu') !== false,
            'Mapbox debe devolver "Maipú" o "Maipu"'
        );
        
        echo "\n🗺️ Mapbox Av Maipú: " . $formattedAddress;
    }

    /**
     * Test: Google Maps - Coordenadas Calle Córdoba
     */
    public function testGoogleMapsCalleCordoba()
    {
        $latitud = -31.442028;
        $longitud = -62.090032;
        
        $apiKey = 'AIzaSyAOCwr8_hWX4aBE2JTHxREP7gUrYLadCgg';
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitud},{$longitud}&key={$apiKey}&language=es";
        
        $response = $this->makeRequest($url);
        $data = json_decode($response, true);
        
        $this->assertEquals('OK', $data['status']);
        $formattedAddress = $data['results'][0]['formatted_address'];
        
        $this->assertTrue(
            strpos($formattedAddress, 'Córdoba') !== false || 
            strpos($formattedAddress, 'Cordoba') !== false,
            'Google Maps debe devolver "Córdoba" o "Cordoba"'
        );
        
        echo "\n📍 Google Maps Calle Córdoba: " . $formattedAddress;
    }

    /**
     * Test: Mapbox - Coordenadas Calle Córdoba
     */
    public function testMapboxCalleCordoba()
    {
        $latitud = -31.442028;
        $longitud = -62.090032;
        
        $mapboxToken = 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ejJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw';
        $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/{$longitud},{$latitud}.json?access_token={$mapboxToken}&language=es";
        
        $response = $this->makeRequest($url);
        $data = json_decode($response, true);
        
        $formattedAddress = $data['features'][0]['place_name'];
        
        $this->assertTrue(
            strpos($formattedAddress, 'Córdoba') !== false || 
            strpos($formattedAddress, 'Cordoba') !== false,
            'Mapbox debe devolver "Córdoba" o "Cordoba"'
        );
        
        echo "\n🗺️ Mapbox Calle Córdoba: " . $formattedAddress;
    }

    /**
     * Método auxiliar para realizar peticiones HTTP
     */
    private function makeRequest($url)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'header' => 'User-Agent: PHP Test Client'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Test Client');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                $this->markTestSkipped("No se pudo conectar a la API. Código HTTP: {$httpCode}");
            }
        }
        
        return $response;
    }
}