<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\AvisOnlinePermissionsSeeder;

class RestructurePermissionsAvisOnline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avisonline:restructure-permissions 
                            {--force : Forzar la reestructuración sin confirmación}
                            {--dry-run : Mostrar cambios sin ejecutar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reestructura los permisos del sistema para AvisOnline de forma segura';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 REESTRUCTURACIÓN DE PERMISOS AVISONLINE');
        $this->info('==========================================');
        
        // Verificar entorno
        $environment = app()->environment();
        $this->info("📍 Entorno actual: {$environment}");
        
        if ($environment === 'production' && !$this->option('force')) {
            $this->error('❌ ENTORNO DE PRODUCCIÓN DETECTADO');
            $this->warn('⚠️ Para ejecutar en producción, usa --force');
            $this->warn('⚠️ ASEGÚRATE DE HACER UN BACKUP DE LA BASE DE DATOS PRIMERO');
            return 1;
        }
        
        // Mostrar información de cambios
        $this->showChangesInfo();
        
        // Dry run
        if ($this->option('dry-run')) {
            $this->info('🔍 MODO DRY-RUN - Solo mostrando cambios');
            return 0;
        }
        
        // Confirmación
        if (!$this->option('force')) {
            if (!$this->confirm('¿Proceder con la reestructuración?', false)) {
                $this->info('❌ Operación cancelada');
                return 1;
            }
        }
        
        // Ejecutar reestructuración
        try {
            $this->info('⚡ Iniciando reestructuración...');
            
            $seeder = new AvisOnlinePermissionsSeeder();
            $seeder->run();
            
            $this->info('✅ Reestructuración completada exitosamente!');
            $this->showPostRestructureInfo();
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la reestructuración:');
            $this->error($e->getMessage());
            $this->warn('⚠️ Revisa los logs para más detalles');
            return 1;
        }
    }
    
    /**
     * Mostrar información de los cambios que se realizarán
     */
    private function showChangesInfo()
    {
        $this->info('📋 CAMBIOS QUE SE REALIZARÁN:');
        $this->line('');
        
        $this->line('🗂️ PERMISOS ANTERIORES → NUEVOS:');
        $this->table(
            ['Permiso Anterior', 'Permiso Nuevo', 'Descripción'],
            [
                ['manage-users', 'manage-users', 'Gestionar usuarios, roles y permisos'],
                ['manage-products', 'manage-all-announcements', 'Gestionar todos los anuncios'],
                ['manage-own-products', 'manage-own-announcements', 'Solo anuncios propios'],
                ['(nuevo)', 'full-admin', 'Super administrador'],
                ['(nuevo)', 'manage-categories', 'Gestionar categorías'],
                ['(nuevo)', 'manage-sliders', 'Gestionar sliders'],
            ]
        );
        
        $this->line('');
        $this->line('👥 ROLES:');
        $this->table(
            ['Rol', 'Permisos Asignados'],
            [
                ['Admin', 'Todos los permisos (incluyendo full-admin)'],
                ['Usuario', 'Solo manage-own-announcements']
            ]
        );
        
        $this->line('');
        $this->warn('⚠️ IMPORTANTE:');
        $this->warn('• Los usuarios existentes mantendrán sus roles');
        $this->warn('• Se preservará al menos un usuario administrador');
        $this->warn('• Los permisos antiguos serán migrados automáticamente');
        $this->line('');
    }
    
    /**
     * Mostrar información post-reestructuración
     */
    private function showPostRestructureInfo()
    {
        $this->line('');
        $this->info('📊 PRÓXIMOS PASOS RECOMENDADOS:');
        $this->line('');
        
        $this->line('1️⃣ FRONTEND - Actualizar referencias:');
        $this->line('   • Cambiar manage-products → manage-all-announcements');
        $this->line('   • Cambiar manage-own-products → manage-own-announcements');
        $this->line('');
        
        $this->line('2️⃣ BACKEND - Actualizar rutas:');
        $this->line('   • Cambiar middleware permission:manage-products');
        $this->line('   • Por permission:manage-all-announcements');
        $this->line('');
        
        $this->line('3️⃣ VERIFICAR FUNCIONAMIENTO:');
        $this->line('   • Probar login de usuarios');
        $this->line('   • Verificar permisos en el frontend');
        $this->line('   • Confirmar acceso a las secciones');
        $this->line('');
        
        $this->info('🎯 COMANDOS ÚTILES:');
        $this->line('   php artisan avisonline:verify-permissions');
        $this->line('   php artisan avisonline:show-user-permissions {email}');
        $this->line('');
        
        $this->info('✨ ¡La reestructuración está completa!');
    }
}
