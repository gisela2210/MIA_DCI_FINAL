<?php require_once 'layout.php';

function db_col_exists($tabla,$columna){
    global $pdo;
    try{
        $st=$pdo->prepare('SHOW COLUMNS FROM `'.$tabla.'` LIKE ?');
        $st->execute([$columna]);
        return (bool)$st->fetch(PDO::FETCH_ASSOC);
    }catch(Throwable $e){ return false; }
}
function db_table_exists($tabla){
    global $pdo;
    try{
        $st=$pdo->prepare('SHOW TABLES LIKE ?');
        $st->execute([$tabla]);
        return (bool)$st->fetchColumn();
    }catch(Throwable $e){ return false; }
}
function ensure_col($tabla,$columna,$definicion){
    global $pdo;
    try{ if(!db_col_exists($tabla,$columna)) $pdo->exec('ALTER TABLE `'.$tabla.'` ADD COLUMN '.$definicion); }catch(Throwable $e){}
}
function columna_contenido_seccion(){
    if(db_col_exists('secciones_pagina','contenido_html')) return 'contenido_html';
    if(db_col_exists('secciones_pagina','contenido')) return 'contenido';
    ensure_col('secciones_pagina','contenido_html','contenido_html LONGTEXT NULL');
    return db_col_exists('secciones_pagina','contenido_html') ? 'contenido_html' : 'contenido_html';
}
function ensure_menu_schema(){
    global $pdo;
    try{
        if(!db_table_exists('secciones_pagina')){
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
        }
        ensure_col('secciones_pagina','tipo_seccion',"tipo_seccion VARCHAR(80) DEFAULT 'contenido'");
        ensure_col('secciones_pagina','titulo','titulo VARCHAR(220) NULL');
        ensure_col('secciones_pagina','subtitulo','subtitulo VARCHAR(220) NULL');
        ensure_col('secciones_pagina','contenido_html','contenido_html LONGTEXT NULL');
        ensure_col('secciones_pagina','ruta_imagen','ruta_imagen VARCHAR(255) NULL');
        ensure_col('secciones_pagina','orden','orden INT DEFAULT 0');
        ensure_col('secciones_pagina','visible','visible TINYINT(1) DEFAULT 1');
        ensure_col('paginas','creada_desde_dashboard','creada_desde_dashboard TINYINT(1) DEFAULT 0');
        ensure_col('paginas','tipo_menu',"tipo_menu VARCHAR(30) DEFAULT 'principal'");
        ensure_col('paginas','slug_menu','slug_menu VARCHAR(120) NULL');
        if(db_table_exists('versiones_pagina')){
            ensure_col('versiones_pagina','html','html LONGTEXT NULL');
            ensure_col('versiones_pagina','nota','nota VARCHAR(255) NULL');
        }
    }catch(Throwable $e){}
}
ensure_menu_schema();

