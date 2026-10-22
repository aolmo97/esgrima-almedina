# Almedina Club Manager v0.2 / v0.3 — documentación de desarrollo

Entorno desplegado: Coolify (`…sslip.io/gestion/`). **No** está en esgrimacordoba.es.

## 1. Arquitectura

- Plugin WordPress (PHP ≥ 7.4) en `wp-content/plugins/almedina-club-manager/`. Sin build: JavaScript vanilla servido como ficheros estáticos.
- `almedina-club-manager.php`: arranque, ruta `/gestion/`, ruta pública `/resultados/{token}/`, migración por versión, API del calendario (heredada de v0.1, sin cambios de comportamiento).
- `includes/class-schema.php`: tablas (dbDelta, aditivo). `includes/class-rest.php`: API REST nueva. La carga de ambos va en `try/catch (Throwable)`: si fallara, el calendario y el sitio siguen vivos y se muestra un aviso en wp-admin.
- `assets/engine.js`: motor de torneos en funciones puras (poules, reparto, orden de asaltos, clasificación, cuadro, avance). Se prueba con Node y se ejecuta igual en el navegador.
- `assets/shared.js` (vistas comunes), `core.js` (API, modales), `app.js` (menú lateral + router por hash), `view-*.js` (una por sección), `public.js` (página pública).
- Persistencia: tablas `{prefijo}almedina_events` (existente, intacta), `_fencers`, `_tournaments`, `_participants`, `_bouts`. Volumen `db_data` en Coolify.
- Seguridad: capacidad `manage_almedina_calendar` en todas las rutas salvo `/public/{token}`; nonce de WordPress con sesión por cookie; validación de tipos/longitudes/valores en servidor; SQL siempre con `$wpdb->prepare/insert/update`; cabeceras `no-store` y `noindex` en toda la API; salida escapada en el frontend.

## 2. Qué se ha implementado (guarda y recupera datos reales)

| Sección | Estado |
|---|---|
| Menú lateral responsive (hamburguesa en móvil) | Hecho |
| Dashboard (próximas competiciones, contadores, torneos en curso) | Hecho |
| Calendario (v0.1 conservada: alta/edición/baja, filtros FAE/FADE/RFEE/Otra, mensual/listado, imprimir) | Hecho |
| Tiradores (alta, edición, búsqueda, inactivos, armas, club, licencia) | Hecho |
| Gestor de torneos: crear/editar/borrar, vínculo opcional con el calendario, inscripción con cabeza de serie, alta rápida | Hecho |
| Poules automáticas (nº configurable, serpiente por serie, separación de clubes, orden de asaltos sin repetir tirador consecutivo) | Hecho |
| Resultados de poule (tocados, ganador, prioridad en empate, corrección) y clasificación (V/A, índice, TD, desempate por serie, empates marcados) | Hecho |
| Cuadro de eliminación directa (byes para los mejores, avance automático, bloqueo si la ronda siguiente ya empezó, clasificación final con empates por ronda) | Hecho |
| Marcador móvil (tocados con tope, cronómetro por periodos con descanso, minuto extra, sorteo de prioridad, tarjetas amarilla/roja/negra, deshacer, pantalla despierta, guardado en vivo y resultado final en el asalto del torneo) | Hecho |
| Resultados (clasificaciones, matrices de poule, cuadro; imprimible) | Hecho |
| Enlace público de solo lectura por torneo (`/resultados/{token}/`, se refresca cada 15 s, sin datos personales) | Hecho |
| Configuración (versión, copia de seguridad JSON) | Hecho |

Inspirado en las capacidades públicas de fencer.app (poules, DE, marcador con tarjetas/prioridad/minuto extra, resultados desde otros dispositivos). Implementación propia.

## 3. Pruebas ejecutadas

- **Motor (Node, `node --test wp-content/plugins/almedina-club-manager/tests/`)**: 26 pruebas — nº de poules, cobertura todos-contra-todos, sin repetición consecutiva (n = 5–8), reparto en serpiente, separación de clubes, validación de resultados, estadísticas, desempates, orden de cabezas de serie del cuadro, byes, avance, torneo completo de 8 hasta campeón, escape de HTML.
- **API (`tests/api-integration.js`, ejecutada en el navegador contra Coolify con sesión real)**: 107 comprobaciones — sin sesión (401), CSRF sin nonce, validación de todos los campos (tipos, longitudes, rangos), tiradores, torneos, inscripción, poules (casos inválidos y válidos), resultados y prioridad, reapertura, bloqueos 409, cuadro completo con byes y avance, finalización automática, vista pública sin sesión y sin datos privados, backup, dashboard, regresión del calendario, borrado en cascada y limpieza. Resultado: 107 superadas, 0 fallidas.
- **Interfaz (recorrido real en Coolify)**: crear torneo desde el formulario, inscribir 8 tiradores, sorteo de 2 poules, 12 resultados (uno con empate y prioridad), clasificación, cuadro de 8, un asalto jugado en el marcador (cronómetro, tocados, tarjeta roja = punto al rival, deshacer, autoguardado, fin y avance a semifinales), resto del cuadro desde el torneo, torneo finalizado con campeón y clasificación final, página pública.
- **Móvil (iframe de 390 px)**: sin desbordamiento horizontal en todas las vistas tras corregir el marcador.
- **Persistencia**: datos del torneo y de la sesión conservados tras redespliegues completos de contenedores.

