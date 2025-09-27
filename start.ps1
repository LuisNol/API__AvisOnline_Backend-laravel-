# Script para iniciar Laravel con Docker
Write-Host "Iniciando Laravel con Docker..." -ForegroundColor Green

# Verificar que existe archivo .env
if (!(Test-Path ".env")) {
    Write-Host "No se encontro archivo .env. Por favor crea uno basado en env.docker" -ForegroundColor Red
    exit 1
} else {
    Write-Host "Usando archivo .env existente..." -ForegroundColor Green
    
    # Actualizar variables para Docker
    Write-Host "Actualizando variables para Docker..." -ForegroundColor Yellow
    $envContent = Get-Content ".env" -Raw
    
    # Actualizar DB_HOST para Docker
    $envContent = $envContent -replace "DB_HOST=.*", "DB_HOST=db"
    $envContent = $envContent -replace "DB_DATABASE=.*", "DB_DATABASE=avisonline"
    $envContent = $envContent -replace "DB_USERNAME=.*", "DB_USERNAME=root"
    $envContent = $envContent -replace "DB_PASSWORD=.*", "DB_PASSWORD=root_password"
    
    # Actualizar APP_URL para Docker
    $envContent = $envContent -replace "APP_URL=.*", "APP_URL=http://localhost:8000"
    
    Set-Content ".env" $envContent
    Write-Host "Variables actualizadas para Docker" -ForegroundColor Green
}

# Construir y levantar contenedores
Write-Host "Construyendo contenedores..." -ForegroundColor Yellow
docker-compose build

Write-Host "Levantando contenedores..." -ForegroundColor Yellow
docker-compose up -d

# Esperar a que la base de datos este lista
Write-Host "Esperando a que la base de datos este lista..." -ForegroundColor Yellow
Start-Sleep -Seconds 20

# Generar clave de aplicacion
Write-Host "Generando clave de aplicacion..." -ForegroundColor Yellow
docker-compose exec app php artisan key:generate

# Crear enlace simbólico de storage
Write-Host "Creando enlace simbólico de storage..." -ForegroundColor Yellow
docker-compose exec app php artisan storage:link

# Limpiar cache
Write-Host "Limpiando cache..." -ForegroundColor Yellow
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Optimizar aplicacion
Write-Host "Optimizando aplicacion..." -ForegroundColor Yellow
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app php artisan optimize

Write-Host "Configuracion completada!" -ForegroundColor Green
Write-Host "Aplicacion disponible en: http://localhost:8000" -ForegroundColor Cyan
Write-Host "phpMyAdmin disponible en: http://localhost:8081" -ForegroundColor Cyan
Write-Host "Credenciales phpMyAdmin:" -ForegroundColor Yellow
Write-Host "  Usuario: root" -ForegroundColor White
Write-Host "  Contrasena: root_password" -ForegroundColor White
