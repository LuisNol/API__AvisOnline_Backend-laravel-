<?php

require_once 'vendor/autoload.php';

// Cargar las variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    // Conectar a la base de datos
    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_DATABASE'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔗 Conectado a la base de datos: $dbname\n";
    
    // Obtener todas las tablas
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n📋 TODAS LAS TABLAS ACTUALES:\n";
    foreach($allTables as $table) {
        echo "  📦 $table\n";
    }
    
    // Clasificar tablas
    $necessary = [
        'users', 'roles', 'permissions', 'role_user', 'permission_role',
        'products', 'categories', 'product_images', 
        'sliders', 'anuncio_views',
        'migrations', 'failed_jobs', 'password_reset_tokens', 'personal_access_tokens'
    ];
    
    $stillUnnecessary = [];
    $unknown = [];
    
    foreach($allTables as $table) {
        if (in_array($table, $necessary)) {
            // Es necesaria, no hacer nada
        } elseif (strpos($table, 'cupone') !== false || 
                  strpos($table, 'sale') !== false ||
                  strpos($table, 'user_addres') !== false) {
            $stillUnnecessary[] = $table;
        } else {
            $unknown[] = $table;
        }
    }
    
    echo "\n✅ TABLAS NECESARIAS:\n";
    foreach($necessary as $table) {
        $exists = in_array($table, $allTables) ? '✅' : '❌';
        echo "  $exists $table\n";
    }
    
    if (!empty($stillUnnecessary)) {
        echo "\n🗑️ TABLAS INNECESARIAS QUE QUEDARON:\n";
        foreach($stillUnnecessary as $table) {
            echo "  ❌ $table\n";
        }
    }
    
    if (!empty($unknown)) {
        echo "\n❓ TABLAS DESCONOCIDAS (REVISAR):\n";
        foreach($unknown as $table) {
            echo "  ❓ $table\n";
        }
    }
    
    // Conteo de registros de tablas principales
    echo "\n📊 REGISTROS EN TABLAS PRINCIPALES:\n";
    $mainTables = ['users', 'products', 'categories', 'product_images', 'anuncio_views'];
    
    foreach($mainTables as $table) {
        if (in_array($table, $allTables)) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo "  📋 $table: $count registros\n";
            } catch (Exception $e) {
                echo "  ⚠️ Error contando $table: " . $e->getMessage() . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 