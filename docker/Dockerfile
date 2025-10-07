# Usar a imagem oficial do PHP 8.2 FPM como base
FROM php:8.2-fpm-alpine

# Definir o diretório de trabalho
WORKDIR /var/www

# Instalar dependências persistentes, dependências de compilação,
# compilar as extensões PHP e depois limpar as dependências de compilação.
# TUDO EM UM ÚNICO COMANDO 'RUN' PARA OTIMIZAR O TAMANHO DA IMAGEM.
RUN apk add --no-cache \
        # Dependências que o Laravel precisa em tempo de execução
        postgresql-dev \
        libzip-dev \
        zip \
        unzip \
        nodejs \
        npm \
    # Adicionar dependências de compilação em um grupo virtual
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS autoconf gcc libc-dev make \
    \
    # ---- INSTALAR EXTENSÕES ----
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_pgsql zip \
    \
    # ---- LIMPEZA ----
    # Remover o grupo de dependências de compilação
    && apk del .build-deps

# Instalar o nodemon globalmente usando o npm
RUN npm install -g nodemon

# Limpar o cache do apk
RUN rm -rf /var/cache/apk/*

# Instalar o Composer (gerenciador de dependências do PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar os arquivos da aplicação para o diretório de trabalho
COPY . /var/www

# Definir o usuário que rodará a aplicação
RUN addgroup -g 1000 laravel && adduser -u 1000 -G laravel -s /bin/sh -D laravel
USER laravel

# Expor a porta 9000 para o PHP-FPM
EXPOSE 9000

# Comando padrão
CMD ["php-fpm"]
