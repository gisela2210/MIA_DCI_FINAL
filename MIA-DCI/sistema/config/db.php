<?php
$DB_HOST='localhost'; $DB_NAME='doctorado_maestria_cms'; $DB_USER='root'; $DB_PASS='';
try {
  $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
}
catch(Exception $e){ die('Error de conexión: '.$e->getMessage().'<br>Importa database.sql en phpMyAdmin.'); }

/*
  Reparación automática de tablas del panel.
  Esto evita errores como: Table doctorado_maestria_cms.estadisticas_pagina doesn't exist
  cuando se actualiza el código pero la base anterior no tenía las tablas nuevas.
*/
function ensure_cms_schema(PDO $pdo){
  try{
    $pdo->exec("CREATE TABLE IF NOT EXISTS estadisticas_pagina(
      id INT AUTO_INCREMENT PRIMARY KEY,
      pagina_id INT NOT NULL UNIQUE,
      vistas INT DEFAULT 0,
      ediciones INT DEFAULT 0,
      ultima_vista_en TIMESTAMP NULL,
      ultima_edicion_en TIMESTAMP NULL,
      INDEX(pagina_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS bitacora_actividad(
      id INT AUTO_INCREMENT PRIMARY KEY,
      tipo VARCHAR(50) NOT NULL,
      descripcion VARCHAR(255) NOT NULL,
      pagina_id INT NULL,
      usuario_id INT NULL,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX(pagina_id),
      INDEX(usuario_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notificaciones(
      id INT AUTO_INCREMENT PRIMARY KEY,
      usuario_id INT NOT NULL,
      titulo VARCHAR(160) NOT NULL,
      mensaje VARCHAR(255) NOT NULL,
      pagina_id INT NULL,
      leida TINYINT(1) DEFAULT 0,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX(usuario_id),
      INDEX(pagina_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notificaciones_correo(
      id INT AUTO_INCREMENT PRIMARY KEY,
      usuario_id INT NULL,
      correo_destino VARCHAR(160) NOT NULL,
      asunto VARCHAR(220) NOT NULL,
      mensaje TEXT NOT NULL,
      enviado TINYINT(1) DEFAULT 0,
      error_envio TEXT NULL,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX(usuario_id),
      INDEX(correo_destino)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion_sitio(
      clave_configuracion VARCHAR(80) PRIMARY KEY,
      valor_configuracion TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");



    // Reparación para el módulo "Secciones del menú" con bases antiguas.
    $pdo->exec("CREATE TABLE IF NOT EXISTS secciones_pagina(
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
      INDEX(pagina_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $cols = $pdo->query("SHOW COLUMNS FROM secciones_pagina")->fetchAll(PDO::FETCH_COLUMN);
    if(!in_array('contenido_html',$cols,true)) $pdo->exec("ALTER TABLE secciones_pagina ADD COLUMN contenido_html LONGTEXT NULL");
    if(!in_array('ruta_imagen',$cols,true)) $pdo->exec("ALTER TABLE secciones_pagina ADD COLUMN ruta_imagen VARCHAR(255) NULL");
    if(!in_array('tipo_seccion',$cols,true)) $pdo->exec("ALTER TABLE secciones_pagina ADD COLUMN tipo_seccion VARCHAR(80) DEFAULT 'contenido'");

    // Seguridad: evita sesiones duplicadas por usuario.
    $colsUsu = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
    if(!in_array('token_sesion_actual',$colsUsu,true)) $pdo->exec("ALTER TABLE usuarios ADD COLUMN token_sesion_actual VARCHAR(128) NULL");
    if(!in_array('id_sesion_actual',$colsUsu,true)) $pdo->exec("ALTER TABLE usuarios ADD COLUMN id_sesion_actual VARCHAR(128) NULL");
    if(!in_array('ultimo_inicio_sesion_en',$colsUsu,true)) $pdo->exec("ALTER TABLE usuarios ADD COLUMN ultimo_inicio_sesion_en TIMESTAMP NULL");
    if(!in_array('ultima_actividad_sesion_en',$colsUsu,true)) $pdo->exec("ALTER TABLE usuarios ADD COLUMN ultima_actividad_sesion_en TIMESTAMP NULL");

    $colsPag = $pdo->query("SHOW COLUMNS FROM paginas")->fetchAll(PDO::FETCH_COLUMN);
    if(!in_array('creada_desde_dashboard',$colsPag,true)) $pdo->exec("ALTER TABLE paginas ADD COLUMN creada_desde_dashboard TINYINT(1) DEFAULT 0");
    if(!in_array('tipo_menu',$colsPag,true)) $pdo->exec("ALTER TABLE paginas ADD COLUMN tipo_menu VARCHAR(30) DEFAULT 'principal'");
    if(!in_array('slug_menu',$colsPag,true)) $pdo->exec("ALTER TABLE paginas ADD COLUMN slug_menu VARCHAR(120) NULL");

    if($pdo->query("SHOW TABLES LIKE 'versiones_pagina'")->fetchColumn()){
      $colsVer = $pdo->query("SHOW COLUMNS FROM versiones_pagina")->fetchAll(PDO::FETCH_COLUMN);
      if(!in_array('html',$colsVer,true)) $pdo->exec("ALTER TABLE versiones_pagina ADD COLUMN html LONGTEXT NULL");
      if(!in_array('nota',$colsVer,true)) $pdo->exec("ALTER TABLE versiones_pagina ADD COLUMN nota VARCHAR(255) NULL");
    }

    // Inserta estadísticas faltantes para páginas ya existentes.
    $pdo->exec("INSERT IGNORE INTO estadisticas_pagina(pagina_id,vistas,ediciones)
                SELECT id,0,0 FROM paginas");

    // Configuración de fuente por defecto si no existe.
    $st=$pdo->prepare("INSERT IGNORE INTO configuracion_sitio(clave_configuracion,valor_configuracion) VALUES(?,?)");
    $st->execute(['fuente_global','Noto Sans']);
    $st->execute(['fuente_publica','Noto Sans']);
    $st->execute(['fuente_panel','Noto Sans']);
    $st->execute(['url_base','http://localhost/MIA-DCI/sistema/admin']);
    $st->execute(['correo_remitente','no-reply@localhost']);
    $st->execute(['nombre_remitente','Sistema TecNM Posgrados']);
    $st->execute(['notificar_cambios','1']);
    $st->execute(['modo_envio_correo','mail']);
    $st->execute(['smtp_host','']);
    $st->execute(['smtp_puerto','587']);
    $st->execute(['smtp_seguridad','tls']);
    $st->execute(['smtp_usuario','']);
    $st->execute(['smtp_contrasena','']);
  }catch(Throwable $e){
    // No detenemos todo el sistema por una reparación secundaria.
    // Si falta una tabla principal como paginas/usuarios, database.sql sí debe importarse completo.
  }
}
ensure_cms_schema($pdo);

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function base_path($rel=''){ return realpath(__DIR__.'/../../').($rel?DIRECTORY_SEPARATOR.$rel:''); }
function safe_page_path($rel){
  $root=realpath(__DIR__.'/../../'); $full=realpath($root.DIRECTORY_SEPARATOR.$rel);
  if(!$full || strpos($full,$root)!==0 || !preg_match('/\.html$/i',$full)) return false;
  return $full;
}
?>