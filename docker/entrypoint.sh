#!/bin/sh
set -e

# El volumen de `storage` puede montarse vacío la primera vez (Docker copia
# el contenido original de la imagen dentro), así que los permisos hay que
# reforzarlos en cada arranque, no solo en el build de la imagen.
chown -R www-data:www-data /var/www/html/storage

exec "$@"
