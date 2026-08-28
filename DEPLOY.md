# Desplegar AulaMaster en el VPS (subdominio de prueba)

Guía paso a paso para levantar `aulamastertest.codermaster.com.co` en el mismo VPS donde ya corre `codermaster.com.co`, sin tocar nada de lo que ya funciona. El sitio principal sigue siendo atendido por el Nginx que ya está instalado en el sistema; AulaMaster corre dentro de Docker, en un puerto interno, y ese mismo Nginx lo reenvía hacia el subdominio.

No hace falta saber Docker de antes — cada comando de acá está explicado. Si algo falla, copiame el mensaje de error tal cual y seguimos desde ahí.

---

## 0. Qué se instaló en el repositorio

- `Dockerfile`: arma una sola imagen con PHP-FPM + Nginx + Supervisor adentro. Compila los assets de Vue en una etapa aparte (con Node) y solo copia el resultado ya compilado — Node no queda instalado en la imagen final.
- `docker-compose.yml`: levanta 4 contenedores — `app` (la web), `queue` (procesa notificaciones en segundo plano), `scheduler` (corre las tareas programadas: backups, recordatorios), y `mysql` (la base de datos, separada del resto de la app).
- `docker/`: las configuraciones que usan esos contenedores por dentro, más el bloque de Nginx que vas a copiar en el **host** (fuera de Docker).
- `.env.production.example`: la plantilla de variables de entorno para el VPS.

---

## 1. Requisitos en el VPS

Conectate por SSH al VPS y confirmá si Docker ya está instalado:

```bash
docker --version
docker compose version
```

Si no existen, instalalos (Ubuntu/Debian):

```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
```

Cerrá sesión SSH y volvé a entrar para que el usuario quede en el grupo `docker` (si no, vas a necesitar `sudo` antes de cada comando `docker`).

---

## 2. DNS del subdominio

Donde administres el DNS de `codermaster.com.co` (tu proveedor de dominio o Cloudflare), agregá un registro:

| Tipo | Nombre | Valor |
|---|---|---|
| A | `aulamastertest` | la IP pública del VPS |

Esperá unos minutos y confirmá que resuelve:

```bash
ping aulamastertest.codermaster.com.co
```

---

## 3. Subir el código al VPS

Este proyecto todavía no está en un repositorio Git remoto, así que la forma más simple es copiarlo directo con `rsync` desde tu máquina (excluye lo que no hace falta subir: se reconstruye dentro de Docker o son archivos locales de desarrollo).

Desde tu máquina Windows, en Git Bash, parado en `c:\laragon\www\INSTITUTO`:

```bash
rsync -avz --progress \
  --exclude node_modules \
  --exclude vendor \
  --exclude .git \
  --exclude storage/logs \
  --exclude .env \
  ./ usuario@IP_DEL_VPS:/home/usuario/aulamaster/
```

(Reemplazá `usuario`, `IP_DEL_VPS` y la ruta destino por los tuyos reales. Si preferís algo visual en vez de terminal, `WinSCP` o `FileZilla` hacen lo mismo por SFTP.)

Para actualizaciones futuras, repetís el mismo comando — `rsync` solo transfiere lo que cambió.

---

## 4. Configurar el `.env` en el VPS

Ya en el VPS, dentro de la carpeta del proyecto:

```bash
cd /home/usuario/aulamaster
cp .env.production.example .env
nano .env
```

Completá al menos:
- `DB_PASSWORD` y `DB_ROOT_PASSWORD`: inventá dos contraseñas fuertes distintas (esto crea el usuario y la base de datos de MySQL la primera vez que arranca el contenedor).
- Revisá `APP_URL` (ya viene con `https://aulamastertest.codermaster.com.co`).

Guardá y salí (`Ctrl+O`, Enter, `Ctrl+X` en nano).

---

## 5. Construir y levantar los contenedores

```bash
docker compose build
docker compose up -d
```

La primera vez tarda varios minutos (instala dependencias de PHP, compila el frontend, descarga la imagen de MySQL). Revisá que los 4 contenedores estén corriendo:

```bash
docker compose ps
```

Si alguno aparece reiniciándose en bucle, mirá sus logs:

```bash
docker compose logs app
docker compose logs mysql
```

---

## 6. Generar la llave de la app y migrar la base de datos

Estos comandos corren **dentro** del contenedor `app` (por eso el `docker compose exec app` adelante):

```bash
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=RoleSeeder --force
```

Si querés arrancar con un usuario admin ya creado, seguí el mismo patrón que usamos en desarrollo (`php artisan tinker`, ver `ACCESOS.md` como referencia de qué crear) pero ejecutándolo con `docker compose exec app php artisan tinker`.

---

## 7. Configurar el Nginx del VPS (el que ya tenés corriendo)

El archivo `docker/host-nginx.conf.example` de este repo ya trae los comandos exactos en su cabecera. En resumen:

```bash
sudo cp docker/host-nginx.conf.example /etc/nginx/sites-available/aulamastertest.codermaster.com.co
sudo ln -s /etc/nginx/sites-available/aulamastertest.codermaster.com.co /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

`nginx -t` valida que la configuración esté bien escrita antes de aplicarla — si tira error, no se recarga nada y tu sitio principal sigue intacto.

---

## 8. Certificado HTTPS

```bash
sudo certbot --nginx -d aulamastertest.codermaster.com.co
```

Certbot edita automáticamente el archivo que copiaste en el paso 7 para agregar el bloque HTTPS y el redirect de HTTP a HTTPS. Si nunca usaste Certbot en este VPS, instalalo primero: `sudo apt install certbot python3-certbot-nginx`.

---

## 9. Verificar que todo funciona

Abrí `https://aulamastertest.codermaster.com.co/login` en el navegador. Deberías ver la pantalla de login de AulaMaster con el candado de HTTPS.

Si algo no carga:

```bash
docker compose logs -f app       # errores de la aplicación
docker compose logs -f queue     # si las notificaciones no llegan
docker compose logs -f scheduler # si los recordatorios/backups no corren
sudo tail -f /var/log/nginx/error.log   # si el proxy del host no conecta
```

---

## 10. Comandos del día a día

| Qué querés hacer | Comando |
|---|---|
| Ver logs en vivo | `docker compose logs -f app` |
| Reiniciar todo | `docker compose restart` |
| Apagar todo | `docker compose down` (los datos de MySQL y `storage/` sobreviven, están en volúmenes aparte) |
| Actualizar código | `rsync` de nuevo (paso 3) → `docker compose build` → `docker compose up -d` |
| Correr una migración nueva | `docker compose exec app php artisan migrate --force` |
| Entrar a una consola de Tinker | `docker compose exec app php artisan tinker` |
| Backup manual | `docker compose exec app php artisan backup:run` |

---

## 11. Pendientes ya documentados en `CHECKLIST.md`

Esto no es específico de Docker, pero aplica igual una vez desplegado:
- Cambiar la contraseña del admin de prueba antes de compartir la URL con nadie más.
- `MAIL_MAILER=log` sigue mandando los correos al log en vez de enviarlos de verdad — cambialo cuando tengas un proveedor de correo real.
- Wompi sigue con las llaves vacías — completalas en el `.env` del VPS cuando las tengas, no hace falta reconstruir la imagen, solo `docker compose restart app queue`.

---

**Importante — esto no se probó corriendo en un VPS real.** Se armó y se revisó con cuidado, pero no tengo acceso a tu servidor para levantarlo yo mismo (a diferencia de todo lo demás en este proyecto, que sí validé corriendo). Andá paso por paso, y si algún comando tira un error, pegame el mensaje completo y lo resolvemos juntos.
