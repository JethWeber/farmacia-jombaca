FROM php:8.2-apache

# Instala extensões necessárias
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Ativa mod_rewrite (útil para URLs amigáveis no futuro)
RUN a2enmod rewrite

# Limites de upload (imagens de produtos, filiais, etc.)
RUN { \
  echo 'upload_max_filesize = 16M'; \
  echo 'post_max_size = 24M'; \
  echo 'memory_limit = 256M'; \
} > /usr/local/etc/php/conf.d/farmacia-uploads.ini

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Copia arquivos de configuração customizados (opcional)
# COPY 000-default.conf /etc/apache2/sites-available/000-default.conf