## 4. Pendiente / limitaciones (no se han ejecutado o no existen)

- Repesca, formatos de varias rondas de poules, equipos, cuadros con tercer puesto: no implementados.
- La tabla de orden de asaltos no es la oficial FIE (método de rotación con búsqueda sin repetición).
- Los desempates por sorteo no se sortean: se resuelven por cabeza de serie y se marcan con `*`.
- Cada marcador guarda en el asalto, pero **no hay modo "participantes introducen sus propios tocados"** (solo el administrador).
- No hay importación/restauración desde el JSON de copia de seguridad: la copia es de exportación; ver §5.
- No probado: acceso de usuario **no administrador** (solo revisado en código), impresión real del navegador (solo reglas `@media print` revisadas), dispositivos físicos y otros navegadores, carga con muchos torneos.
- Sin PHP local: la sintaxis PHP se valida con un analizador y, en la práctica, al ejecutarse en Coolify.
- Auto-deploy de Coolify desactivado: cada cambio requiere "Redeploy" manual.

## 5. Copias de seguridad y rollback

- **Datos del plugin**: Configuración → "Descargar copia (JSON)" (o `GET /wp-json/almedina/v1/backup`). Contiene las 5 tablas. Antes de este despliegue se exportó el calendario (0 registros).
- **Copia completa recuperable** (base de datos + ficheros): All-in-One WP Migration (ya en `wp-content/plugins/`) o instantánea del volumen `db_data` en el servidor de Coolify. El JSON sirve como respaldo de datos, pero la restauración requiere importarlo a mano en la base de datos.
- **Rollback de código**: `git revert` del commit y "Redeploy" en Coolify. Las tablas nuevas son aditivas y no afectan a la versión anterior (que solo usa `almedina_events`); no hace falta borrarlas.
- **Rollback rápido**: desactivar el plugin en wp-admin (no borra datos).

## 6. Despliegue

`git push origin main` → en Coolify, aplicación "Esgrima almedina" → **Redeploy**. En el primer arranque con la nueva versión, `maybe_upgrade` ejecuta dbDelta y refresca las reglas de reescritura. Las variables `SERVICE_PASSWORD_64_*` (claves de WordPress) las genera Coolify: no las borres o se cerrarán las sesiones.

## 7. Novedades v0.3.0

- **Marca del club**: el panel, la página pública y los documentos impresos usan el logo, el nombre, el eslogan y el color principal de «Ajustes del sitio» de la web (campos ACF `logo_sitio`, `nombre_marca`, `eslogan_marca`, `color_primario`), con valores por defecto si no existen. Menú lateral oscuro (pizarra de la web) con acento verde corporativo. Sin fuentes externas.
- **Imprimir / PDF** en cada torneo (`#/imprimir/{id}/{tipo}`), A4 con logo: lista de inscritos, **hojas de poule en blanco** (matriz, orden de asaltos, pista/árbitro/hora) y **cuadro en blanco** (apaisado; con la clasificación real si las poules han terminado o con «Clasificado nº k» si no) para llevar el torneo **a mano**, y resultados con datos. PDF = diálogo de impresión del navegador → «Guardar como PDF» (no se genera un PDF en servidor).
- **Indexación y acceso**: `/gestion/` exige sesión de WordPress (sin sesión redirige al login) y la capacidad `manage_almedina_calendar`; responde con `X-Robots-Tag: noindex, nofollow`, `<meta robots noindex>` y `Cache-Control: no-store`. `robots.txt` (virtual de WordPress) añade `Disallow: /gestion/` y `Disallow: /wp-json/almedina/`. La página pública `/resultados/{token}/` también es `noindex` y no aparece en el sitemap. Comprobado con pruebas automáticas (112 en total).
- **Aviso**: si el servidor tiene un `robots.txt` físico, WordPress no lo genera y la regla no se añade; la cabecera y la meta `noindex` siguen protegiendo. No se ha comprobado el `robots.txt` de producción (el plugin no está instalado allí).
- **Pruebas**: 29 en Node (motor y vistas) y 112 de API/integración. No ejecutado: diálogo de impresión real y PDF generado en un navegador concreto (se revisó la maquetación en pantalla y las reglas `@media print`).