function slug_espanol($texto){
    $texto = trim(mb_strtolower($texto, 'UTF-8'));
    $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'];
    $texto = strtr($texto, $map);
    $texto = preg_replace('/[^a-z0-9]+/','-', $texto);
    $texto = trim($texto,'-');
    return $texto ?: 'nueva-seccion';
}
function programa_folder($codigo){ return $codigo === 'MIA' ? 'FRONTEND-MIA' : 'FRONTEND-DCI'; }
function ruta_base(){ return realpath(__DIR__.'/../..'); }
function ruta_abs_from_ruta_archivo($ruta){ return ruta_base().DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ruta); }
function archivos_html_programa($folder){
    $base = ruta_base().DIRECTORY_SEPARATOR.$folder;
    $files = [];
    if(file_exists($base.'/index.html')) $files[]=$base.'/index.html';
    foreach(glob($base.'/pages/*.html') ?: [] as $f) $files[]=$f;
    return array_values(array_unique($files));
}
function href_para_archivo($archivo, $folder, $slug){
    $norm = str_replace('\\','/',$archivo);
    return preg_match('#/'.$folder.'/pages/#',$norm) ? './'.$slug.'.html' : './pages/'.$slug.'.html';
}
function ya_tiene_link($html, $slug){
    return preg_match('/href=["\'](?:\.\/pages\/|\.\/|\.\.\/pages\/)?'.preg_quote($slug,'/').'\.html["\']/i', $html);
}
function titulo_desde_html($html,$fallback){
    if(preg_match('/<h1[^>]*>(.*?)<\/h1>/is',$html,$m)) return trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8')) ?: $fallback;
    if(preg_match('/<title[^>]*>(.*?)<\/title>/is',$html,$m)) return trim(preg_replace('/\s*\|.*$/','',html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8'))) ?: $fallback;
    return $fallback;
}
function insertar_link_menu($html, $href, $titulo, $tipo){
    $hrefEsc = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
    $tituloEsc = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    $link = '<a href="'.$hrefEsc.'">'.$tituloEsc.'</a>';

    if($tipo === 'submenu'){
        if(preg_match('/<div\s+class=["\']nav-submenu["\'][^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)){
            $posInicio = $m[0][1] + strlen($m[0][0]);
            $posFin = stripos($html, '</div>', $posInicio);
            if($posFin !== false) return substr($html,0,$posFin)."\n        ".$link."\n      ".substr($html,$posFin);
        }
        return $html;
    }
    $patrones = [
        '/(<a\s+href=["\']\.\/pages\/contacto\.html["\'][^>]*>\s*Contacto\s*<\/a>)/i',
        '/(<a\s+href=["\']\.\/contacto\.html["\'][^>]*>\s*Contacto\s*<\/a>)/i',
        '/(<a\s+href=["\']\.\.\/pages\/contacto\.html["\'][^>]*>\s*Contacto\s*<\/a>)/i'
    ];
    foreach($patrones as $p){ if(preg_match($p,$html)) return preg_replace($p, $link."\n        ".'$1', $html, 1); }
    return preg_replace('/(<\/nav>)/i', "        ".$link."\n      ".'$1', $html, 1);
}
function actualizar_texto_link_menu($html, $slug, $titulo){
    $tituloEsc = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    return preg_replace('/(<a\b[^>]*href=["\'](?:\.\/pages\/|\.\/|\.\.\/pages\/)?'.preg_quote($slug,'/').'\.html["\'][^>]*>)(.*?)(<\/a>)/is', '$1'.$tituloEsc.'$3', $html);
}
function eliminar_link_menu($html, $slug){
    return preg_replace('/\s*<a\b[^>]*href=["\'](?:\.\/pages\/|\.\/|\.\.\/pages\/)?'.preg_quote($slug,'/').'\.html["\'][^>]*>.*?<\/a>\s*/is', "\n", $html);
}
function limpiar_saltos_literal($txt){
    $txt = str_replace(['\\r\\n','\\n','\\r'], "\n", $txt);
    return trim($txt);
}
function generar_pagina_desde_plantilla($folder, $titulo, $contenido, $imagen=''){
    $base = ruta_base().DIRECTORY_SEPARATOR.$folder;
    $plantilla = file_exists($base.'/pages/contacto.html') ? $base.'/pages/contacto.html' : $base.'/index.html';
    $html = file_get_contents($plantilla);
    $tituloEsc = htmlspecialchars($titulo,ENT_QUOTES,'UTF-8');
    $contenido = limpiar_saltos_literal($contenido ?: 'Escribe aquí el contenido de esta sección desde la vista previa editable.');
    $contenidoSeguro = nl2br(htmlspecialchars($contenido,ENT_QUOTES,'UTF-8'));
    $imgHtml = $imagen ? '<img src="'.htmlspecialchars($imagen,ENT_QUOTES,'UTF-8').'" alt="'.$tituloEsc.'">' : '';
    $html = preg_replace('/<title>.*?<\/title>/is', '<title>'.$tituloEsc.' | TecNM Veracruz</title>', $html, 1);
    $main = <<<HTML
<main id="main-content">
  <section class="page-hero">
    {$imgHtml}
    <div class="container page-hero__content">
      <p class="eyebrow">Sección</p>
      <h1>{$tituloEsc}</h1>
      <p class="lead">Contenido administrable agregado desde el dashboard.</p>
    </div>
  </section>
  <section class="section section--white">
    <div class="container">
      <h2>{$tituloEsc}</h2>
      <p>{$contenidoSeguro}</p>
    </div>
  </section>
</main>
HTML;
    if(preg_match('/<main\b[^>]*>.*?<\/main>/is',$html)) return preg_replace('/<main\b[^>]*>.*?<\/main>/is',$main,$html,1);
    return str_ireplace('</body>',$main."\n</body>",$html);
}
function actualizar_contenido_pagina_creada($html,$titulo,$contenido,$imagen=''){
    $tituloEsc = htmlspecialchars($titulo,ENT_QUOTES,'UTF-8');
    $contenido = limpiar_saltos_literal($contenido ?: 'Escribe aquí el contenido de esta sección desde la vista previa editable.');
    $contenidoSeguro = nl2br(htmlspecialchars($contenido,ENT_QUOTES,'UTF-8'));
    $imgHtml = $imagen ? '<img src="'.htmlspecialchars($imagen,ENT_QUOTES,'UTF-8').'" alt="'.$tituloEsc.'">' : '';
    $main = <<<HTML
<main id="main-content">
  <section class="page-hero">
    {$imgHtml}
    <div class="container page-hero__content">
      <p class="eyebrow">Sección</p>
      <h1>{$tituloEsc}</h1>
      <p class="lead">Contenido administrable agregado desde el dashboard.</p>
    </div>
  </section>
  <section class="section section--white">
    <div class="container">
      <h2>{$tituloEsc}</h2>
      <p>{$contenidoSeguro}</p>
    </div>
  </section>
</main>
HTML;
    if(preg_match('/<main\b[^>]*>.*?<\/main>/is',$html)) return preg_replace('/<main\b[^>]*>.*?<\/main>/is',$main,$html,1);
    return str_ireplace('</body>',$main."\n</body>",$html);
}
function guardar_version_archivo($rutaArchivo,$paginaId,$descripcion){
    global $pdo;
    if(!$rutaArchivo || !file_exists($rutaArchivo) || !$paginaId) return;
    $contenido = file_get_contents($rutaArchivo);
    try{
        if(db_col_exists('versiones_pagina','html')){
            $pdo->prepare('INSERT INTO versiones_pagina(pagina_id,usuario_id,html,nota,creado_en) VALUES(?,?,?,?,NOW())')
                ->execute([$paginaId,user()['id'] ?? null,$contenido,$descripcion]);
        }elseif(db_col_exists('versiones_pagina','contenido_html')){
            $pdo->prepare('INSERT INTO versiones_pagina(pagina_id,usuario_id,contenido_html,descripcion,creado_en) VALUES(?,?,?,?,NOW())')
                ->execute([$paginaId,user()['id'] ?? null,$contenido,$descripcion]);
        }
    }catch(Throwable $e){}
}
function insertar_seccion_pagina($pagina_id,$titulo,$contenido,$imagen){
    global $pdo;
    $col = columna_contenido_seccion();
    $sql = 'INSERT INTO secciones_pagina(pagina_id,tipo_seccion,titulo,`'.$col.'`,ruta_imagen,orden,visible) VALUES(?,?,?,?,?,1,1)';
    $pdo->prepare($sql)->execute([$pagina_id,'pagina_menu',$titulo,'<p>'.htmlspecialchars(limpiar_saltos_literal($contenido),ENT_QUOTES,'UTF-8').'</p>',$imagen ?: null]);
}
function actualizar_seccion_pagina($pagina_id,$titulo,$contenido,$imagen){
    global $pdo;
    $col = columna_contenido_seccion();
    $sql='UPDATE secciones_pagina SET titulo=?, `'.$col.'`=?, ruta_imagen=? WHERE pagina_id=? AND tipo_seccion="pagina_menu"';
    $pdo->prepare($sql)->execute([$titulo,'<p>'.htmlspecialchars(limpiar_saltos_literal($contenido),ENT_QUOTES,'UTF-8').'</p>',$imagen ?: null,$pagina_id]);
    if(!$pdo->prepare('SELECT COUNT(*) FROM secciones_pagina WHERE pagina_id=? AND tipo_seccion="pagina_menu"')->execute([$pagina_id])){}
}
function obtener_pagina_menu($id){
    global $pdo;
    $col = columna_contenido_seccion();
    $selectContenido = db_col_exists('secciones_pagina',$col) ? 's.`'.$col.'`' : 'NULL';
    $st=$pdo->prepare('SELECT p.*, pr.codigo AS codigo_programa, pr.nombre AS nombre_programa,
                              COALESCE('.$selectContenido.',"") AS contenido_html,
                              s.ruta_imagen,
                              COALESCE(s.titulo, REPLACE(REPLACE(SUBSTRING_INDEX(p.titulo," |",1),"-"," "),"_"," ")) AS titulo_seccion
                       FROM paginas p
                       JOIN programas pr ON pr.id=p.programa_id
                       LEFT JOIN secciones_pagina s ON s.pagina_id=p.id AND s.tipo_seccion="pagina_menu"
                       WHERE p.id=? LIMIT 1');
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC);
}
function puede_gestionar_pagina_menu($p){ return $p && can_program($p['codigo_programa']); }
function sincronizar_secciones_menu_huerfanas(){
    global $pdo;
    $originales = ['contacto.html','enlaces.html','estudiantes.html','instalaciones.html','lineas-investigacion.html','mapa-curricular.html','otros.html','perfil-egreso.html','perfil-ingreso.html','protocolos-etica.html','retribucion-social.html'];
    foreach(['DCI'=>'FRONTEND-DCI','MIA'=>'FRONTEND-MIA'] as $codigo=>$folder){
        $pidSt=$pdo->prepare('SELECT id FROM programas WHERE codigo=? LIMIT 1');
        $pidSt->execute([$codigo]);
        $programa_id=(int)$pidSt->fetchColumn();
        if(!$programa_id) continue;
        foreach(glob(ruta_base().DIRECTORY_SEPARATOR.$folder.'/pages/*.html') ?: [] as $archivo){
            $base=basename($archivo);
            if(in_array($base,$originales,true)) continue;
            $ruta=$folder.'/pages/'.$base;
            $ex=$pdo->prepare('SELECT id FROM paginas WHERE ruta_archivo=? LIMIT 1');
            $ex->execute([$ruta]);
            if($ex->fetchColumn()) continue;
            $html=file_get_contents($archivo);
            $slug=basename($base,'.html');
            $titulo=titulo_desde_html($html, ucwords(str_replace('-',' ',$slug)));
            $orden=(int)$pdo->query('SELECT COALESCE(MAX(orden),0)+1 FROM paginas')->fetchColumn();
            $st=$pdo->prepare('INSERT INTO paginas(programa_id,titulo,ruta_archivo,orden,visible,creada_desde_dashboard,tipo_menu,slug_menu) VALUES(?,?,?,?,1,1,"principal",?)');
            $st->execute([$programa_id,$titulo.' | TecNM Veracruz',$ruta,$orden,$slug]);
            $pagina_id=(int)$pdo->lastInsertId();
            insertar_seccion_pagina($pagina_id,$titulo,'Contenido administrable agregado desde el dashboard.','');
            try{ $pdo->prepare('INSERT IGNORE INTO estadisticas_pagina(pagina_id,vistas,ediciones) VALUES(?,0,0)')->execute([$pagina_id]); }catch(Throwable $e){}
            try{ $pdo->prepare('INSERT IGNORE INTO permisos_usuario_pagina(usuario_id,pagina_id,puede_ver,puede_editar) SELECT id,?,1,1 FROM usuarios WHERE rol="admin" OR codigo_programa="AMBOS" OR codigo_programa=?')->execute([$pagina_id,$codigo]); }catch(Throwable $e){}
        }
    }
}
sincronizar_secciones_menu_huerfanas();

$mensaje=''; $error='';
$accion = $_POST['accion'] ?? '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    if($accion==='crear'){
        $codigo = strtoupper($_POST['codigo_programa'] ?? '');
        $titulo = trim($_POST['titulo'] ?? '');
        $slug = slug_espanol(($_POST['slug'] ?? '') ?: $titulo);
        $contenido = limpiar_saltos_literal($_POST['contenido'] ?? '');
        $imagen = trim($_POST['imagen'] ?? '');
        $tipo = ($_POST['tipo_menu'] ?? 'principal') === 'submenu' ? 'submenu' : 'principal';
        if(!in_array($codigo,['DCI','MIA'],true)) $error='Selecciona DCI o MIA.';
        elseif(!can_program($codigo)) $error='No tienes permiso para agregar secciones en este programa.';
        elseif($titulo==='') $error='Escribe el nombre de la sección.';
        else{
            $dest=null;
            try{
                $folder=programa_folder($codigo); $base=ruta_base().DIRECTORY_SEPARATOR.$folder; $pagesDir=$base.'/pages';
                if(!is_dir($pagesDir)) mkdir($pagesDir,0777,true);
                $dest=$pagesDir.'/'.$slug.'.html';
                if(file_exists($dest)) throw new Exception('Ya existe una página con esa ruta: '.$slug.'.html');
                $programa_id_st=$pdo->prepare('SELECT id FROM programas WHERE codigo=?'); $programa_id_st->execute([$codigo]); $programa_id=(int)$programa_id_st->fetchColumn();
                if(!$programa_id) throw new Exception('No existe el programa '.$codigo.' en la base de datos.');
                $pdo->beginTransaction();
                $htmlNuevo=generar_pagina_desde_plantilla($folder,$titulo,$contenido,$imagen);
                if(file_put_contents($dest,$htmlNuevo)===false) throw new Exception('No se pudo crear el archivo en '.$dest);
                $actualizados=0;
                foreach(archivos_html_programa($folder) as $archivo){
                    $html=file_get_contents($archivo); if(ya_tiene_link($html,$slug)) continue;
                    $href=href_para_archivo($archivo,$folder,$slug); $nuevo=insertar_link_menu($html,$href,$titulo,$tipo);
                    if($nuevo!==$html){ file_put_contents($archivo,$nuevo); $actualizados++; }
                }
                $orden=(int)$pdo->query('SELECT COALESCE(MAX(orden),0)+1 FROM paginas')->fetchColumn();
                $ruta=$folder.'/pages/'.$slug.'.html';
                $st=$pdo->prepare('INSERT INTO paginas(programa_id,titulo,ruta_archivo,orden,visible,creada_desde_dashboard,tipo_menu,slug_menu) VALUES(?,?,?,?,1,1,?,?)');
                $st->execute([$programa_id,$titulo.' | TecNM Veracruz',$ruta,$orden,$tipo,$slug]);
                $pagina_id=(int)$pdo->lastInsertId();
                try{ $pdo->prepare('INSERT IGNORE INTO estadisticas_pagina(pagina_id,vistas,ediciones) VALUES(?,0,0)')->execute([$pagina_id]); }catch(Throwable $e){}
                insertar_seccion_pagina($pagina_id,$titulo,$contenido,$imagen);
                try{ $pdo->prepare('INSERT IGNORE INTO permisos_usuario_pagina(usuario_id,pagina_id,puede_ver,puede_editar) SELECT id,?,1,1 FROM usuarios WHERE rol="admin" OR codigo_programa="AMBOS" OR codigo_programa=?')->execute([$pagina_id,$codigo]); }catch(Throwable $e){}
                guardar_version_archivo($dest,$pagina_id,'Versión inicial al crear sección del menú');
                $pdo->commit();
                $desc='Se agregó sección al menú '.$codigo.': '.$titulo;
                log_activity('seccion_menu',$desc,$pagina_id,user()['id']); notify_usuarios('Nueva sección agregada',$desc,$pagina_id,user()['id']);
                $mensaje='Sección agregada correctamente. Ahora aparece en la lista y se puede editar o eliminar. Archivos actualizados: '.$actualizados.'.';
            }catch(Throwable $e){
                if($pdo->inTransaction()) $pdo->rollBack();
                if($dest && file_exists($dest)) @unlink($dest);
                $error='Error: '.$e->getMessage();
            }
        }
    }elseif($accion==='editar'){
        $pagina_id=(int)($_POST['pagina_id'] ?? 0); $p=obtener_pagina_menu($pagina_id);
        if(!puede_gestionar_pagina_menu($p)) $error='No tienes permiso para editar esta sección.';
        else{
            $titulo=trim($_POST['titulo'] ?? ''); $contenido=limpiar_saltos_literal($_POST['contenido'] ?? ''); $imagen=trim($_POST['imagen'] ?? '');
            if($titulo==='') $error='El título no puede estar vacío.';
            else{
                try{
                    $archivo=ruta_abs_from_ruta_archivo($p['ruta_archivo']); $folder=programa_folder($p['codigo_programa']); $slug=basename($p['ruta_archivo'],'.html');
                    $pdo->beginTransaction(); guardar_version_archivo($archivo,$pagina_id,'Antes de editar sección del menú');
                    if(file_exists($archivo)){
                        $html=file_get_contents($archivo); $html=preg_replace('/<title>.*?<\/title>/is','<title>'.htmlspecialchars($titulo,ENT_QUOTES,'UTF-8').' | TecNM Veracruz</title>',$html,1);
                        $html=actualizar_contenido_pagina_creada($html,$titulo,$contenido,$imagen); file_put_contents($archivo,$html);
                    }
                    foreach(archivos_html_programa($folder) as $f){ $html=file_get_contents($f); $nuevo=actualizar_texto_link_menu($html,$slug,$titulo); if($nuevo!==$html) file_put_contents($f,$nuevo); }
                    $pdo->prepare('UPDATE paginas SET titulo=? WHERE id=?')->execute([$titulo.' | TecNM Veracruz',$pagina_id]);
                    actualizar_seccion_pagina($pagina_id,$titulo,$contenido,$imagen);
                    try{ $pdo->prepare('UPDATE estadisticas_pagina SET ediciones=ediciones+1, ultima_edicion_en=NOW() WHERE pagina_id=?')->execute([$pagina_id]); }catch(Throwable $e){}
                    $pdo->commit(); $desc='Se editó sección del menú '.$p['codigo_programa'].': '.$titulo; log_activity('editar_seccion_menu',$desc,$pagina_id,user()['id']); notify_usuarios('Sección editada',$desc,$pagina_id,user()['id']);
                    $mensaje='Sección actualizada correctamente.';
                }catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); $error='Error: '.$e->getMessage(); }
            }
        }
    }elseif($accion==='eliminar'){
        $pagina_id=(int)($_POST['pagina_id'] ?? 0); $p=obtener_pagina_menu($pagina_id);
        if(!puede_gestionar_pagina_menu($p)) $error='No tienes permiso para eliminar esta sección.';
        else{
            try{
                $archivo=ruta_abs_from_ruta_archivo($p['ruta_archivo']); $folder=programa_folder($p['codigo_programa']); $slug=basename($p['ruta_archivo'],'.html');
                guardar_version_archivo($archivo,$pagina_id,'Antes de eliminar sección del menú');
                foreach(archivos_html_programa($folder) as $f){ $html=file_get_contents($f); $nuevo=eliminar_link_menu($html,$slug); if($nuevo!==$html) file_put_contents($f,$nuevo); }
                if(file_exists($archivo)) @unlink($archivo);
                $pdo->beginTransaction();
                try{ $pdo->exec('SET FOREIGN_KEY_CHECKS=0'); }catch(Throwable $e){}
                foreach(['permisos_usuario_pagina','imagenes_pagina','estadisticas_pagina','eventos_vista_pagina','eventos_edicion','bitacora_actividad','notificaciones','notificaciones_correo','historial_restauraciones','versiones_pagina','subventanas_pagina','secciones_pagina'] as $tabla){
                    if(db_table_exists($tabla)){
                        try{ $pdo->prepare("DELETE FROM `$tabla` WHERE pagina_id=?")->execute([$pagina_id]); }catch(Throwable $e){}
                    }
                }
                $pdo->prepare('DELETE FROM paginas WHERE id=?')->execute([$pagina_id]);
                try{ $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); }catch(Throwable $e){}
                $pdo->commit();
                $desc='Se eliminó sección del menú '.$p['codigo_programa'].': '.preg_replace('/ \| TecNM Veracruz$/','',$p['titulo']);
                log_activity('eliminar_seccion_menu',$desc,null,user()['id']); notify_usuarios('Sección eliminada',$desc,null,user()['id']);
                $mensaje='Sección eliminada correctamente. Se quitó del menú azul y se borró su archivo.';
            }catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); try{ $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); }catch(Throwable $x){} $error='Error: '.$e->getMessage(); }
        }
    }
}

