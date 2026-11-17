<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/notifier.php';
require_login();

function nodo_por_selector_simple(DOMDocument $dom, string $selector){
    $selector = trim($selector);
    if($selector === '') return null;
    $partes = array_filter(array_map('trim', explode('>', $selector)), fn($x)=>$x!=='');
    $bodyNodes = $dom->getElementsByTagName('body');
    $body = $bodyNodes->length ? $bodyNodes->item(0) : null;
    // La ruta CSS que viene desde la previsualización normalmente NO incluye html/body.
    // Antes se empezaba solo desde <html>, por eso no encontraba main, section, div, etc.
    // Ahora se intenta desde <html> y desde <body> para que el cambio sí caiga en el archivo real
    // de FRONTEND-DCI o FRONTEND-MIA.
    $actuales = array_filter([$dom->documentElement, $body]);

    foreach($partes as $parte){
        // Se ignora html/body si viene incluido en el path.
        if(in_array(strtolower($parte), ['html','body'])) continue;

        $tag = $parte;
        $id = null;
        $nth = null;

        if(preg_match('/^([a-z0-9_-]+)#(.+)$/i', $parte, $m)){
            $tag = strtolower($m[1]);
            $id = str_replace('\\:', ':', $m[2]);
        } elseif(preg_match('/^([a-z0-9_-]+):nth-of-type\((\d+)\)$/i', $parte, $m)){
            $tag = strtolower($m[1]);
            $nth = (int)$m[2];
        } elseif(preg_match('/^([a-z0-9_-]+)$/i', $parte, $m)){
            $tag = strtolower($m[1]);
        } else {
            return null;
        }

        $siguientes = [];
        foreach($actuales as $padre){
            if($id !== null){
                $xp = new DOMXPath($dom);
                $q = '//*[@id="'.str_replace('"','\\"',$id).'"]';
                $encontrados = $xp->query($q);
                foreach($encontrados as $n){
                    if(strtolower($n->nodeName)===$tag){ $siguientes[]=$n; break; }
                }
            } else {
                $contador = 0;
                foreach($padre->childNodes as $hijo){
                    if($hijo->nodeType === XML_ELEMENT_NODE && strtolower($hijo->nodeName)===$tag){
                        $contador++;
                        if($nth === null || $contador === $nth){ $siguientes[] = $hijo; if($nth !== null) break; }
                    }
                }
            }
        }
        if(empty($siguientes)) return null;
        $actuales = [$siguientes[0]];
    }
    return $actuales[0] ?? null;
}

function es_zona_protegida(DOMNode $n){
    while($n && $n->nodeType === XML_ELEMENT_NODE){
        $tag = strtolower($n->nodeName);
        $cls = ' '.($n->attributes?($n->attributes->getNamedItem('class')->nodeValue ?? ''):'').' ';
        $id = ' '.($n->attributes?($n->attributes->getNamedItem('id')->nodeValue ?? ''):'').' ';
        if(in_array($tag, ['header','footer','nav'])) return true;
        if(preg_match('/\b(brand-strip|site-header|site-nav|site-footer|topbar|gobmx|gob-mx|navbar|main-footer|footer-pleca)\b/i', $cls.$id)) return true;
        $n = $n->parentNode;
    }
    return false;
}

function reemplazar_texto(DOMDocument $dom, DOMNode $n, string $texto){
    while($n->firstChild){ $n->removeChild($n->firstChild); }
    $n->appendChild($dom->createTextNode($texto));
}

function insertar_html_despues(DOMDocument $dom, DOMNode $n, string $html){
    $tmp = new DOMDocument('1.0','UTF-8');
    libxml_use_internal_errors(true);
    $tmp->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    $wrap = $tmp->getElementsByTagName('div')->item(0);
    if(!$wrap) return;
    $ref = $n->nextSibling;
    foreach(iterator_to_array($wrap->childNodes) as $child){
        $import = $dom->importNode($child, true);
        if($ref) $n->parentNode->insertBefore($import, $ref); else $n->parentNode->appendChild($import);
    }
}

