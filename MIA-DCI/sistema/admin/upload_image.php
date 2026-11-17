<?php require_once __DIR__.'/auth.php'; require_once __DIR__.'/notifier.php'; require_login();
$page_id=(int)($_POST['page_id']??0); $program_id=(int)($_POST['program_id']??0); $label=trim($_POST['label']??'Imagen');
$st=$pdo->prepare('SELECT p.id, p.programa_id AS program_id, p.titulo AS title, p.ruta_archivo AS file_path, pr.codigo AS code FROM paginas p JOIN programas pr ON p.programa_id=pr.id WHERE p.id=?'); $st->execute([$page_id]); $p=$st->fetch(PDO::FETCH_ASSOC); if(!$p || !can_program($p['code'])) die('Sin permiso');
if(empty($_FILES['image']['tmp_name'])) die('No se subió imagen'); $ext=strtolower(pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION)); if(!in_array($ext,['png','jpg','jpeg','webp','gif'])) die('Formato no permitido');
$dir=__DIR__.'/../uploads'; if(!is_dir($dir)) mkdir($dir,0777,true); $name='img_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)).'.'.$ext; move_uploaded_file($_FILES['image']['tmp_name'],$dir.'/'.$name); $path='sistema/uploads/'.$name;
$pdo->prepare('INSERT INTO imagenes_pagina(programa_id,pagina_id,etiqueta,ruta_imagen,nombre_original,seccion_referencia) VALUES(?,?,?,?,?,?)')->execute([$program_id,$page_id,$label,$path,$_FILES['image']['name'],$p['title']]);
$desc='Imagen subida: '.$_FILES['image']['name'].' en '.$p['title'].' por '.user()['username'];
log_activity('imagen',$desc,$page_id,user()['id']);
notify_usuarios('Imagen subida',$desc,$page_id,user()['id']);
notify_admins('Imagen subida', $desc."\nRuta: ".$path."\nFecha: ".date('Y-m-d H:i:s'));
header('Location: preview.php?id='.$page_id); ?>
