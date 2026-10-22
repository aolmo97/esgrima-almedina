# Almedina Club Manager v0.1.1 — informe de pruebas e instalación en producción

Paquete: `almedina-club-manager-v0.1.1.zip` (en esta misma carpeta). Código fuente: `wp-content/plugins/almedina-club-manager/`.

## 1. Entorno de pruebas (Coolify)

- URL: `https://bx433vat7rf4bpz0nhloa25y.217.154.105.133.sslip.io/gestion/` (requiere cuenta administradora de ese WordPress de pruebas).
- Es un WordPress aparte (WordPress + MySQL 8 + Adminer en Docker Compose). No es copia de producción: tiene datos propios.
- El plugin va en el repo (`wp-content/plugins/`, bind mount). Despliegue: commit → push → **Redeploy manual** en Coolify (no hay auto-deploy).
- Persistencia: volúmenes `db_data` (MySQL) y `wp_uploads`. Verificado que los datos del plugin sobreviven a un redespliegue completo.
- Cambio de infraestructura: `docker-compose.yml` fija las claves/sales de WordPress con variables `SERVICE_PASSWORD_64_*` que genera Coolify. Antes se regeneraban en cada despliegue y cerraban todas las sesiones. Verificado: la sesión sobrevive al redespliegue.

## 2. Cambios respecto al ZIP original (v0.1.0)

| Cambio | Motivo |
|---|---|
| Validación de longitud: ciudad ≤150, categoría ≤80, notas ≤5000, URL ≤500 | Con MySQL estricto, un valor largo daba error 500; ahora devuelve 400 con mensaje |
| Rechazo de campos que no sean texto (arrays/objetos/números) | Evitar comportamiento indefinido en sanitización |
| URL de origen: solo `http`/`https`; si no, 400 | Antes `javascript:` se vaciaba en silencio |
| Cabeceras `Cache-Control: no-store, private` y `X-Robots-Tag: noindex` en la API REST | Que ninguna caché/CDN guarde datos de gestión |
| Comprobación de versión de BD en `init` (prioridad 20) | Aplicar cambios de esquema al actualizar sin reactivar |

Incidencia durante las pruebas: la primera versión de la comprobación de BD usaba `plugins_loaded` y provocó un error fatal (aún no existe `$wp_rewrite`). Corregido a `init` antes de cualquier prueba funcional. Aviso: ese fallo tumbó el sitio **de pruebas** unos minutos; no afectó a producción.

## 3. Pruebas ejecutadas (todas en el entorno de Coolify)

Superadas (ejecutadas realmente):
- Sin sesión: GET, POST, PUT y DELETE de la API → 401. `/gestion/` → redirige a login (302).
- Con sesión de administrador: crear (201), listar, editar (200) y borrar; persistencia de la edición; borrado desaparece de la lista; 404 al editar/borrar un id inexistente.
- Validación (400 en todos): título vacío, título >220, fecha inexistente, formato de fecha incorrecto, fin anterior a inicio, entidad/arma/estado fuera de lista, ciudad >150, categoría >80, notas >5000, URL `javascript:`, URL `ftp:`, URL >500, título como array, título como objeto, fecha como número, cuerpo que no es objeto JSON.
- CSRF: con cookie de sesión pero sin cabecera nonce, GET/POST/DELETE → 401.
- Inyección: `<script>…` y `OR 1=1;--` en el título se guardan como texto saneado (sin etiquetas); consultas por `$wpdb`.
- Valores por defecto: sin `end_date` usa el inicio; entidad FAE, arma espada, estado provisional.
- Interfaz: carga del panel; filtros FAE/FADE/RFEE/OTRA/Todas con los recuentos correctos; calendario mensual con eventos de varios días; diálogo de edición y guardado desde el formulario; vista móvil (390 px) sin desbordamiento horizontal.
- Reinicio: los 4 registros de prueba siguieron tras el redespliegue completo de contenedores; sesión conservada.

