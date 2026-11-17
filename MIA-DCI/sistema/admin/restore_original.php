<?php
require_once 'layout.php';
if(!function_exists('recursive_copy_selected')){
function recursive_copy_selected($src,$dst){
    if(!is_dir($src)) return 0;
    if(!is_dir($dst)) mkdir($dst,0777,true);
    $count=0;
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src,FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach($it as $item){
        $rel=substr($item->getPathname(), strlen($src)+1);
        $target=$dst.DIRECTORY_SEPARATOR.$rel;
        if($item->isDir()) { if(!is_dir($target)) mkdir($target,0777,true); continue; }
        $ext=strtolower(pathinfo($item->getFilename(),PATHINFO_EXTENSION));
        if(!in_array($ext,['html','css','js','md'],true)) continue;
        if(!is_dir(dirname($target))) mkdir(dirname($target),0777,true);
        copy($item->getPathname(),$target); $count++;
    }
    return $count;
}
}
$program=strtoupper($_GET['programa'] ?? $_POST['programa'] ?? '');
if(!in_array($program,['DCI','MIA'],true) || !can_program($program)){ http_response_code(403); echo 'No tienes permiso o programa inválido.'; exit; }
$folder=$program==='MIA'?'FRONTEND-MIA':'FRONTEND-DCI';
$base=realpath(__DIR__.'/../..');
$src=realpath(__DIR__.'/../originales/'.$folder);
$dst=$base.DIRECTORY_SEPARATOR.$folder;
if(!$src || !is_dir($src)){ die('No existe respaldo original para '.$program); }
try{
    $pdo->beginTransaction();
    $st=$pdo->prepare('SELECT p.id,p.ruta_archivo FROM paginas p JOIN programas pr ON pr.id=p.programa_id WHERE pr.codigo=?');
    $st->execute([$program]);
    foreach($st->fetchAll(PDO::FETCH_ASSOC) as $p){
        $file=$base.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $p['ruta_archivo']);
        if(file_exists($file)){
            try{
                $cols=$pdo->query("SHOW COLUMNS FROM versiones_pagina")->fetchAll(PDO::FETCH_COLUMN);
                if(in_array('html',$cols,true)){
                    $pdo->prepare('INSERT INTO versiones_pagina(pagina_id,usuario_id,html,nota,creado_en) VALUES(?,?,?,?,NOW())')
                        ->execute([$p['id'], user()['id'], file_get_contents($file), 'Antes de restaurar desde ZIP original']);
                }else{
                    $pdo->prepare('INSERT INTO versiones_pagina(pagina_id,usuario_id,contenido_html,descripcion,creado_en) VALUES(?,?,?,?,NOW())')
                        ->execute([$p['id'], user()['id'], file_get_contents($file), 'Antes de restaurar desde ZIP original']);
                }
            }catch(Throwable $e){}
        }
    }
    $pdo->commit();
}catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); }
$count=recursive_copy_selected($src,$dst);
$desc='Se restauró '.$program.' desde el ZIP original. Archivos restaurados: '.$count;
log_activity('restaurar_original',$desc,null,user()['id']);
notify_usuarios('Se restauró una página original',$desc,null,user()['id']);
header('Location: pages.php?ok='.urlencode($desc)); exit;
