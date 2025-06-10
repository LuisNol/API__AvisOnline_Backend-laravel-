<?php

require 'vendor/autoload.php';

// Configurar la base de datos
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== INFORMACIÓN DEL USUARIO ACTUAL ===\n\n";

// Token que se está usando en el frontend (los primeros 30 caracteres que vimos en los logs)
$tokenPrefix = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUz";

// Simular petición a /api/me para obtener información del usuario
$url = "http://localhost:8000/api/me";

// Necesitamos el token completo del localStorage del navegador
echo "Para obtener información del usuario actual, necesitamos hacer una petición con el token.\n";
echo "Ve a la consola del navegador y ejecuta: localStorage.getItem('token')\n";
echo "Luego copia el token completo aquí.\n\n";

// Mientras tanto, vamos a verificar las rutas y qué necesitamos
echo "Rutas disponibles para obtener información del usuario:\n";
echo "- POST /api/me (requiere token)\n";
echo "- POST /api/permissions (requiere token)\n\n";

echo "El token debe enviarse en el header: Authorization: Bearer TOKEN\n\n";

// También podemos hacer una petición de prueba al endpoint de usuarios para ver el error exacto
$url = "http://localhost:8000/api/admin/users-list";
$data = [
    'draw' => 1,
    'start' => 0,
    'length' => 10
];

echo "Vamos a ver qué error específico devuelve el endpoint de usuarios sin token completo...\n";

$headers = [
    'Content-Type: application/json',
    'Accept: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Sin token - Código: {$httpCode}\n";
echo "Respuesta: {$response}\n\n";

echo "¡Análisis completado!\n"; 