#!/bin/bash

# Script de actualización rápida para producción
# Solo actualiza cambios sin recrear todo

echo "🚀 Iniciando actualización rápida..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Funciones de log
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar que existe docker-compose.prod.yml
if [ ! -f "docker-compose.prod.yml" ]; then
    print_error "No se encontró docker-compose.prod.yml. Ejecuta este script desde el directorio raíz del proyecto."
    exit 1
fi

# Verificar que existe el archivo .env
if [ ! -f ".env" ]; then
    print_error "No se encontró .env. Asegúrate de tener el archivo .env configurado."
    exit 1
fi

# Actualizar variables para producción en el .env existente
print_status "Actualizando variables para producción..."
sed -i 's/APP_ENV=local/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
sed -i 's|APP_URL=http://localhost:8000|APP_URL=https://apis.avisonline.store|' .env
sed -i 's|GOOGLE_REDIRECT_URI=http://localhost:8000/auth/main|GOOGLE_REDIRECT_URI=https://apis.avisonline.store/auth/main|' .env

# Arreglar permisos del directorio
print_status "Arreglando permisos del directorio..."
chown -R root:root /var/www
chmod -R 755 /var/www

# Configurar Git para el directorio
git config --global --add safe.directory /var/www

# Crear directorio vendor
print_status "Creando directorio vendor..."
mkdir -p /var/www/vendor
chown -R root:root /var/www/vendor
chmod -R 755 /var/www/vendor

# Crear directorio vendor en el proyecto también
mkdir -p vendor
chown -R root:root vendor
chmod -R 755 vendor

# Crear directorio para certificados SSL
print_status "Creando directorio para certificados SSL..."
mkdir -p ssl

# Generar certificados SSL autofirmados si no existen
if [ ! -f "ssl/nginx.crt" ] || [ ! -f "ssl/nginx.key" ]; then
    print_warning "No se encontraron certificados SSL. Generando certificados autofirmados..."
    print_warning "IMPORTANTE: Para producción, reemplaza estos certificados con certificados válidos de Let's Encrypt o tu CA."
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout ssl/nginx.key -out ssl/nginx.crt \
        -subj "/C=PE/ST=Lima/L=Lima/O=Avisonline/OU=IT/CN=apis.avisonline.store"
    print_warning "Certificados autofirmados generados. Reemplaza con certificados válidos para producción."
fi

# Solo reiniciar contenedores (no reconstruir)
print_status "Reiniciando contenedores..."
docker-compose -f docker-compose.prod.yml restart

# Esperar a que la base de datos esté lista
print_status "Esperando a que la base de datos esté lista..."
sleep 10

# Verificar que vendor existe (ya viene del proyecto)
print_status "Verificando dependencias del proyecto..."
if [ -d "vendor" ] && [ -f "vendor/autoload.php" ]; then
    print_status "Dependencias del proyecto encontradas. Usando dependencias existentes."
else
    print_warning "Vendor no encontrado en el directorio actual. Verificando en el directorio del proyecto..."
    if [ -d "/var/www/API__AvisOnline_Backend-laravel-/vendor" ] && [ -f "/var/www/API__AvisOnline_Backend-laravel-/vendor/autoload.php" ]; then
        print_status "Dependencias encontradas en /var/www/API__AvisOnline_Backend-laravel-/vendor/"
    else
        print_error "Error: Dependencias no encontradas. Verifica que vendor/ existe en el proyecto."
        exit 1
    fi
fi

# Ejecutar comandos de Laravel
print_status "Ejecutando comandos de Laravel..."

# Verificar que vendor existe antes de ejecutar comandos
if [ -d "vendor" ] && [ -f "vendor/autoload.php" ]; then
    print_status "Dependencias instaladas correctamente. Ejecutando comandos de Laravel..."
    
    # Generar clave de aplicación
    docker-compose -f docker-compose.prod.yml exec app php artisan key:generate --force
    
    # Crear enlace simbólico de storage
    docker-compose -f docker-compose.prod.yml exec app php artisan storage:link
    
    # Limpiar cache
    docker-compose -f docker-compose.prod.yml exec app php artisan cache:clear
    docker-compose -f docker-compose.prod.yml exec app php artisan config:clear
    docker-compose -f docker-compose.prod.yml exec app php artisan route:clear
    docker-compose -f docker-compose.prod.yml exec app php artisan view:clear
    
    # Optimizar aplicación para producción
    print_status "Optimizando aplicación para producción..."
    docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
    docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
    docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
    docker-compose -f docker-compose.prod.yml exec app php artisan optimize
else
    print_error "Error: Las dependencias no se instalaron correctamente. Verifica los logs."
    print_warning "Puedes intentar instalar manualmente con:"
    print_warning "docker-compose -f docker-compose.prod.yml exec app composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-gd"
fi

# Verificar estado de los contenedores
print_status "Verificando estado de los contenedores..."
docker-compose -f docker-compose.prod.yml ps

print_status "🎉 Actualización completada exitosamente!"

echo -e "\n📋 Información de acceso:"
echo -e "  🌐 API: ${CYAN}https://apis.avisonline.store${NC}"
echo -e "  🗄️  phpMyAdmin: ${CYAN}https://apis.avisonline.store:8081${NC}"
echo -e "  📊 Base de datos: ${CYAN}avisonline${NC}"

echo -e "\n🔧 Comandos útiles:"
echo -e "  Ver logs: ${YELLOW}docker-compose -f docker-compose.prod.yml logs -f${NC}"
echo -e "  Reiniciar: ${YELLOW}docker-compose -f docker-compose.prod.yml restart${NC}"
echo -e "  Detener: ${YELLOW}docker-compose -f docker-compose.prod.yml down${NC}"

print_warning "⚠️  IMPORTANTE: Configura tu dominio apis.avisonline.store para que apunte a este servidor."
print_warning "⚠️  Reemplaza los certificados SSL autofirmados con certificados válidos de Let's Encrypt."
