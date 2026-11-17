<?php require_once __DIR__.'/auth.php'; require_once __DIR__.'/notifier.php'; require_login();
$snapshot_id=(int)($_POST['snapshot_id']??0); if(!$snapshot_id) die('Falta versión');
$st=$pdo->prepare('SELECT ps.id, ps.pagina_id AS page_id, ps.usuario_id AS user_id, ps.html, ps.nota AS note, ps.creado_en AS created_at, p.ruta_archivo AS file_path, p.titulo AS title, pr.codigo AS code FROM versiones_pagina ps JOIN paginas p ON ps.pagina_id=p.id JOIN programas pr ON p.programa_id=pr.id WHERE ps.id=?'); $st->execute([$snapshot_id]); $v=$st->fetch(PDO::FETCH_ASSOC); if(!$v || !can_program($v['code'])) die('Sin permiso');
$file=safe_page_path($v['file_path']); if(!$file) die('Archivo no permitido');
$current=file_exists($file)?file_get_contents($file):'';
if($current!=='') $pdo->prepare('INSERT INTO versiones_pagina(pagina_id,usuario_id,html,nota) VALUES(?,?,?,?)')->execute([$v['page_id'],user()['id'],$current,'Respaldo automático antes de restaurar versión']);
file_put_contents($file,$v['html']);
$pdo->prepare('UPDATE estadisticas_pagina SET ediciones=ediciones+1,ultima_edicion_en=NOW() WHERE pagina_id=?')->execute([$v['page_id']]);
$desc='Versión restaurada: '.$v['title'].' por '.user()['username']; log_activity('restauracion',$desc,$v['page_id'],user()['id']); notify_usuarios('Versión restaurada',$desc,$v['page_id'],user()['id']); notify_admins('Versión restaurada',$desc);
header('Location: preview.php?id='.$v['page_id']);
?>
