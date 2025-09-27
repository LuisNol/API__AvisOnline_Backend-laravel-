FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Redis
RUN pecl install redis && docker-php-ext-enable redis

# Crear usuario para Laravel
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar archivos de la aplicación
COPY . /var/www

# Cambiar permisos de TODO el directorio
RUN chown -R www:www /var/www

# Cambiar al usuario www
USER www

# Exponer puerto
EXPOSE 9000

# Comando por defecto
CMD ["php-fpm"]
