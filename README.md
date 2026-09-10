# Sala de Esgrima Almedina Córdoba (S.E.A.C.) — Web WordPress

## Estructura del proyecto

```
web-club-esgrima/
├── docker-compose.yml
├── .env.example
├── wp-content/
│   ├── themes/
│   │   └── seac-theme/          ← tema propio (código versionado)
│   │       ├── style.css
│   │       ├── functions.php
│   │       ├── header.php
│   │       ├── footer.php
│   │       ├── front-page.php   ← Home
│   │       ├── page-quienes-somos.php
│   │       ├── page-contactanos.php
│   │       ├── template-parts/
│   │       ├── inc/
│   │       │   └── acf-fields.php   ← definición de campos ACF
│   │       └── assets/
│   └── plugins/                 ← ACF y otros plugins (versionados)
```

## 1. Arranque en local

```bash
cp .env.example .env
docker compose up -d
```

- WordPress: http://localhost:8080
- Adminer (gestor de base de datos): http://localhost:8081
  - Sistema: MySQL, Servidor: `db`, Usuario/contraseña: los del `.env`

Primer arranque: sigue el asistente de instalación de WordPress (idioma, título del sitio, usuario admin).

### Plugins necesarios (instalar desde el admin de WP o vía WP-CLI)
- **Advanced Custom Fields (ACF)** — gestión de campos personalizados
- **Custom Post Type UI** (opcional, si prefieres GUI en vez de código para los CPTs)

## 2. Despliegue de prueba en Coolify

1. Sube este repo a tu Git (GitHub/Gitea/etc. — lo que uses con Coolify).
2. En Coolify: **New Resource → Docker Compose**, apunta al repo.
3. Coolify detectará el `docker-compose.yml`. Configura las variables de entorno equivalentes al `.env` (usa contraseñas fuertes en producción, no las de ejemplo).
4. Ajusta el dominio/subdominio de prueba (ej. `seac-test.tudominio.com`) y deja que Coolify gestione el proxy/TLS (como ya haces con tus otros proyectos).
5. Despliega. El volumen `wp_uploads` y `db_data` persisten entre despliegues.

### Notas para pruebas
- El tema y los plugins van en el propio repo (bind mount), así que cualquier cambio de código se despliega igual que en tus otros proyectos: commit → push → redeploy en Coolify.
- Los `uploads` y la base de datos NO se versionan (son volúmenes), así que el contenido que suba el club (fotos, textos) persiste entre despliegues pero no viaja con el código.
- Para pasar de "pruebas" a producción real, cambia el dominio, revisa backups de `db_data` y `wp_uploads`, y desactiva `WORDPRESS_DEBUG`.

## 3. Convención de campos ACF (ver `inc/acf-fields.php`)

| Página / CPT | Campo | Tipo |
|---|---|---|
| Home | `hero_titulo`, `hero_texto`, `hero_imagen` | Texto / WYSIWYG / Imagen |
| Home | Repetidor `modalidades` (nombre, icono, descripción) | Repeater |
| Home | Repetidor `horarios` (categoría, días, hora_inicio, hora_fin) | Repeater |
| Quiénes somos | `historia_bloques` (WYSIWYG por bloque) | Repeater / WYSIWYG |
| Quiénes somos | Galería general | Galería nativa |
| CPT `miembro_equipo` | `nombre`, `nacionalidad`, `arma`, `foto`, `biografia`, `destacado` (bool, para el maestro) | Custom Post Type + campos |
| Contáctanos | `direccion`, `telefono`, `email`, `mapa` (Google Map o iframe) | Texto / Google Map |
