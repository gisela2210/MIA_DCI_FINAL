# Entrega primera version

## Proyecto

Sitio web estatico del Doctorado en Ciencias de la Ingenieria.

Ruta local:

```text
C:\xampp\htdocs\doctorado-dci
```

## Como entregar

Entregar la carpeta completa `doctorado-dci` comprimida en `.zip`.

La persona revisora debe descomprimir la carpeta y abrir:

```text
doctorado-dci\index.html
```

No requiere instalacion.
No requiere build.
No requiere servidor local.
No requiere framework.

## Requisitos para revisar

- Navegador moderno: Chrome, Edge o Firefox.
- Conexion a internet para cargar recursos externos:
  - estilos/componentes de Gobierno de Mexico.
  - mapas embebidos de Google Maps.

## Estructura principal

```text
doctorado-dci/
|-- index.html
|-- README.md
|-- ENTREGA_PRIMERA_VERSION.md
|-- css/
|   `-- styles.css
|-- js/
|   `-- main.js
|-- pages/
|   |-- perfil-ingreso.html
|   |-- perfil-egreso.html
|   |-- mapa-curricular.html
|   |-- lineas-investigacion.html
|   |-- estudiantes.html
|   |-- enlaces.html
|   |-- otros.html
|   |-- retribucion-social.html
|   |-- instalaciones.html
|   `-- contacto.html
`-- assets/
    |-- docs/
    |-- images/
    `-- logos/
```

## Paginas incluidas

- Inicio.
- Perfil de ingreso.
- Perfil de egreso.
- Mapa curricular.
- Lineas de investigacion.
- Estudiantes.
- Enlaces.
- Otros.
- Retribucion social.
- Instalaciones.
- Contacto.

## Funcionalidad incluida

- Navegacion principal responsive.
- Menu hamburguesa en pantallas pequenas.
- Submenu en `Enlaces`.
- Header fijo al hacer scroll.
- Footer institucional azul con direccion y mapa.
- Tarjetas de estudiantes con modal de perfil.
- Documentos PDF embebidos.
- Imagenes y logos locales.

## Archivos tecnicos

- `css/styles.css`: estilos generales, layout, responsive, cards, header, footer, modales y secciones.
- `js/main.js`: menu hamburguesa, submenu y modales.
- `assets/images/`: imagenes del sitio.
- `assets/logos/`: logos institucionales.
- `assets/docs/`: documentos PDF usados en paginas.

## Puntos sugeridos para revision

- Revisar texto institucional.
- Revisar nombres, cargos, correos y telefonos.
- Revisar datos de estudiantes, tesis, directores, codirectores y tutores.
- Revisar que documentos PDF sean version final.
- Revisar que imagenes sean autorizadas.
- Revisar visual en celular, tablet y escritorio.

## Alcance de esta version

Primera version visual y funcional para revision.

Contenido actualizado con base en el Word local `INFORMACION DE PAGINA WEB DEL DOCTORADO EN CIAS DE LA INGENIERIA (1).docx`, sin resumir las secciones institucionales.

Estructura comprobada contra `SECCIONES DCI.docx`.
Repositorio de referencia indicado por el documento: `https://github.com/gisela2210/DOCTORADO-EN-CIENCIAS-DE-LA-INGENIERIA.git`.

El proyecto queda preparado como sitio estatico HTML, CSS y JavaScript vanilla.
No incluye backend, panel administrador, base de datos, framework ni deploy final.