$editar_id=(int)($_GET['editar'] ?? 0); $editando=$editar_id ? obtener_pagina_menu($editar_id) : null; if($editando && !puede_gestionar_pagina_menu($editando)) $editando=null;
$contenido_editar=''; if($editando){ $contenido_editar = limpiar_saltos_literal(trim(strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $editando['contenido_html'] ?? '')))); }
$programas=[]; if(is_admin()) $programas=['DCI'=>'Doctorado en Ciencias de la Ingeniería','MIA'=>'Maestría en Inteligencia Artificial']; else $programas=[user()['program_code']=>user()['program_code']];
$permitidos = is_admin() ? ['DCI','MIA'] : [user()['program_code']]; $marks = implode(',', array_fill(0,count($permitidos),'?'));
$sql='SELECT p.id,p.titulo,p.ruta_archivo,p.visible,pr.codigo AS codigo_programa,
             COALESCE(s.titulo, SUBSTRING_INDEX(p.titulo," |",1)) AS titulo_seccion,
             s.ruta_imagen,s.creado_en
      FROM paginas p JOIN programas pr ON pr.id=p.programa_id
      LEFT JOIN secciones_pagina s ON s.pagina_id=p.id AND s.tipo_seccion="pagina_menu"
      WHERE pr.codigo IN ('.$marks.') AND (p.creada_desde_dashboard=1 OR s.tipo_seccion="pagina_menu")
      ORDER BY pr.codigo,p.id DESC';
