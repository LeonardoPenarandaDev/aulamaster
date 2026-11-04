#!/bin/sh
set -e

# El volumen de `storage` puede montarse vacío la primera vez (Docker copia
# el contenido original de la imagen dentro), así que los permisos hay que
# reforzarlos en cada arranque, no solo en el build de la imagen.
chown -R www-data:www-data /var/www/html/storage

# Cachea configuración, rutas y eventos al arrancar (no en el build) porque
# las variables de entorno llegan por `env_file` en tiempo de ejecución.
# bootstrap/cache vive dentro de cada contenedor, no en el volumen compartido.
php artisan config:cache
php artisan route:cache
php artisan event:cache
chown -R www-data:www-data /var/www/html/bootstrap/cache

exec "$@"
