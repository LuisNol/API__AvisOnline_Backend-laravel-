#!/bin/bash

# Script de despliegue para producción en Ubuntu Server
# Dominio: https://apis.avisonline.store

echo "🚀 Iniciando despliegue de producción..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para mostrar mensajes
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar que estamos en el directorio correcto
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

# Crear directorio para certificados SSL
print_status "Creando directorio para certificados SSL..."
mkdir -p ssl

# Verificar si existen certificados SSL
if [ ! -f "ssl/cert.pem" ] || [ ! -f "ssl/key.pem" ]; then
    print_warning "No se encontraron certificados SSL. Generando certificados autofirmados..."
    print_warning "IMPORTANTE: Para producción, reemplaza estos certificados con certificados válidos de Let's Encrypt o tu CA."
    
    # Generar certificados autofirmados (solo para desarrollo)
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout ssl/key.pem \
        -out ssl/cert.pem \
        -subj "/C=PE/ST=Lima/L=Lima/O=Avisonline/OU=IT/CN=apis.avisonline.store"
    
    print_warning "Certificados autofirmados generados. Reemplaza con certificados válidos para producción."
fi

# Parar contenedores existentes
print_status "Deteniendo contenedores existentes..."
docker-compose -f docker-compose.prod.yml down

# Construir y levantar contenedores de producción
print_status "Construyendo y levantando contenedores de producción..."
docker-compose -f docker-compose.prod.yml build --no-cache
docker-compose -f docker-compose.prod.yml up -d

# Esperar a que la base de datos esté lista
print_status "Esperando a que la base de datos esté lista..."
sleep 30

# Ejecutar comandos de Laravel
print_status "Ejecutando comandos de Laravel..."

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

# Verificar estado de los contenedores
print_status "Verificando estado de los contenedores..."
docker-compose -f docker-compose.prod.yml ps

# Mostrar información de acceso
echo ""
print_status "🎉 Despliegue completado exitosamente!"
echo ""
echo "📋 Información de acceso:"
echo "  🌐 API: https://apis.avisonline.store"
echo "  🗄️  phpMyAdmin: https://apis.avisonline.store:8081"
echo "  📊 Base de datos: avisonline"
echo ""
echo "🔧 Comandos útiles:"
echo "  Ver logs: docker-compose -f docker-compose.prod.yml logs -f"
echo "  Reiniciar: docker-compose -f docker-compose.prod.yml restart"
echo "  Detener: docker-compose -f docker-compose.prod.yml down"
echo ""
print_warning "⚠️  IMPORTANTE: Configura tu dominio apis.avisonline.store para que apunte a este servidor."
print_warning "⚠️  Reemplaza los certificados SSL autofirmados con certificados válidos de Let's Encrypt."