**No ejecutadas (no las doy por superadas):**
- Acceso de un usuario **no administrador** (debe dar 403). Solo revisado en el código (`current_user_can('manage_almedina_calendar')`). No creé usuarios en el sitio de pruebas.
- **Impresión / PDF**: no se pudo abrir el diálogo de impresión desde la automatización. Solo revisadas las reglas `@media print`. Pruébalo a mano (Ctrl+P en el calendario).
- Pruebas automáticas (PHPUnit): el plugin no tiene; no hay PHP local para validar sintaxis más allá de que WordPress lo ejecuta sin errores.
- Prueba en móvil real y con otros navegadores (solo emulada con iframe de 390 px).
- Carga/volumen de datos.

Datos de prueba: borrados; la tabla quedó vacía.

## 4. Producción — hallazgos

- Alojamiento real: **Hostinger** (cabeceras `platform: hostinger`, `panel: hpanel`, `Server: hcdn`), PHP 8.3, WordPress 7.1.3, DNS en `dns-parking.com`.
- El plugin exige PHP ≥ 7.4: compatible.
- `https://esgrimacordoba.es/gestion/` da 404 ahora: el plugin **no está instalado** en producción.
- Instalación de plugins: desde wp-admin (Plugins → Añadir → Subir plugin), la misma vía que se usó para el tema.
- Copia de seguridad recuperable: **no verificada**. No tengo acceso al hPanel; hazlo tú antes (apartado 5).

## 5. Instalación en producción (requiere tu aprobación)

Antes:
1. hPanel → Sitios web → Copias de seguridad: confirma que hay una copia reciente, o crea una (“Generar copia de seguridad”). Alternativa: All-in-One WP Migration → Exportar (descarga el .wpress) y guárdalo fuera del servidor.
2. Comprueba que puedes descargarla/restaurarla. Una copia que no se puede restaurar no vale.

Instalar:
1. wp-admin de esgrimacordoba.es → Plugins → Añadir plugin → Subir plugin → `almedina-club-manager-v0.1.1.zip` → Instalar → **Activar**.
2. Ajustes → Enlaces permanentes → **Guardar cambios** (refresca las reglas; solo si `/gestion/` da 404).
3. Abre `https://esgrimacordoba.es/gestion/` con tu cuenta administradora.
4. Si usas LiteSpeed Cache (o la caché de Hostinger): excluye de caché `/gestion/` y `/wp-json/almedina/v1/`. El plugin ya envía `no-store`, pero conviene excluirlas de forma explícita. Purga la caché tras activar.
5. Crea una competición de prueba, comprueba que se guarda y bórrala.

## 6. Rollback

- Rápido (sin pérdida de datos): Plugins → **Desactivar**. `/gestion/` vuelve a 404; la tabla `wp_almedina_events` y los eventos **no se borran** al desactivar.
- Eliminar el plugin: desactivar y “Borrar”. La tabla `{prefijo}almedina_events` permanece en la BD (el plugin no tiene `uninstall.php`). Para quitarla del todo, bórrala a mano en phpMyAdmin solo si no necesitas los datos.
- Restauración completa: restaurar la copia de seguridad del paso 5 (hPanel o .wpress).

## 7. Riesgos y tareas pendientes

- Probar el acceso de un usuario no administrador (403) y la impresión antes de usarlo en serio.
- El plugin no tiene historial de cambios ni papelera: un borrado no se puede deshacer (pide confirmación en la interfaz).
- Solo administradores pueden entrar (por diseño). Dar acceso a otros roles requiere cambios.
- Los eventos no se publican en la web pública (fase posterior).
- Sin `uninstall.php`; sin pruebas automáticas.
- Coolify sigue sin auto-deploy: cada cambio necesita Redeploy manual. Las nuevas variables `SERVICE_PASSWORD_64_*` quedan guardadas en Coolify: no las borres o se cerrarán las sesiones.
- Ya existen en Coolify una página “Colaboradores” y mecenas de ejemplo (solo pruebas).
