-- =========================================================
-- BASE DE DATOS 100% EN ESPAÑOL PARA SISTEMA DCI / MIA TECNM
-- Importa este único archivo en phpMyAdmin.
-- Nombre de BD: doctorado_maestria_cms
-- =========================================================
CREATE DATABASE IF NOT EXISTS doctorado_maestria_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE doctorado_maestria_cms;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS recuperaciones_contrasena;
DROP TABLE IF EXISTS intentos_inicio_sesion;
DROP TABLE IF EXISTS notificaciones_correo;
DROP TABLE IF EXISTS eventos_edicion;
DROP TABLE IF EXISTS eventos_vista_pagina;
DROP TABLE IF EXISTS historial_restauraciones;
DROP TABLE IF EXISTS versiones_pagina;
DROP TABLE IF EXISTS notificaciones;
DROP TABLE IF EXISTS bitacora_actividad;
DROP TABLE IF EXISTS estadisticas_pagina;
DROP TABLE IF EXISTS filas_tabla_contenido;
DROP TABLE IF EXISTS tablas_contenido;
DROP TABLE IF EXISTS elementos_galeria;
DROP TABLE IF EXISTS galerias;
DROP TABLE IF EXISTS enlaces;
DROP TABLE IF EXISTS documentos;
DROP TABLE IF EXISTS lineas_investigacion;
DROP TABLE IF EXISTS personas_academicas;
DROP TABLE IF EXISTS biblioteca_medios;
DROP TABLE IF EXISTS subventanas_pagina;
DROP TABLE IF EXISTS elementos_seccion;
DROP TABLE IF EXISTS secciones_pagina;
DROP TABLE IF EXISTS permisos_usuario_pagina;
DROP TABLE IF EXISTS imagenes_pagina;
DROP TABLE IF EXISTS paginas;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS configuracion_sitio;
DROP TABLE IF EXISTS programas;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE programas(
 id INT AUTO_INCREMENT PRIMARY KEY,
 codigo VARCHAR(10) UNIQUE NOT NULL,
 nombre VARCHAR(180) NOT NULL,
 descripcion TEXT NULL,
 activo TINYINT(1) DEFAULT 1,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE paginas(
 id INT AUTO_INCREMENT PRIMARY KEY,
 programa_id INT NOT NULL,
 titulo VARCHAR(220) NOT NULL,
 ruta_archivo VARCHAR(255) NOT NULL UNIQUE,
 orden INT DEFAULT 0,
 visible TINYINT(1) DEFAULT 1,
 creada_desde_dashboard TINYINT(1) DEFAULT 0,
 tipo_menu VARCHAR(30) DEFAULT 'principal',
 slug_menu VARCHAR(120) NULL,
 actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(programa_id) REFERENCES programas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios(
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre_usuario VARCHAR(60) NOT NULL UNIQUE,
 correo VARCHAR(160) NOT NULL UNIQUE,
 nombre_completo VARCHAR(160) NOT NULL,
 contrasena_hash VARCHAR(255) NOT NULL,
 rol ENUM('admin','editor') NOT NULL DEFAULT 'editor',
 codigo_programa ENUM('DCI','MIA','AMBOS') NOT NULL DEFAULT 'AMBOS',
 foto_perfil VARCHAR(255) NULL,
 correo_verificado_en TIMESTAMP NULL,
 token_verificacion_correo VARCHAR(128) NULL,
 token_sesion_actual VARCHAR(128) NULL,
 id_sesion_actual VARCHAR(128) NULL,
 ultimo_inicio_sesion_en TIMESTAMP NULL,
 ultima_actividad_sesion_en TIMESTAMP NULL,
 activo TINYINT(1) DEFAULT 1,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permisos_usuario_pagina(
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL,
 pagina_id INT NOT NULL,
 puede_ver TINYINT(1) DEFAULT 1,
 puede_editar TINYINT(1) DEFAULT 1,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE(usuario_id,pagina_id),
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE imagenes_pagina(
 id INT AUTO_INCREMENT PRIMARY KEY,
 programa_id INT NOT NULL,
 pagina_id INT NULL,
 etiqueta VARCHAR(180) NOT NULL,
 ruta_imagen VARCHAR(255) NOT NULL,
 nombre_original VARCHAR(255) NULL,
 seccion_referencia VARCHAR(180) NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(programa_id) REFERENCES programas(id) ON DELETE CASCADE,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE secciones_pagina(
 id INT AUTO_INCREMENT PRIMARY KEY,
 pagina_id INT NOT NULL,
 tipo_seccion VARCHAR(80) DEFAULT 'contenido',
 titulo VARCHAR(220) NULL,
 subtitulo VARCHAR(220) NULL,
 contenido_html LONGTEXT NULL,
 ruta_imagen VARCHAR(255) NULL,
 orden INT DEFAULT 0,
 visible TINYINT(1) DEFAULT 1,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE elementos_seccion(
 id INT AUTO_INCREMENT PRIMARY KEY,
 seccion_id INT NOT NULL,
 tipo_elemento VARCHAR(80) NOT NULL,
 titulo VARCHAR(220) NULL,
 texto TEXT NULL,
 ruta_imagen VARCHAR(255) NULL,
 url VARCHAR(255) NULL,
 orden INT DEFAULT 0,
 visible TINYINT(1) DEFAULT 1,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(seccion_id) REFERENCES secciones_pagina(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subventanas_pagina(
 id INT AUTO_INCREMENT PRIMARY KEY,
 pagina_id INT NOT NULL,
 seccion_id INT NULL,
 titulo VARCHAR(220) NOT NULL,
 contenido_html LONGTEXT NULL,
 ruta_imagen VARCHAR(255) NULL,
 boton_texto VARCHAR(80) DEFAULT 'Ver más',
 orden INT DEFAULT 0,
 visible TINYINT(1) DEFAULT 1,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE CASCADE,
 FOREIGN KEY(seccion_id) REFERENCES secciones_pagina(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE biblioteca_medios(
 id INT AUTO_INCREMENT PRIMARY KEY,
 programa_id INT NULL,
 pagina_id INT NULL,
 tipo_medio ENUM('imagen','documento','video','otro') DEFAULT 'imagen',
 titulo VARCHAR(180) NOT NULL,
 ruta_archivo VARCHAR(255) NOT NULL,
 nombre_original VARCHAR(255) NULL,
 descripcion TEXT NULL,
 subido_por INT NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(programa_id) REFERENCES programas(id) ON DELETE SET NULL,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE SET NULL,
 FOREIGN KEY(subido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE galerias(
 id INT AUTO_INCREMENT PRIMARY KEY,
 pagina_id INT NOT NULL,
 titulo VARCHAR(180) NOT NULL,
 descripcion TEXT NULL,
 orden INT DEFAULT 0,
 visible TINYINT(1) DEFAULT 1,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE elementos_galeria(
 id INT AUTO_INCREMENT PRIMARY KEY,
 galeria_id INT NOT NULL,
 ruta_imagen VARCHAR(255) NOT NULL,
 titulo VARCHAR(180) NULL,
 descripcion TEXT NULL,
 orden INT DEFAULT 0,
 FOREIGN KEY(galeria_id) REFERENCES galerias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tablas_contenido(
 id INT AUTO_INCREMENT PRIMARY KEY,
 pagina_id INT NOT NULL,
 seccion_id INT NULL,
 titulo VARCHAR(180) NOT NULL,
 encabezados_json JSON NULL,
 orden INT DEFAULT 0,
 visible TINYINT(1) DEFAULT 1,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE CASCADE,
 FOREIGN KEY(seccion_id) REFERENCES secciones_pagina(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE filas_tabla_contenido(
 id INT AUTO_INCREMENT PRIMARY KEY,
 tabla_id INT NOT NULL,
 datos_json JSON NOT NULL,
 orden INT DEFAULT 0,
 FOREIGN KEY(tabla_id) REFERENCES tablas_contenido(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE personas_academicas(
 id INT AUTO_INCREMENT PRIMARY KEY,
 programa_id INT NOT NULL,
 nombre VARCHAR(180) NOT NULL,
 tipo_persona ENUM('docente','estudiante','egresado','coordinador') NOT NULL,
 grado VARCHAR(120) NULL,
 correo VARCHAR(160) NULL,
 linea_investigacion VARCHAR(220) NULL,
 semblanza TEXT NULL,
 foto VARCHAR(255) NULL,
 perfil_url VARCHAR(255) NULL,
 visible TINYINT(1) DEFAULT 1,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(programa_id) REFERENCES programas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lineas_investigacion(
 id INT AUTO_INCREMENT PRIMARY KEY,
 programa_id INT NOT NULL,
 nombre VARCHAR(220) NOT NULL,
 descripcion TEXT NULL,
 imagen VARCHAR(255) NULL,
 visible TINYINT(1) DEFAULT 1,
 FOREIGN KEY(programa_id) REFERENCES programas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE documentos(
 id INT AUTO_INCREMENT PRIMARY KEY,
 programa_id INT NOT NULL,
 pagina_id INT NULL,
 titulo VARCHAR(220) NOT NULL,
 descripcion TEXT NULL,
 ruta_documento VARCHAR(255) NOT NULL,
 tipo_documento VARCHAR(80) NULL,
 visible TINYINT(1) DEFAULT 1,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(programa_id) REFERENCES programas(id) ON DELETE CASCADE,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enlaces(
 id INT AUTO_INCREMENT PRIMARY KEY,
 programa_id INT NULL,
 pagina_id INT NULL,
 titulo VARCHAR(220) NOT NULL,
 url VARCHAR(255) NOT NULL,
 descripcion TEXT NULL,
 categoria VARCHAR(120) NULL,
 visible TINYINT(1) DEFAULT 1,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(programa_id) REFERENCES programas(id) ON DELETE SET NULL,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE versiones_pagina(
 id INT AUTO_INCREMENT PRIMARY KEY,
 pagina_id INT NOT NULL,
 usuario_id INT NULL,
 html LONGTEXT NOT NULL,
 nota VARCHAR(255) NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE CASCADE,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE historial_restauraciones(
 id INT AUTO_INCREMENT PRIMARY KEY,
 version_id INT NULL,
 pagina_id INT NOT NULL,
 usuario_id INT NULL,
 nota VARCHAR(255) NULL,
 restaurado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(version_id) REFERENCES versiones_pagina(id) ON DELETE SET NULL,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE CASCADE,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE estadisticas_pagina(
 id INT AUTO_INCREMENT PRIMARY KEY,
 pagina_id INT NOT NULL UNIQUE,
 vistas INT DEFAULT 0,
 ediciones INT DEFAULT 0,
 ultima_vista_en TIMESTAMP NULL,
 ultima_edicion_en TIMESTAMP NULL,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE eventos_vista_pagina(
 id INT AUTO_INCREMENT PRIMARY KEY,
 pagina_id INT NOT NULL,
 usuario_id INT NULL,
 ip VARCHAR(80) NULL,
 agente_usuario VARCHAR(255) NULL,
 visto_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE CASCADE,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE eventos_edicion(
 id INT AUTO_INCREMENT PRIMARY KEY,
 pagina_id INT NOT NULL,
 usuario_id INT NULL,
 accion VARCHAR(120) NOT NULL,
 detalle TEXT NULL,
 editado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE CASCADE,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bitacora_actividad(
 id INT AUTO_INCREMENT PRIMARY KEY,
 tipo VARCHAR(50) NOT NULL,
 descripcion VARCHAR(255) NOT NULL,
 pagina_id INT NULL,
 usuario_id INT NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE SET NULL,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notificaciones(
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL,
 titulo VARCHAR(160) NOT NULL,
 mensaje VARCHAR(255) NOT NULL,
 pagina_id INT NULL,
 leida TINYINT(1) DEFAULT 0,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
 FOREIGN KEY(pagina_id) REFERENCES paginas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notificaciones_correo(
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NULL,
 correo_destino VARCHAR(160) NOT NULL,
 asunto VARCHAR(220) NOT NULL,
 mensaje TEXT NOT NULL,
 enviado TINYINT(1) DEFAULT 0,
 error_envio TEXT NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE intentos_inicio_sesion(
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre_usuario VARCHAR(60) NULL,
 ip VARCHAR(80) NULL,
 exitoso TINYINT(1) DEFAULT 0,
 detalle VARCHAR(255) NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recuperaciones_contrasena(
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL,
 token VARCHAR(128) NOT NULL,
 usado TINYINT(1) DEFAULT 0,
 expira_en TIMESTAMP NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracion_sitio(
 clave_configuracion VARCHAR(80) PRIMARY KEY,
 valor_configuracion TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO programas(codigo,nombre,descripcion) VALUES
('DCI','Doctorado en Ciencias de la Ingeniería','Programa de posgrado DCI'),
('MIA','Maestría en Inteligencia Artificial','Programa de posgrado MIA');

INSERT INTO usuarios(nombre_usuario,correo,nombre_completo,contrasena_hash,rol,codigo_programa,correo_verificado_en,activo) VALUES
('admin','admin@itver.edu.mx','Administrador General','$2y$12$TtBWRgRVRwIsdP9vnRrv0er7kY.qm0cWiqQggTZzlx7qPB7QamKmG','admin','AMBOS',NOW(),1),
('editor_dci','editor.dci@itver.edu.mx','Editor DCI','$2y$12$yBAFZcyGRyIrGk6ryyRBYunRUDaUTginVx03te5ldjxJF9uE24roW','editor','DCI',NOW(),1),
('editor_mia','editor.mia@itver.edu.mx','Editor MIA','$2y$12$wdwNGHMqkpZ4kYayM78DGetOuQ9GR8LbsANSJVXu9GqsBiChT4uMS','editor','MIA',NOW(),1);

INSERT INTO paginas(programa_id,titulo,ruta_archivo,orden,visible) VALUES
((SELECT id FROM programas WHERE codigo='DCI'),'Doctorado en Ciencias de la Ingeniería | TecNM Veracruz','FRONTEND-DCI/index.html',1,1),
((SELECT id FROM programas WHERE codigo='DCI'),'Contacto | DCI TecNM Veracruz','FRONTEND-DCI/pages/contacto.html',2,1),
((SELECT id FROM programas WHERE codigo='DCI'),'Enlaces | DCI TecNM Veracruz','FRONTEND-DCI/pages/enlaces.html',3,1),
((SELECT id FROM programas WHERE codigo='DCI'),'Estudiantes | DCI TecNM Veracruz','FRONTEND-DCI/pages/estudiantes.html',4,1),
((SELECT id FROM programas WHERE codigo='DCI'),'Instalaciones | DCI TecNM Veracruz','FRONTEND-DCI/pages/instalaciones.html',5,1),
((SELECT id FROM programas WHERE codigo='DCI'),'Líneas de investigación | DCI TecNM Veracruz','FRONTEND-DCI/pages/lineas-investigacion.html',6,1),
((SELECT id FROM programas WHERE codigo='DCI'),'Mapa curricular | DCI TecNM Veracruz','FRONTEND-DCI/pages/mapa-curricular.html',7,1),
((SELECT id FROM programas WHERE codigo='DCI'),'Otros | DCI TecNM Veracruz','FRONTEND-DCI/pages/otros.html',8,1),
((SELECT id FROM programas WHERE codigo='DCI'),'Perfil de egreso | DCI TecNM Veracruz','FRONTEND-DCI/pages/perfil-egreso.html',9,1),
((SELECT id FROM programas WHERE codigo='DCI'),'Perfil de ingreso | DCI TecNM Veracruz','FRONTEND-DCI/pages/perfil-ingreso.html',10,1),
((SELECT id FROM programas WHERE codigo='DCI'),'Protocolos de ética | DCI TecNM Veracruz','FRONTEND-DCI/pages/protocolos-etica.html',11,1),
((SELECT id FROM programas WHERE codigo='DCI'),'Retribución social | DCI TecNM Veracruz','FRONTEND-DCI/pages/retribucion-social.html',12,1),
((SELECT id FROM programas WHERE codigo='MIA'),'Maestría en Inteligencia Artificial | TecNM Veracruz','FRONTEND-MIA/index.html',13,1),
((SELECT id FROM programas WHERE codigo='MIA'),'Contacto - Maestría en Inteligencia Artificial | TecNM Veracruz','FRONTEND-MIA/pages/contacto.html',14,1),
((SELECT id FROM programas WHERE codigo='MIA'),'Enlaces - Maestría en Inteligencia Artificial | TecNM Veracruz','FRONTEND-MIA/pages/enlaces.html',15,1),
((SELECT id FROM programas WHERE codigo='MIA'),'Núcleo académico - Maestría en Inteligencia Artificial | TecNM Veracruz','FRONTEND-MIA/pages/estudiantes.html',16,1),
((SELECT id FROM programas WHERE codigo='MIA'),'Instalaciones - Maestría en Inteligencia Artificial | TecNM Veracruz','FRONTEND-MIA/pages/instalaciones.html',17,1),
((SELECT id FROM programas WHERE codigo='MIA'),'Líneas de investigación - Maestría en Inteligencia Artificial | TecNM Veracruz','FRONTEND-MIA/pages/lineas-investigacion.html',18,1),
((SELECT id FROM programas WHERE codigo='MIA'),'Mapa curricular - Maestría en Inteligencia Artificial | TecNM Veracruz','FRONTEND-MIA/pages/mapa-curricular.html',19,1),
((SELECT id FROM programas WHERE codigo='MIA'),'Egresados - Maestría en Inteligencia Artificial | TecNM Veracruz','FRONTEND-MIA/pages/otros.html',20,1),
((SELECT id FROM programas WHERE codigo='MIA'),'Perfil de egreso - Maestría en Inteligencia Artificial | TecNM Veracruz','FRONTEND-MIA/pages/perfil-egreso.html',21,1),
((SELECT id FROM programas WHERE codigo='MIA'),'Perfil de ingreso - Maestría en Inteligencia Artificial | TecNM Veracruz','FRONTEND-MIA/pages/perfil-ingreso.html',22,1),
((SELECT id FROM programas WHERE codigo='MIA'),'Protocolos de ética - Maestría en Inteligencia Artificial | TecNM Veracruz','FRONTEND-MIA/pages/protocolos-etica.html',23,1),
((SELECT id FROM programas WHERE codigo='MIA'),'Retribución social - Maestría en Inteligencia Artificial | TecNM Veracruz','FRONTEND-MIA/pages/retribucion-social.html',24,1);

INSERT INTO estadisticas_pagina(pagina_id,vistas,ediciones,ultima_vista_en,ultima_edicion_en) SELECT id,0,0,NULL,NULL FROM paginas;

INSERT INTO permisos_usuario_pagina(usuario_id,pagina_id,puede_ver,puede_editar) SELECT u.id,p.id,1,1 FROM usuarios u JOIN paginas p WHERE u.rol='admin' OR u.codigo_programa='AMBOS' OR u.codigo_programa=(SELECT codigo FROM programas WHERE id=p.programa_id);

INSERT INTO imagenes_pagina(programa_id,pagina_id,etiqueta,ruta_imagen,nombre_original,seccion_referencia) VALUES
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'amanda-caracas.jpg','FRONTEND-DCI/assets/images/amanda-caracas.jpg','amanda-caracas.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'bioprocesos.jpg','FRONTEND-DCI/assets/images/bioprocesos.jpg','bioprocesos.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'dra-ines.jpeg','FRONTEND-DCI/assets/images/dra-ines.jpeg','dra-ines.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'dra-olaya.jpeg','FRONTEND-DCI/assets/images/dra-olaya.jpeg','dra-olaya.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'genaro-mendez.jpg','FRONTEND-DCI/assets/images/genaro-mendez.jpg','genaro-mendez.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'instalaciones-1.jpg','FRONTEND-DCI/assets/images/instalaciones-1.jpg','instalaciones-1.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'instalaciones-2.jpg','FRONTEND-DCI/assets/images/instalaciones-2.jpg','instalaciones-2.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'inteligencia-computacional.jpg','FRONTEND-DCI/assets/images/inteligencia-computacional.jpg','inteligencia-computacional.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'maria-elena.jpg','FRONTEND-DCI/assets/images/maria-elena.jpg','maria-elena.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'nanociencias.jpg','FRONTEND-DCI/assets/images/nanociencias.jpg','nanociencias.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'perfil-ingreso.png','FRONTEND-DCI/assets/images/perfil-ingreso.png','perfil-ingreso.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'plan-estudios.jpeg','FRONTEND-DCI/assets/images/plan-estudios.jpeg','plan-estudios.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'portada-contacto.jpg','FRONTEND-DCI/assets/images/portada-contacto.jpg','portada-contacto.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'portada-enlaces.jpg','FRONTEND-DCI/assets/images/portada-enlaces.jpg','portada-enlaces.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'portada-estudiantes.jpg','FRONTEND-DCI/assets/images/portada-estudiantes.jpg','portada-estudiantes.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'portada-inicio.jpg','FRONTEND-DCI/assets/images/portada-inicio.jpg','portada-inicio.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'portada-lineas-investigacion.jpg','FRONTEND-DCI/assets/images/portada-lineas-investigacion.jpg','portada-lineas-investigacion.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'portada-mapa-curricular.jpg','FRONTEND-DCI/assets/images/portada-mapa-curricular.jpg','portada-mapa-curricular.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'portada-perfil-egreso.jpg','FRONTEND-DCI/assets/images/portada-perfil-egreso.jpg','portada-perfil-egreso.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'portada-perfil-ingreso.jpg','FRONTEND-DCI/assets/images/portada-perfil-ingreso.jpg','portada-perfil-ingreso.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'termodinamica.jpg','FRONTEND-DCI/assets/images/termodinamica.jpg','termodinamica.jpg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'vinculacion-industrial.png','FRONTEND-DCI/assets/images/vinculacion-industrial.png','vinculacion-industrial.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-01.png','FRONTEND-DCI/assets/images/word/word-imagen-01.png','word-imagen-01.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-02.jpeg','FRONTEND-DCI/assets/images/word/word-imagen-02.jpeg','word-imagen-02.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-03.jpeg','FRONTEND-DCI/assets/images/word/word-imagen-03.jpeg','word-imagen-03.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-04.jpeg','FRONTEND-DCI/assets/images/word/word-imagen-04.jpeg','word-imagen-04.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-05.jpeg','FRONTEND-DCI/assets/images/word/word-imagen-05.jpeg','word-imagen-05.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-06.jpeg','FRONTEND-DCI/assets/images/word/word-imagen-06.jpeg','word-imagen-06.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-07.jpeg','FRONTEND-DCI/assets/images/word/word-imagen-07.jpeg','word-imagen-07.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-08.jpeg','FRONTEND-DCI/assets/images/word/word-imagen-08.jpeg','word-imagen-08.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-09.jpeg','FRONTEND-DCI/assets/images/word/word-imagen-09.jpeg','word-imagen-09.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-10.jpeg','FRONTEND-DCI/assets/images/word/word-imagen-10.jpeg','word-imagen-10.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-11.jpeg','FRONTEND-DCI/assets/images/word/word-imagen-11.jpeg','word-imagen-11.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-12.png','FRONTEND-DCI/assets/images/word/word-imagen-12.png','word-imagen-12.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-13.png','FRONTEND-DCI/assets/images/word/word-imagen-13.png','word-imagen-13.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-14.png','FRONTEND-DCI/assets/images/word/word-imagen-14.png','word-imagen-14.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'word-imagen-15.png','FRONTEND-DCI/assets/images/word/word-imagen-15.png','word-imagen-15.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'calendario.png','FRONTEND-DCI/assets/icons/calendario.png','calendario.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'china.png','FRONTEND-DCI/assets/icons/china.png','china.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'corea.png','FRONTEND-DCI/assets/icons/corea.png','corea.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'correo.png','FRONTEND-DCI/assets/icons/correo.png','correo.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'francia.png','FRONTEND-DCI/assets/icons/francia.png','francia.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'mexico.png','FRONTEND-DCI/assets/icons/mexico.png','mexico.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'ojo.png','FRONTEND-DCI/assets/icons/ojo.png','ojo.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'teams.png','FRONTEND-DCI/assets/icons/teams.png','teams.png',NULL),
((SELECT id FROM programas WHERE codigo='DCI'),NULL,'usa.png','FRONTEND-DCI/assets/icons/usa.png','usa.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'arturo-perez-rendon.jpeg','FRONTEND-MIA/assets/images/maestros/arturo-perez-rendon.jpeg','arturo-perez-rendon.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'delio-coss-camilo.jpeg','FRONTEND-MIA/assets/images/maestros/delio-coss-camilo.jpeg','delio-coss-camilo.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'francisco-javier-gomez-gonzalez.jpeg','FRONTEND-MIA/assets/images/maestros/francisco-javier-gomez-gonzalez.jpeg','francisco-javier-gomez-gonzalez.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'genaro-mendez-lopez.jpeg','FRONTEND-MIA/assets/images/maestros/genaro-mendez-lopez.jpeg','genaro-mendez-lopez.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'hector-perez-ortiz.jpeg','FRONTEND-MIA/assets/images/maestros/hector-perez-ortiz.jpeg','hector-perez-ortiz.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'lizbeth-sanchez-ferrer.jpeg','FRONTEND-MIA/assets/images/maestros/lizbeth-sanchez-ferrer.jpeg','lizbeth-sanchez-ferrer.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'martin-hernandez-ordonez.jpeg','FRONTEND-MIA/assets/images/maestros/martin-hernandez-ordonez.jpeg','martin-hernandez-ordonez.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'primavera-arguelles-lucho.jpeg','FRONTEND-MIA/assets/images/maestros/primavera-arguelles-lucho.jpeg','primavera-arguelles-lucho.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'rafael-rivera-lopez.jpeg','FRONTEND-MIA/assets/images/maestros/rafael-rivera-lopez.jpeg','rafael-rivera-lopez.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'xochitl-citlalli-hernandez-silva.jpeg','FRONTEND-MIA/assets/images/maestros/xochitl-citlalli-hernandez-silva.jpeg','xochitl-citlalli-hernandez-silva.jpeg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'mapa-curricular-reticula.png','FRONTEND-MIA/assets/images/mapa-curricular-reticula.png','mapa-curricular-reticula.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'portada-contacto.jpg','FRONTEND-MIA/assets/images/portada-contacto.jpg','portada-contacto.jpg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'portada-egresados.png','FRONTEND-MIA/assets/images/portada-egresados.png','portada-egresados.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'portada-enlaces.jpg','FRONTEND-MIA/assets/images/portada-enlaces.jpg','portada-enlaces.jpg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'portada-inicio.png','FRONTEND-MIA/assets/images/portada-inicio.png','portada-inicio.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'portada-lineas-investigacion.png','FRONTEND-MIA/assets/images/portada-lineas-investigacion.png','portada-lineas-investigacion.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'portada-mapa-curricular.png','FRONTEND-MIA/assets/images/portada-mapa-curricular.png','portada-mapa-curricular.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'portada-mapa-egresados.jpg','FRONTEND-MIA/assets/images/portada-mapa-egresados.jpg','portada-mapa-egresados.jpg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'portada-nucleo-academico.png','FRONTEND-MIA/assets/images/portada-nucleo-academico.png','portada-nucleo-academico.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'portada-perfil-egreso.jpg','FRONTEND-MIA/assets/images/portada-perfil-egreso.jpg','portada-perfil-egreso.jpg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'portada-perfil-ingreso.jpg','FRONTEND-MIA/assets/images/portada-perfil-ingreso.jpg','portada-perfil-ingreso.jpg',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'calendario.png','FRONTEND-MIA/assets/icons/calendario.png','calendario.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'china.png','FRONTEND-MIA/assets/icons/china.png','china.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'corea.png','FRONTEND-MIA/assets/icons/corea.png','corea.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'correo.png','FRONTEND-MIA/assets/icons/correo.png','correo.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'francia.png','FRONTEND-MIA/assets/icons/francia.png','francia.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'mexico.png','FRONTEND-MIA/assets/icons/mexico.png','mexico.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'ojo.png','FRONTEND-MIA/assets/icons/ojo.png','ojo.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'teams.png','FRONTEND-MIA/assets/icons/teams.png','teams.png',NULL),
((SELECT id FROM programas WHERE codigo='MIA'),NULL,'usa.png','FRONTEND-MIA/assets/icons/usa.png','usa.png',NULL);

INSERT INTO configuracion_sitio(clave_configuracion,valor_configuracion) VALUES
('fuente_global','Noto Sans'),
('fuente_publica','Noto Sans'),
('fuente_panel','Noto Sans'),
('fuente_global_panel','Noto Sans'),
('fuente_global_publica','Noto Sans'),
('notificar_cambios','1'),
('url_base','http://localhost/MIA-DCI/sistema/admin'),
('correo_remitente','no-reply@localhost'),
('nombre_remitente','Sistema TecNM Posgrados'),
('modo_envio_correo','mail'),
('smtp_host',''),
('smtp_puerto','587'),
('smtp_seguridad','tls'),
('smtp_usuario',''),
('smtp_contrasena',''),
('nombre_sitio','Panel DCI/MIA TecNM Veracruz');

INSERT INTO bitacora_actividad(tipo,descripcion,pagina_id,usuario_id) VALUES
('sistema','Base de datos en español instalada correctamente',NULL,(SELECT id FROM usuarios WHERE nombre_usuario='admin'));
INSERT INTO notificaciones(usuario_id,titulo,mensaje,pagina_id,leida)
SELECT id,'Sistema listo','La base de datos en español fue instalada correctamente.',NULL,0 FROM usuarios;
