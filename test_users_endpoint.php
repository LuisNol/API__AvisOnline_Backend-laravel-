<?php

require 'vendor/autoload.php';

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

// Configurar la base de datos
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PROBANDO ENDPOINT DE USUARIOS ===\n\n";

// Generar token para un usuario Admin
$adminUser = User::whereHas('roles', function($q) {
    $q->where('name', 'Admin');
})->first();

if (!$adminUser) {
    echo "❌ No se encontró usuario Admin\n";
    exit(1);
}

echo "Usuario Admin encontrado: {$adminUser->name} ({$adminUser->email})\n";

// Generar token JWT
$token = JWTAuth::fromUser($adminUser);
echo "Token generado: " . substr($token, 0, 50) . "...\n\n";

// Simular petición HTTP
$url = "http://localhost:8000/api/admin/users-list";
$data = [
    'draw' => 1,
    'start' => 0,
    'length' => 10
];

$headers = [
    'Authorization: Bearer ' . $token,
    'X-User-Permission: manage-users',
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

echo "Realizando petición a: {$url}\n";
echo "Headers enviados:\n";
foreach ($headers as $header) {
    if (strpos($header, 'Authorization') !== false) {
        echo "- Authorization: Bearer " . substr($token, 0, 20) . "...\n";
    } else {
        echo "- {$header}\n";
    }
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "\nCódigo de respuesta: {$httpCode}\n";

if ($error) {
    echo "❌ Error cURL: {$error}\n";
} else {
    echo "Respuesta del servidor:\n";
    echo $response . "\n";
}

echo "\n¡Prueba completada!\n"; 