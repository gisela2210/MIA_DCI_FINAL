<?php
require_once __DIR__.'/layout.php';
if(!is_admin()) die('Solo el administrador puede reparar encabezados.');

function cms_remove_node_repair($n){ if($n && $n->parentNode) $n->parentNode->removeChild($n); }
function repair_gobmx_duplicates($html){
    libxml_use_internal_errors(true);
    $dom=new DOMDocument('1.0','UTF-8');
    $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    $xp=new DOMXPath($dom);
    foreach($xp->query('//base | //script[@id="cms-preview-tools"] | //style[@id="cms-preview-style"] | //script[contains(text(),"window.__CMS_PREVIEW__")]') as $n){ cms_remove_node_repair($n); }
    foreach($xp->query('//*[@contenteditable]') as $n){ $n->removeAttribute('contenteditable'); }
    foreach($xp->query('//*[contains(concat(" ",normalize-space(@class)," ")," __cmsSel ")]') as $n){
        $class=preg_replace('/\s*__cmsSel\s*/',' ', $n->getAttribute('class'));
        $class=trim(preg_replace('/\s+/',' ', $class));
        if($class==='') $n->removeAttribute('class'); else $n->setAttribute('class',$class);
    }
    $remove=[];
    foreach($xp->query('//*[self::header or self::nav or self::footer or self::div or self::section]') as $n){
        $txt=trim(preg_replace('/\s+/u',' ', $n->textContent));
        $cls=' '.$n->getAttribute('class').' ';
        $id=' '.$n->getAttribute('id').' ';
        $keep=preg_match('/\b(brand-strip|site-header|site-nav|site-footer|main-nav|topbar|hero|section)\b/i',$cls);
        if($keep) continue;
        if(preg_match('/\b(navbar|sub-navbar|main-footer|footer-pleca|gobmx|gob-mx|gobmx-header|gobmx-footer|pleca)\b/i',$cls.$id)){ $remove[]=$n; continue; }
        if((stripos($txt,'Gobierno de México')!==false || stripos($txt,'Gobierno de Mexico')!==false || stripos($txt,'gob.mx')!==false) && (stripos($txt,'Trámites')!==false || stripos($txt,'Tramites')!==false || stripos($txt,'Datos abiertos')!==false || stripos($txt,'Denuncia contra')!==false)){ $remove[]=$n; continue; }
    }
    $body=$xp->query('//body')->item(0);
    if($body){
        $firstReal=null;
        foreach($body->childNodes as $c){
            if($c->nodeType===XML_ELEMENT_NODE){
                $cls=' '.$c->getAttribute('class').' ';
                if(stripos($cls,'skip-link')!==false || stripos($cls,'brand-strip')!==false){ $firstReal=$c; break; }
            }
        }
        if($firstReal){
            $toDelete=[];
            foreach($body->childNodes as $c){ if($c===$firstReal) break; if($c->nodeType===XML_ELEMENT_NODE) $toDelete[]=$c; }
            foreach($toDelete as $c) $remove[]=$c;
        }
    }
    $unique=[]; foreach($remove as $n){ if($n && $n->parentNode) $unique[spl_object_hash($n)]=$n; }
    foreach($unique as $n) cms_remove_node_repair($n);
    $gobScripts=[]; foreach($xp->query('//script[contains(@src,"gobmx.js")]') as $s){ $gobScripts[]=$s; }
    if(count($gobScripts)>1){ for($i=0;$i<count($gobScripts)-1;$i++) cms_remove_node_repair($gobScripts[$i]); }
    $out=$dom->saveHTML();
    $out=preg_replace('/^<\?xml[^>]+>\s*/','',$out);
    if(stripos($out,'<!doctype')!==0) $out='<!doctype html>' . "\n" . $out;
    return $out;
}

$rows=$pdo->query('SELECT p.id, p.programa_id AS program_id, p.titulo AS title, p.ruta_archivo AS file_path, p.orden, p.visible, pr.codigo AS code FROM paginas p JOIN programas pr ON p.programa_id=pr.id ORDER BY pr.codigo,p.id')->fetchAll(PDO::FETCH_ASSOC);
$done=[];
foreach($rows as $p){
    $file=safe_page_path($p['file_path']);
    if(!$file || !file_exists($file)) continue;
    $before=file_get_contents($file);
    $after=repair_gobmx_duplicates($before);
    if($after!==$before){
        $pdo->prepare('INSERT INTO versiones_pagina(pagina_id,usuario_id,html,nota) VALUES(?,?,?,?)')->execute([$p['id'],user()['id'],$before,'Respaldo antes de reparar encabezados duplicados']);
        file_put_contents($file,$after);
        log_activity('reparacion','Se repararon encabezados duplicados en '.$p['title'],$p['id'],user()['id']);
        notify_usuarios('Encabezados reparados','El administrador reparó encabezados duplicados en '.$p['title'],$p['id'],user()['id']);
        $done[]=$p['title'];
    }
}
header_admin('Reparar encabezados');
?>
<div class="main-card card p-4">
  <h4 class="card-title-tecnm"><i class="bi bi-tools"></i> Reparación de encabezados</h4>
  <?php if($done): ?>
    <div class="alert alert-success rounded-4">Se limpiaron encabezados duplicados y se creó respaldo en Versiones.</div>
    <ul><?php foreach($done as $d): ?><li><?=e($d)?></li><?php endforeach; ?></ul>
  <?php else: ?>
    <div class="alert alert-info rounded-4">No se encontraron encabezados duplicados en los archivos actuales.</div>
  <?php endif; ?>
  <a href="pages.php" class="btn btn-tecnm btn-rounded"><i class="bi bi-arrow-left"></i> Volver a páginas</a>
</div>
<?php footer_admin(); ?>