$st=$pdo->prepare($sql); $st->execute($permitidos); $secciones=$st->fetchAll(PDO::FETCH_ASSOC);
header_admin('Secciones del menú');
?>
<div class="row g-3">
  <div class="col-xl-5">
    <div class="main-card card p-4">
      <h5 class="card-title-tecnm"><i class="bi bi-menu-button-wide"></i> <?= $editando ? 'Editar sección nueva' : 'Agregar sección al menú azul' ?></h5>
      <div class="help-box mb-3">Esto agrega o modifica una opción del menú azul como <b>Inicio, Perfil de ingreso, Estudiantes, Enlaces o Contacto</b>. No toca encabezados ni pies de página.</div>
      <?php if($mensaje): ?><div class="alert alert-success fw-semibold"><?=$mensaje?></div><?php endif; ?>
      <?php if($error): ?><div class="alert alert-danger fw-semibold"><?=e($error)?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="accion" value="<?= $editando ? 'editar' : 'crear' ?>">
        <?php if($editando): ?><input type="hidden" name="pagina_id" value="<?= (int)$editando['id'] ?>"><?php endif; ?>
        <label class="form-label fw-bold">Programa</label>
        <?php if($editando): ?>
          <input class="form-control mb-3" value="<?=e($editando['codigo_programa'])?>" disabled>
        <?php else: ?>
          <select name="codigo_programa" class="form-select mb-3" required><?php foreach($programas as $k=>$v): ?><option value="<?=e($k)?>"><?=e($k.' - '.$v)?></option><?php endforeach; ?></select>
        <?php endif; ?>
        <label class="form-label fw-bold">Nombre de la sección</label>
        <input name="titulo" class="form-control mb-3" value="<?= $editando ? e($editando['titulo_seccion']) : '' ?>" placeholder="Ejemplo: Convocatoria, Becas, Requisitos" required>
        <?php if(!$editando): ?>
          <label class="form-label fw-bold">Ruta / slug</label><input name="slug" class="form-control mb-3" placeholder="Se genera automático si lo dejas vacío">
          <label class="form-label fw-bold">Dónde ponerla</label><select name="tipo_menu" class="form-select mb-3"><option value="principal">Como sección principal del menú azul</option><option value="submenu">Como subopción dentro de Enlaces</option></select>
        <?php else: ?><div class="alert alert-info py-2">La ruta no se cambia para no romper enlaces. Puedes editar nombre, imagen y contenido.</div><?php endif; ?>
        <label class="form-label fw-bold">Imagen principal opcional</label><input name="imagen" class="form-control mb-3" value="<?= $editando ? e($editando['ruta_imagen'] ?? '') : '' ?>" placeholder="../assets/images/portada-inicio.jpg">
        <label class="form-label fw-bold">Contenido inicial</label><textarea name="contenido" rows="6" class="form-control mb-3" placeholder="Escribe el texto inicial de la sección"><?= $editando ? e($contenido_editar) : '' ?></textarea>
        <div class="d-grid gap-2"><button class="btn btn-tecnm btn-lg"><i class="bi <?= $editando ? 'bi-save' : 'bi-plus-circle' ?>"></i> <?= $editando ? 'Guardar cambios de la sección' : 'Crear sección en menú y página' ?></button><?php if($editando): ?><a class="btn btn-outline-secondary" href="menu_sections.php"><i class="bi bi-x-circle"></i> Cancelar edición</a><?php endif; ?></div>
      </form>
    </div>
  </div>
  <div class="col-xl-7">
    <div class="main-card card p-4 mb-3"><h5 class="card-title-tecnm"><i class="bi bi-list-ul"></i> Secciones nuevas administrables</h5><div class="table-responsive rounded-4 border"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Programa</th><th>Sección</th><th>Archivo</th><th>Acciones</th></tr></thead><tbody>
      <?php if(!$secciones): ?><tr><td colspan="4" class="text-center text-muted py-4">Todavía no hay secciones nuevas creadas desde el dashboard.</td></tr><?php endif; ?>
      <?php foreach($secciones as $s): ?><tr><td><span class="badge text-bg-primary"><?=e($s['codigo_programa'])?></span></td><td class="fw-bold"><?=e($s['titulo_seccion'])?></td><td><code><?=e($s['ruta_archivo'])?></code></td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="menu_sections.php?editar=<?=(int)$s['id']?>"><i class="bi bi-pencil-square"></i> Editar</a> <a class="btn btn-sm btn-outline-success" href="preview.php?id=<?=(int)$s['id']?>"><i class="bi bi-eye"></i> Vista</a> <form method="post" class="d-inline" onsubmit="return confirm('¿Seguro que quieres eliminar esta sección nueva? Se quitará del menú azul y se borrará su archivo.');"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="pagina_id" value="<?=(int)$s['id']?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button></form></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
    <div class="main-card card p-4"><h5 class="card-title-tecnm"><i class="bi bi-info-circle"></i> Cómo usarlo</h5><div class="row g-3"><div class="col-md-6"><div class="feature-box"><i class="bi bi-menu-button-wide"></i><h6>Sección del menú</h6><p>Es una opción del menú azul, como Inicio, Perfil de ingreso, Mapa curricular, Estudiantes, Enlaces o Contacto.</p></div></div><div class="col-md-6"><div class="feature-box"><i class="bi bi-file-earmark-text"></i><h6>Contenido interno</h6><p>Después de crearla, abre Vista previa para agregar texto, tablas, imágenes o botones dentro de esa página.</p></div></div></div></div>
  </div>
</div>
<?php footer_admin(); ?>
