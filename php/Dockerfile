FROM php:8.2-apache

# Instala extensões necessárias
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Ativa mod_rewrite (útil para URLs amigáveis no futuro)
RUN a2enmod rewrite

# Copia arquivos de configuração customizados (opcional)
# COPY 000-default.conf /etc/apache2/sites-available/000-default.conf