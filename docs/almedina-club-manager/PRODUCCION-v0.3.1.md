# Despliegue en esgrimacordoba.es — Almedina Club Manager v0.3.1

Estado: **preparado, NO ejecutado**. Producción no se ha modificado. Paquete: `almedina-club-manager-v0.3.1.zip` (esta carpeta; sin carpeta `tests/`).

## 1. Infraestructura de producción (comprobada en solo lectura)

| Dato | Valor |
|---|---|
| Alojamiento | Hostinger (hPanel), CDN `hcdn`, PHP 8.3.35 |
| WordPress | 7.1.3 (el plugin exige PHP ≥ 7.4) |
| Plugins activos | ACF Pro, All-in-One WP Migration, Complianz, Contact Form 7, Polylang |
| LiteSpeed Cache | instalado pero **inactivo** (no hay caché de página que excluir) |
| `robots.txt` | virtual (lo genera WordPress): la regla `Disallow: /gestion/` del plugin se aplicará |
| `/gestion/`, `/resultados/…` | 404 ahora (plugin no instalado) |
| Marca (logo, nombre, color) | vienen de «Ajustes del sitio» (ACF Pro activo) |
| **Copias de seguridad** | **No hay ninguna** (AIOWPM: «No se han encontrado copias de seguridad») |

## 2. Bloqueo antes de instalar: copia de seguridad

No instalar sin una copia recuperable. Hazla tú o autoriza a que la haga yo:

1. wp-admin → **All-in-One WP Migration → Copias de seguridad → Crear backup** → «Archivo» (genera un `.wpress` en el servidor).
2. **Descárgala** a tu ordenador (icono de descarga junto a la copia). Una copia que solo está en el servidor no vale.
3. Opcional y recomendable: hPanel → Sitios web → Copias de seguridad, comprobar que hay una copia reciente de Hostinger.
4. Guarda el `.wpress` fuera del servidor. Comprueba el tamaño (> 0) y que la descarga termina.

## 3. Instalación (≈5 minutos)

1. wp-admin → Plugins → Añadir plugin → **Subir plugin** → `almedina-club-manager-v0.3.1.zip` → Instalar → **Activar**.
   - La activación crea 4 tablas nuevas `{prefijo}almedina_*` (aditivo) y añade el permiso `manage_almedina_calendar` a administradores.
2. Si `/gestion/` da 404: Ajustes → Enlaces permanentes → **Guardar cambios** (refresca reglas; `/resultados/{token}/` también).
3. Abre `https://esgrimacordoba.es/gestion/` con tu cuenta de administrador.

## 4. Verificación tras instalar (checklist)

- [ ] `/gestion/` sin sesión redirige al login; con sesión carga el panel con logo, «S.E.A.C.» y verde del club.
- [ ] `/robots.txt` incluye `Disallow: /gestion/` y `Disallow: /wp-json/almedina/`.
- [ ] Cabeceras de `/gestion/`: `X-Robots-Tag: noindex, nofollow` y sin caché.
- [ ] Calendario: crear una competición de prueba, filtrarla, imprimirla y borrarla.
- [ ] Tiradores y torneo de prueba: 4 tiradores, poules, resultados, cuadro; imprimir hojas «a mano»; borrar todo.
- [ ] Marcador en el móvil real (iPhone/Android): tocados, cronómetro y guardado.
- [ ] Enlace público de resultados abre sin iniciar sesión y no muestra datos privados.
- [ ] La web pública (inicio, formularios) sigue igual; revisar el Registro de errores de PHP en hPanel (sin errores nuevos).
- [ ] Ajustes → Configuración → «Descargar copia (JSON)» funciona.

Opcionalmente puedo ejecutar la batería `tests/api-integration.js` en producción: crea y borra datos con prefijo `ZZTEST` (dejaría 0 restos). Hay que decidirlo expresamente porque escribe en la base de datos real.

## 5. Rollback

- **Rápido (sin pérdida)**: Plugins → **Desactivar** `Almedina Club Manager`. `/gestion/` vuelve a 404; los datos del plugin se conservan.
- **Retirar el plugin**: desactivar → Borrar. Las 5 tablas `almedina_*` **permanecen** (el plugin no tiene `uninstall.php`). Para eliminarlas del todo, bórralas en phpMyAdmin solo si ya no necesitas los datos.
- **Restauración completa**: All-in-One WP Migration → Importar el `.wpress` del paso 2 (sustituye base de datos y ficheros; hacerlo solo si algo grave falla).
- No hay cambios en el tema ni en otros plugins: el rollback del plugin no afecta a la web.

## 6. Diferencias con el entorno probado (riesgos)

- Probado en Coolify (WordPress 6 / PHP 8.2 en Docker), no en Hostinger (PHP 8.3, WordPress 7.1.3). No esperamos incompatibilidades, pero es la primera ejecución real allí.
- No probado: usuarios no administradores (403), diálogo de impresión real, dispositivos físicos.
- El CDN `hcdn` marca `/gestion/` como DYNAMIC; el plugin envía `no-store`, así que no se cachea. Se verifica en el checklist.
- Polylang: `/gestion/` y `/resultados/` son rutas propias; si Polylang añadiera prefijo de idioma habría que revisarlo (en Coolify, con Polylang activo, funcionan).
- Sin pruebas automáticas de PHP; la sintaxis solo se ha validado con un analizador y ejecutándolo en Coolify.