function insertar_html_dentro(DOMDocument $dom, DOMNode $n, string $html){
    $tmp = new DOMDocument('1.0','UTF-8');
    libxml_use_internal_errors(true);
    $tmp->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    $wrap = $tmp->getElementsByTagName('div')->item(0);
    if(!$wrap) return;
    foreach(iterator_to_array($wrap->childNodes) as $child){
        $import = $dom->importNode($child, true);
        $n->appendChild($import);
    }
}

function insertar_seccion_segura(DOMDocument $dom, ?DOMNode $n, string $html){
    if($n && !es_zona_protegida($n)){
        insertar_html_despues($dom, $n, $html);
        return true;
    }
    $mains = $dom->getElementsByTagName('main');
    $destino = $mains->length ? $mains->item(0) : $dom->getElementsByTagName('body')->item(0);
    if(!$destino) return false;
    insertar_html_dentro($dom, $destino, $html);
    return true;
}

function registrar_seccion_bd(PDO $pdo, int $pagina_id, string $tipo, string $html){
    try{
        $titulo = 'Contenido agregado desde dashboard';
        if(preg_match('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $html, $m)){
            $titulo = trim(strip_tags($m[1]));
        }
        $st=$pdo->prepare('INSERT INTO secciones_pagina(pagina_id,tipo_seccion,titulo,contenido_html,orden,visible) VALUES(?,?,?,?,999,1)');
        $st->execute([$pagina_id,$tipo,$titulo,$html]);
    }catch(Throwable $e){ }
}

$id = (int)($_POST['id'] ?? 0);
$cambiosJson = $_POST['cambios'] ?? '';
$htmlCompleto = $_POST['html'] ?? '';
if(!$id) die('Faltan datos de página.');

$st=$pdo->prepare('SELECT p.id, p.programa_id, p.titulo, p.ruta_archivo, pr.codigo FROM paginas p JOIN programas pr ON p.programa_id=pr.id WHERE p.id=?');
$st->execute([$id]);
$p=$st->fetch(PDO::FETCH_ASSOC);
if(!$p || !can_program($p['codigo'])) die('Sin permiso.');
$file=safe_page_path($p['ruta_archivo']);
if(!$file || !file_exists($file)) die('Archivo no permitido o inexistente.');

$previous = file_get_contents($file);
$cambios = json_decode($cambiosJson, true);
if(!is_array($cambios)) $cambios=[];

try{
    if(empty($cambios) && $htmlCompleto==='') die('No hay cambios para guardar.');

    $backupDir=__DIR__.'/../backups';
    if(!is_dir($backupDir)) mkdir($backupDir,0777,true);
    copy($file, $backupDir.'/'.date('Ymd_His').'_'.basename($file));

    $pdo->prepare('INSERT INTO versiones_pagina(pagina_id,usuario_id,html,nota) VALUES(?,?,?,?)')
        ->execute([$id,user()['id'],$previous,'Versión anterior antes de guardar']);

    $nuevo = $previous;

    if(!empty($cambios)){
        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0','UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        $dom->loadHTML('<?xml encoding="UTF-8">'.$previous, LIBXML_HTML_NODEFDTD);

        $aplicados = 0;
        foreach($cambios as $c){
            $selector = $c['selector'] ?? '';
            $tipo = $c['tipo'] ?? '';
            $n = nodo_por_selector_simple($dom, $selector);
            if($tipo !== 'seccion_nueva' && (!$n || es_zona_protegida($n))) continue;
            if($tipo === 'seccion_nueva' && $n && es_zona_protegida($n)) $n = null;

            if($tipo === 'texto'){
                reemplazar_texto($dom, $n, (string)($c['texto'] ?? ''));
                $aplicados++;
            } elseif($tipo === 'eliminar'){
                if($n && !es_zona_protegida($n) && $n->parentNode){
                    $n->parentNode->removeChild($n);
                    registrar_seccion_bd($pdo, $id, 'elemento_eliminado', '<!-- Elemento eliminado desde dashboard -->');
                    $aplicados++;
                }
            } elseif($tipo === 'imagen' && strtolower($n->nodeName)==='img'){
                $src = (string)($c['src'] ?? '');
                $alt = (string)($c['alt'] ?? '');
                if($src !== '') $n->setAttribute('src', $src);
                $n->setAttribute('alt', $alt);
                $aplicados++;
            } elseif($tipo === 'tabla'){
                insertar_html_despues($dom, $n, (string)($c['html'] ?? ''));
                registrar_seccion_bd($pdo, $id, 'tabla_agregada', (string)($c['html'] ?? ''));
                $aplicados++;
            } elseif($tipo === 'contenido_dentro'){
                insertar_html_dentro($dom, $n, (string)($c['html'] ?? ''));
                registrar_seccion_bd($pdo, $id, 'contenido_agregado', (string)($c['html'] ?? ''));
                $aplicados++;
            } elseif($tipo === 'contenido_debajo'){
                insertar_html_despues($dom, $n, (string)($c['html'] ?? ''));
                registrar_seccion_bd($pdo, $id, 'contenido_agregado', (string)($c['html'] ?? ''));
                $aplicados++;
            } elseif($tipo === 'slide_inicio'){
                if($n && !es_zona_protegida($n)){
                    insertar_html_dentro($dom, $n, (string)($c['html'] ?? ''));
                    registrar_seccion_bd($pdo, $id, 'slide_inicio', (string)($c['html'] ?? ''));
                    $aplicados++;
                }
            } elseif($tipo === 'seccion_nueva'){
                if(insertar_seccion_segura($dom, $n, (string)($c['html'] ?? ''))){
                    registrar_seccion_bd($pdo, $id, 'seccion_nueva', (string)($c['html'] ?? ''));
                    $aplicados++;
                }
            }
        }
        if($aplicados === 0) die('No se aplicó ningún cambio. Puede que seleccionaras una zona protegida como encabezado, menú o pie de página.');

        $nuevo = $dom->saveHTML();
        $nuevo = preg_replace('/^<\?xml[^>]+>\s*/','',$nuevo);
        if(stripos($nuevo,'<!doctype')!==0) $nuevo='<!doctype html>' . "\n" . $nuevo;
    } else {
        // Modo de emergencia: ya no se recomienda guardar el DOM completo, porque puede traer elementos inyectados.
        // Se mantiene solo por compatibilidad, pero no se usa desde la vista previa nueva.
        $nuevo = $htmlCompleto;
    }

    file_put_contents($file, $nuevo);
    $pdo->prepare('INSERT INTO versiones_pagina(pagina_id,usuario_id,html,nota) VALUES(?,?,?,?)')
        ->execute([$id,user()['id'],$nuevo,'Versión guardada desde vista previa editable']);
    $pdo->prepare('UPDATE estadisticas_pagina SET ediciones=ediciones+1,ultima_edicion_en=NOW() WHERE pagina_id=?')->execute([$id]);

    $desc='Página editada: '.$p['titulo'].' por '.user()['username'];
    log_activity('edicion',$desc,$id,user()['id']);
    notify_usuarios('Cambio realizado', $desc, $id, user()['id']);
    notify_admins('Cambio en página', $desc."\nArchivo: ".$p['ruta_archivo']."\nFecha: ".date('Y-m-d H:i:s'));

    echo 'Cambios guardados correctamente. No se tocó el encabezado ni el pie de página. Los cambios ya se reflejan en FRONTEND-MIA / FRONTEND-DCI.';
}catch(Exception $e){
    http_response_code(500);
    echo 'Error al guardar: '.$e->getMessage();
}
?>
