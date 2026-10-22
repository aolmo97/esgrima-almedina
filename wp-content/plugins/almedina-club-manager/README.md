# Almedina Club Manager — v0.1.1

Plugin de WordPress con panel privado en `/gestion/` para administrar competiciones de esgrima.

## Instalación

1. Haz copia de seguridad de WordPress y de la base de datos; prueba preferiblemente en *staging*.
2. En **Plugins > Añadir plugin > Subir plugin**, selecciona el ZIP.
3. Activa el plugin. Se crea la tabla `{prefijo}almedina_events` y se asigna `manage_almedina_calendar` al rol administrador.
4. Accede a `https://esgrimacordoba.es/gestion/` con una cuenta administradora.
5. Si `/gestion/` devuelve 404, ve a **Ajustes > Enlaces permanentes** y pulsa **Guardar cambios**.

## Funciones

- Acceso mediante sesión WordPress y permisos en cada endpoint REST.
- Alta, edición, eliminación y listado de competiciones; vista de mes y listado.
- Filtros FAE / FADE / RFEE / Otra.
- Arma, categoría, modalidad, sede, fechas, estado, fuente y notas.
- Impresión o PDF con la función de impresión del navegador.
- Sin datos de demostración inventados: empieza vacío.

## Seguridad y límites

- Solo administradores por defecto; no se asignan permisos a otros roles automáticamente.
- No es un segundo sistema de contraseñas: reutiliza el inicio de sesión de WordPress.
- API REST protegida con capacidades y nonce WordPress; consultas SQL mediante API `$wpdb`.
- El panel no está indexado y no debe almacenarse en caché pública. Excluye `/gestion/` y `/wp-json/almedina/v1/` de cualquier CDN/caché.
- Los eventos no se publican en la web pública en esta fase.
- No hay importación automática de calendarios oficiales ni exportación Excel; imprimir a PDF utiliza el navegador.
- La instalación depende de que el servidor admita reglas de enlaces permanentes WordPress.
- El plugin no borra eventos al desactivarse.

## Próximos pasos

Permisos granulares por rol, publicación pública mediante shortcode, importador CSV/ICS, generación PDF con diseño de temporada, historial de cambios, y fuentes oficiales verificadas.
