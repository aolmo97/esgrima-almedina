# Prompt para Google Stitch — Página "Colaboradores"

## Antes de pegarlo en Stitch

- Sube como referencia el `DESIGN.md` de `design-reference/kinetic_blade_sala_de_esgrima/` y las capturas `screen.png` de las carpetas `home_modo_claro…`, `qui_nes_somos_modo_claro…` y `cont_ctanos_modo_claro…`.
- Si usas "Design System" de Stitch, aplícalo a la pantalla.
- Al terminar, pasa el `code.html` o una captura para ajustar `page-colaboradores.php` y el CSS del tema.

## Prompt

```
Diseña la página "Colaboradores" de la web de la Sala de Esgrima Almedina Córdoba (S.E.A.C.). Es una página NUEVA que debe parecer parte del mismo sitio: reutiliza exactamente el sistema de diseño "Kinetic Blade" en modo claro y los componentes de las pantallas de referencia adjuntas (Inicio, Quiénes somos, Contáctanos). No inventes un estilo nuevo: mismo header, mismo footer, mismas tarjetas, botones, chips y formulario que esas pantallas. Idioma: español. Entrega versión escritorio (1440px) y móvil.

SISTEMA VISUAL (idéntico al resto de la web)
- Fondo blanco #ffffff; secciones alternas #f8fafc; bordes #e2e8f0.
- Verde primario #16a34a (hover #047857), tintes #ecfdf5 con borde #a7f3d0.
- Texto: titulares #0f172a, cuerpo #334155, secundario #64748b.
- Titulares en Outfit (700-800), texto en Plus Jakarta Sans.
- Tarjetas blancas con radio 12px, borde #e2e8f0 y sombra suave; al pasar el ratón, sombra más marcada y borde verde claro.
- Botones sólidos verdes con icono a la izquierda y radio 8px.
- Chips/etiquetas pequeñas en mayúsculas con icono y fondo #ecfdf5.
- Iconos Material Symbols Outlined (nunca iconos de karate; el icono del club son dos espadas cruzadas: "swords").
- Contenedor máximo de 1200px, texto mínimo de 16px, contraste alto.

ESPACIADO (importante: compacto)
- Entre bloques de contenido, 32-40px de relleno vertical, no más. Nada de grandes huecos vacíos.
- Cabecera de sección: título + subtítulo y 20px hasta el contenido.
- La página se ve densa y ordenada, como "Quiénes somos".

ESTRUCTURA (de arriba abajo)

1. Header y navegación: exactamente igual que en las demás pantallas.
   - Logo SEAC a la izquierda con "SALA DE ESGRIMA ALMEDINA / CÓRDOBA · SEAC".
   - Menú: INICIO, QUIÉNES SOMOS, COLABORADORES (activo), CONTÁCTANOS.
   - Botón verde "Prueba gratis / Contacto" con icono de rayo, y selector ES / EN.

2. Hero de página compacto (fondo #f8fafc), con el mismo formato que la cabecera de "Quiénes somos":
   - Chip con icono "handshake": "QUIENES NOS APOYAN".
   - Título grande "COLABORADORES".
   - Texto: "Gracias a las empresas, instituciones y personas que hacen posible el día a día de la Sala de Esgrima Almedina."
   - Barra fina verde decorativa debajo, como en "Quiénes somos".

3. Sección "Patrocinadores":
   - Título "Patrocinadores" y subtítulo "Empresas e instituciones que apoyan al club."
   - Cuadrícula de 4 columnas (2 en móvil). CADA patrocinador lleva su propio logo: una tarjeta blanca por patrocinador, con la imagen del logo centrada (máx. 90px de alto, sin deformar, sobre fondo blanco, logos de distintos formatos y colores) y el nombre debajo en peso 600. Ninguna tarjeta de patrocinador va sin logo ni con solo texto.
   - Tres de ejemplo, cada uno con su logo (usa marcadores de logo realistas): "Aceitunas Verdes Catering Córdoba", "Clínicas Beiman Salud y Deporte", "IMDECO — Instituto Municipal de Deportes de Córdoba". La cuarta tarjeta con borde discontinuo, un recuadro de logo vacío con icono "add_photo_alternate" y el texto "Tu marca aquí".

4. Sección "Mecenas" (fondo #f8fafc, con borde superior e inferior):
   - Título "Mecenas" y subtítulo "Personas que impulsan el club con su aportación."
   - Nombres en chips de forma de píldora (borde #e2e8f0, fondo blanco) repartidos en varias filas. Ocho nombres de ejemplo.

5. Bloque CTA "Hazte patrocinador" (mismo estilo que el bloque "Primera clase de prueba" de Inicio):
   - Tarjeta ancha con fondo #ecfdf5, borde #a7f3d0 y radio 16px.
   - Izquierda: chip con icono "volunteer_activism" y "HAZTE PATROCINADOR", título "Tu marca puede estar en cada asalto." y texto "Patrocinar al club es apoyar el deporte base en Córdoba: tu marca llega a deportistas, familias y aficionados, y tú ayudas a financiar material, desplazamientos y competiciones."
   - Derecha: botón verde "Quiero patrocinar al club" con icono de sobre, que baja al formulario.

6. Formulario (mismo estilo que el formulario de Contáctanos):
   - Chip "COLABORA CON NOSOTROS", título "Cuéntanos cómo quieres apoyar al club" y texto "Rellena el formulario y nos pondremos en contacto contigo para explicarte las opciones de patrocinio y colaboración."
   - Tarjeta blanca con sombra y campos de borde #e2e8f0, radio 8px, foco en verde:
     · Fila 1: "Nombre y apellidos *" | "Empresa o entidad".
     · Fila 2: "Correo electrónico *" | "Teléfono de contacto *".
     · Selector "Quiero *": Patrocinar al club / Ser mecenas / Colaborar de otra forma.
     · Área de texto "Mensaje".
     · Casilla "Acepto la política de privacidad y el tratamiento de mis datos…".
     · Botón verde "Enviar solicitud".

7. Footer idéntico al de las demás páginas: logo y descripción, columna "Navegación" (Inicio, Quiénes somos, Modalidades, Horarios, Colaboradores, Contáctanos), columna "Comunidad & Redes" con iconos de Instagram, Facebook, YouTube y WhatsApp, y la barra inferior "© 2026 Sala de Esgrima Almedina de Córdoba (España). Todos los derechos reservados."

NO HACER
- No usar fondos oscuros ni fotos de fondo pesadas.
- No cambiar el logo, la paleta ni las tipografías.
- No añadir secciones que no estén en esta lista.
- No mostrar patrocinadores sin logo: cada patrocinador = un logo + su nombre.
```
