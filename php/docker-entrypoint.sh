#!/bin/bash
set -e
# Garante pastas de upload graváveis após git clone (volume monta ./www)
for d in uploads uploads/produtos uploads/categorias uploads/filiais uploads/servicos; do
  mkdir -p "/var/www/html/${d}"
done
chown -R www-data:www-data /var/www/html/uploads
chmod -R ug+rwX /var/www/html/uploads
exec apache2-foreground
