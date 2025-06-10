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
    
    // Tablas innecesarias restantes a eliminar
    $tablesToDrop = [
        'cupone_brands',
        'cupone_categories', 
        'sale_addres',
        'sale_temps',
        'user_addres'
    ];
    
    echo "\n🗑️ ELIMINANDO TABLAS INNECESARIAS RESTANTES...\n";
    
    // Deshabilitar verificación de foreign keys
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    foreach ($tablesToDrop as $table) {
        try {
            // Verificar si la tabla existe
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            
            if ($stmt->rowCount() > 0) {
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
                echo "  ✅ Tabla '$table' eliminada\n";
            } else {
                echo "  ⚠️ Tabla '$table' no existe\n";
            }
        } catch (Exception $e) {
            echo "  ❌ Error eliminando '$table': " . $e->getMessage() . "\n";
        }
    }
    
    // Re-habilitar verificación de foreign keys
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n🔍 VERIFICACIÓN FINAL - TABLAS RESTANTES:\n";
    
    // Obtener todas las tablas restantes
    $stmt = $pdo->query("SHOW TABLES");
    $remainingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Tablas finales en AvisOnline:\n";
    foreach($remainingTables as $table) {
        echo "  📦 $table\n";
    }
    
    echo "\n✅ ESTRUCTURA FINAL PARA AVISONLINE:\n";
    $finalTables = [
        'users' => 'Usuarios del sistema',
        'roles' => 'Roles de usuario', 
        'permissions' => 'Permisos',
        'role_user' => 'Relación usuario-rol',
        'permission_role' => 'Relación rol-permiso',
        'products' => 'Anuncios/Productos',
        'categories' => 'Categorías de anuncios',
        'product_images' => 'Imágenes de anuncios',
        'sliders' => 'Anuncios destacados',
        'anuncio_views' => 'Estadísticas de visualizaciones',
        'migrations' => 'Control de migraciones',
        'failed_jobs' => 'Jobs fallidos',
        'password_reset_tokens' => 'Tokens de reset password',
        'personal_access_tokens' => 'Tokens de acceso'
    ];
    
    foreach($finalTables as $table => $description) {
        $exists = in_array($table, $remainingTables) ? '✅' : '❌';
        echo "  $exists $table - $description\n";
    }
    
    $extraTables = array_diff($remainingTables, array_keys($finalTables));
    if (!empty($extraTables)) {
        echo "\n⚠️ TABLAS EXTRA (verificar si son necesarias):\n";
        foreach($extraTables as $table) {
            echo "  ❓ $table\n";
        }
    }
    
    echo "\n🎉 ¡Limpieza final completada!\n";
    echo "💡 Base de datos lista para AvisOnline con estructura simplificada.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 