# 🚀 GUÍA PASO A PASO - MIGRACIÓN SEGURA AVISONLINE

## ⚠️ ANTES DE EMPEZAR
**IMPORTANTE: Lee todo antes de ejecutar cualquier comando**

---

## 🔍 PASO 1: VERIFICACIÓN PRE-MIGRACIÓN (2 minutos)

```bash
# Ver qué cambios se realizarán (SIN EJECUTAR)
php artisan avisonline:restructure-permissions --dry-run
```

**🎯 Qué debes ver:**
- Lista de permisos que se crearán
- Roles que se asignarán
- Usuarios que se actualizarán

---

## 💾 PASO 2: HACER BACKUP (1 minuto)

```bash
# Opción 1: Backup manual de la BD (recomendado)
# Usa tu herramienta preferida (phpMyAdmin, HeidiSQL, etc.)

# Opción 2: Si tienes configurado backup en Laravel
# php artisan backup:run
```

**✅ Confirmar:** Tienes backup de la base de datos

---

## 🚀 PASO 3: EJECUTAR MIGRACIÓN (2 minutos)

```bash
# Ejecutar la reestructuración
php artisan avisonline:restructure-permissions
```

**🎯 Qué debes ver:**
- ✅ Permisos creados exitosamente
- ✅ Roles asignados correctamente  
- ✅ Usuarios procesados
- ✅ "Reestructuración completada exitosamente!"

**❌ Si hay errores:**
- Para inmediatamente
- Restaura el backup
- Contacta para resolver el problema

---

## 🧪 PASO 4: PRUEBAS BÁSICAS (5 minutos)

### 4.1 Probar Login
```bash
# En tu navegador, ir a: http://localhost:5000
# Intentar hacer login con tu usuario admin
```

### 4.2 Verificar Permisos en Consola
```bash
# Verificar que los permisos se crearon
php artisan tinker
>>> \App\Models\Permission::all()->pluck('name');
# Debes ver: full-admin, manage-users, manage-all-announcements, etc.

>>> exit
```

### 4.3 Probar Sidebar
- Iniciar sesión como admin → Debes ver todas las secciones
- Crear un usuario normal → Solo debe ver "Anuncios"

---

## ✅ PASO 5: VERIFICACIÓN COMPLETA (Si todo funciona)

### 5.1 Verificar Base de Datos
```sql
-- En tu cliente de BD, ejecutar:
SELECT p.name, p.description FROM permissions p;
SELECT r.name, r.description FROM roles r;
SELECT COUNT(*) FROM role_user; -- Debe tener al menos 1 admin
```

### 5.2 Probar Funcionalidades
- [ ] Login funciona
- [ ] Sidebar se muestra correctamente según rol
- [ ] Usuarios admin ven todo
- [ ] Usuarios normales solo ven anuncios
- [ ] No hay errores en consola del navegador

---

## 🧹 PASO 6: LIMPIEZA (Solo después de 1 semana sin problemas)

```bash
# ⚠️ NO EJECUTAR INMEDIATAMENTE - ESPERAR 1 SEMANA

# Crear carpeta de backup
mkdir -p app/Console/Commands/backup
mkdir -p database/seeders/backup

# Mover archivos obsoletos
mv database/seeders/RolesAndPermissionsSeeder.php database/seeders/backup/
mv app/Console/Commands/PermissionsFixOwnProducts.php app/Console/Commands/backup/
mv app/Console/Commands/RecreateOwnProductsPermission.php app/Console/Commands/backup/
mv app/Console/Commands/FixPermissions.php app/Console/Commands/backup/

# Después de 1 mes, eliminar la carpeta backup si todo funciona bien
```

---

## 🚨 PLAN DE ROLLBACK (En caso de problemas)

### Si algo sale mal DURANTE la migración:
```bash
# 1. Restaurar backup de BD inmediatamente
# 2. Revertir Kernel.php:
#    - Cambiar: 'permission' => \App\Http\Middleware\CheckPermission::class,
# 3. Reiniciar servidor web
```

### Si algo sale mal DESPUÉS de la migración:
```bash
# 1. Temporalmente, cambiar en Kernel.php:
'permission' => \App\Http\Middleware\CheckPermission::class,

# 2. Esto te dará tiempo para investigar sin afectar usuarios
# 3. Una vez resuelto, volver a:
'permission' => \App\Http\Middleware\CheckPermissionAvisOnline::class,
```

---

## 📋 CHECKLIST FINAL

**Antes de migrar:**
- [ ] Leí toda la guía
- [ ] Tengo backup de la BD
- [ ] Estoy en entorno de desarrollo/pruebas
- [ ] Tengo tiempo para hacer pruebas

**Después de migrar:**
- [ ] Login funciona correctamente
- [ ] Permisos se aplican según el rol
- [ ] No hay errores en logs
- [ ] Frontend muestra las secciones correctas
- [ ] Usuarios admin tienen acceso completo
- [ ] Usuarios normales solo ven sus anuncios

**Para limpieza (después de 1 semana):**
- [ ] Sistema funciona sin problemas
- [ ] No hay errores relacionados con permisos
- [ ] Usuarios están satisfechos con el funcionamiento
- [ ] Listo para eliminar archivos obsoletos

---

## 💡 CONSEJOS

1. **Haz la migración en viernes tarde** - Para tener fin de semana para resolver problemas
2. **Avisa a los usuarios** - Que puede haber cambios menores en la interfaz
3. **Monitorea logs** - Especialmente los primeros días
4. **Ten paciencia** - Es normal que haya pequeños ajustes

---

## 📞 ¿PROBLEMAS?

Si algo no funciona como esperabas:
1. **No entres en pánico** - Todo tiene solución
2. **Revisa logs** en `storage/logs/laravel.log`
3. **Restaura backup** si es crítico
4. **Documenta el error** para poder ayudarte mejor

¡Listo para empezar! 🚀 