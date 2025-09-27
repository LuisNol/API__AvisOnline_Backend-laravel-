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
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libgd-dev \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Obtener Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar extension de Redis
RUN pecl install redis && docker-php-ext-enable redis

# Crear usuario para Laravel
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copiar archivos de la aplicación
COPY . /var/www/API__AvisOnline_Backend-laravel-

# Cambiar permisos de TODO el directorio
RUN chown -R www:www /var/www/API__AvisOnline_Backend-laravel-

# Establecer directorio de trabajo
WORKDIR /var/www/API__AvisOnline_Backend-laravel-

# Cambiar al usuario www
USER www

# Exponer puerto
EXPOSE 9000

# Comando por defecto
CMD ["php-fpm"]
