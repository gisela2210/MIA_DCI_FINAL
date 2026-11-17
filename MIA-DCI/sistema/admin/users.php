<?php
require_once 'layout.php';
require_once 'notifier.php';
if(!is_admin()) die('Solo admin');

$msg='';
$verifyLink='';

function sincronizar_permisos_usuario($usuario_id){
  global $pdo;
  $st=$pdo->prepare('SELECT id, rol, codigo_programa FROM usuarios WHERE id=?');
  $st->execute([$usuario_id]);
  $u=$st->fetch(PDO::FETCH_ASSOC);
  if(!$u) return;
  $pdo->prepare('DELETE FROM permisos_usuario_pagina WHERE usuario_id=?')->execute([$usuario_id]);
  if($u['rol']==='admin' || $u['codigo_programa']==='AMBOS'){
    $sql='INSERT IGNORE INTO permisos_usuario_pagina(usuario_id,pagina_id,puede_ver,puede_editar) SELECT ?, id, 1, 1 FROM paginas';
    $pdo->prepare($sql)->execute([$usuario_id]);
  }else{
    $sql="INSERT IGNORE INTO permisos_usuario_pagina(usuario_id,pagina_id,puede_ver,puede_editar)
          SELECT ?, p.id, 1, 1 FROM paginas p JOIN programas pr ON pr.id=p.programa_id WHERE pr.codigo=?";
    $pdo->prepare($sql)->execute([$usuario_id,$u['codigo_programa']]);
  }
}

function asegurar_token_verificacion($id){
  global $pdo;
  $st=$pdo->prepare('SELECT token_verificacion_correo FROM usuarios WHERE id=?');
  $st->execute([$id]);
  $token=$st->fetchColumn();
  if(!$token){
    $token=bin2hex(random_bytes(32));
    $pdo->prepare('UPDATE usuarios SET token_verificacion_correo=? WHERE id=?')->execute([$token,$id]);
  }
  return $token;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action']??'create';

  if($action==='create'){
    $username=trim($_POST['username'] ?? '');
    $email=trim($_POST['email'] ?? '');
    $name=trim($_POST['full_name'] ?? '');
    $pass=$_POST['password'] ?? '';
    $prog=$_POST['program_code'] ?? 'DCI';
    if(!preg_match('/^[a-zA-Z0-9_.-]{4,60}$/',$username)) $msg='El usuario debe tener mínimo 4 caracteres y solo usar letras, números, punto, guion o guion bajo.';
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)) $msg='Correo inválido. Revisa que tenga formato usuario@dominio.com';
    elseif(!validate_password_rules($pass)) $msg='La contraseña debe tener mínimo 8 caracteres, mayúscula, minúscula, número y símbolo.';
    elseif(!in_array($prog,['DCI','MIA','AMBOS'],true)) $msg='Permiso inválido.';
    else {
      $hash=password_hash($pass,PASSWORD_DEFAULT);
      $token=bin2hex(random_bytes(32));
      $st=$pdo->prepare('INSERT INTO usuarios(nombre_usuario,correo,nombre_completo,contrasena_hash,rol,codigo_programa,token_verificacion_correo,correo_verificado_en,activo) VALUES(?,?,?,?,?,?,?,NULL,1)');
      try{
        $st->execute([$username,$email,$name,$hash,'editor',$prog,$token]);
        $newId=(int)$pdo->lastInsertId();
        sincronizar_permisos_usuario($newId);
        $verifyLink=send_verification_email($email,$username,$token,$newId);
        log_activity('usuario','Usuario “'.$username.'” creado y pendiente de verificación',null,user()['id']);
        notify_usuarios('Nuevo usuario creado','El administrador creó el usuario '.$username,null,user()['id']);
        $msg='Usuario creado. Se generó enlace de verificación de correo.';
      }catch(Exception $e){
        $msg='No se pudo crear: usuario o correo repetido.';
      }
    }
  }

  if($action==='update_user'){
    $id=(int)($_POST['id'] ?? 0);
    $username=trim($_POST['username'] ?? '');
    $email=trim($_POST['email'] ?? '');
    $name=trim($_POST['full_name'] ?? '');
    $role=$_POST['role'] ?? 'editor';
    $prog=$_POST['program_code'] ?? 'DCI';
    $active=isset($_POST['active']) ? 1 : 0;
    $verified=isset($_POST['verified']) ? 1 : 0;
    if($id<=0) $msg='Usuario inválido.';
    elseif(!preg_match('/^[a-zA-Z0-9_.-]{4,60}$/',$username)) $msg='El usuario debe tener mínimo 4 caracteres y solo usar letras, números, punto, guion o guion bajo.';
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)) $msg='Correo inválido.';
    elseif(!in_array($role,['admin','editor'],true)) $msg='Rol inválido.';
    elseif(!in_array($prog,['DCI','MIA','AMBOS'],true)) $msg='Programa inválido.';
    elseif($id==(int)user()['id'] && $active===0) $msg='No puedes desactivar tu propio usuario.';
    else{
      try{
        if($verified){
          $sql='UPDATE usuarios SET nombre_usuario=?, correo=?, nombre_completo=?, rol=?, codigo_programa=?, activo=?, correo_verificado_en=COALESCE(correo_verificado_en,NOW()), token_verificacion_correo=NULL WHERE id=?';
          $pdo->prepare($sql)->execute([$username,$email,$name,$role,$prog,$active,$id]);
        }else{
          $token=bin2hex(random_bytes(32));
          $sql='UPDATE usuarios SET nombre_usuario=?, correo=?, nombre_completo=?, rol=?, codigo_programa=?, activo=?, correo_verificado_en=NULL, token_verificacion_correo=? WHERE id=?';
          $pdo->prepare($sql)->execute([$username,$email,$name,$role,$prog,$active,$token,$id]);
        }
        sincronizar_permisos_usuario($id);
        log_activity('usuario','Usuario “'.$username.'” editado por administrador',null,user()['id']);
        create_notification($id,'Tu usuario fue actualizado','El administrador actualizó tus datos de acceso o permisos.');
        $msg='Usuario actualizado correctamente.';
      }catch(Exception $e){
        $msg='No se pudo actualizar: usuario o correo repetido.';
      }
    }
  }

  if($action==='delete_user'){
    $id=(int)($_POST['id'] ?? 0);
    if($id===(int)user()['id']){
      $msg='No puedes eliminar tu propio usuario.';
    }else{
      $st=$pdo->prepare('SELECT nombre_usuario, rol FROM usuarios WHERE id=?');
      $st->execute([$id]);
      $u=$st->fetch(PDO::FETCH_ASSOC);
      if(!$u){
        $msg='Usuario no encontrado.';
      }else{
        if($u['rol']==='admin'){
          $admins=(int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='admin' AND activo=1")->fetchColumn();
          if($admins<=1){
            $msg='No puedes eliminar al último administrador activo.';
          }
        }
        if(!$msg){
          $pdo->prepare('DELETE FROM usuarios WHERE id=?')->execute([$id]);
          log_activity('usuario','Usuario “'.$u['nombre_usuario'].'” eliminado por administrador',null,user()['id']);
          notify_usuarios('Usuario eliminado','El administrador eliminó el usuario '.$u['nombre_usuario'],null,user()['id']);
          $msg='Usuario eliminado correctamente.';
        }
      }
    }
  }

  if($action==='reset_password'){
    $id=(int)$_POST['id'];
    $pass=$_POST['new_password']??'';
    if(!validate_password_rules($pass)) $msg='La nueva contraseña no cumple la validación: mínimo 8 caracteres, mayúscula, minúscula, número y símbolo.';
    else {
      $st=$pdo->prepare('SELECT nombre_usuario FROM usuarios WHERE id=?');
      $st->execute([$id]);
      $username=$st->fetchColumn();
      if($username){
        $pdo->prepare('UPDATE usuarios SET contrasena_hash=?, token_sesion_actual=NULL, id_sesion_actual=NULL WHERE id=?')->execute([password_hash($pass,PASSWORD_DEFAULT),$id]);
        $msg='Contraseña actualizada para '.$username.'.';
        log_activity('usuario','Contraseña actualizada por admin para '.$username,null,user()['id']);
        create_notification($id,'Contraseña actualizada','El administrador actualizó tu contraseña. Entra y cámbiala desde Mi perfil.');
      }
    }
  }

  if($action==='resend'){
    $id=(int)$_POST['id'];
    $st=$pdo->prepare('SELECT id, nombre_usuario AS username, correo AS email FROM usuarios WHERE id=?');
    $st->execute([$id]);
    $u=$st->fetch(PDO::FETCH_ASSOC);
    if($u){
      $token=asegurar_token_verificacion($id);
      $verifyLink=send_verification_email($u['email'],$u['username'],$token,$id);
      $msg='Verificación reenviada/generada.';
    }
  }

  if($action==='manual_verify'){
    $id=(int)$_POST['id'];
    $pdo->prepare('UPDATE usuarios SET correo_verificado_en=NOW(), token_verificacion_correo=NULL WHERE id=?')->execute([$id]);
    $msg='Correo marcado como verificado por el administrador.';
  }

  if($action==='mark_unverified'){
    $id=(int)$_POST['id'];
    $token=bin2hex(random_bytes(32));
    $pdo->prepare('UPDATE usuarios SET correo_verificado_en=NULL, token_verificacion_correo=? WHERE id=?')->execute([$token,$id]);
    $msg='Correo marcado como pendiente de verificación.';
  }
}

$usuarios=$pdo->query('SELECT id, nombre_usuario AS username, correo AS email, nombre_completo AS full_name, contrasena_hash AS password_hash, rol AS role, codigo_programa AS program_code, foto_perfil AS profile_photo, correo_verificado_en AS email_verified_at, token_verificacion_correo AS email_verification_token, activo AS active, creado_en AS created_at FROM usuarios ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
header_admin('Usuarios');
?>
<?php if($msg): ?><div class="alert alert-info rounded-4"><?=e($msg)?><?php if($verifyLink): ?><hr><b>Enlace de verificación:</b><br><input class="form-control mt-2" value="<?=e($verifyLink)?>" readonly><div class="form-text">Si XAMPP no tiene configurado correo real, copia este enlace y mándalo manualmente.</div><?php endif; ?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="main-card card p-4">
      <h5 class="fw-bold text-primary"><i class="bi bi-person-plus-fill"></i> Agregar usuario</h5>
      <p class="text-muted small">Solo el administrador puede crear usuarios. El acceso será con <b>nombre de usuario</b> y contraseña.</p>
      <form method="post" class="user-form">
        <input type="hidden" name="action" value="create">
        <label class="form-label fw-bold">Nombre completo</label><input name="full_name" class="form-control mb-2" placeholder="Nombre completo" required>
        <label class="form-label fw-bold">Usuario</label><input name="username" class="form-control mb-2" placeholder="editor_dci" minlength="4" pattern="[A-Za-z0-9_.-]{4,60}" required>
        <label class="form-label fw-bold">Correo</label><input name="email" type="email" class="form-control mb-2" placeholder="usuario@dominio.com" required>
        <label class="form-label fw-bold">Contraseña</label><input name="password" type="password" class="form-control password-check mb-2" placeholder="Contraseña segura" required>
        <div class="strength-wrap mb-2"><div class="strength-bar"><span></span></div><div class="strength-label text-muted">Seguridad: escribe una contraseña</div></div>
        <div class="password-rules mb-3"><b>Debe tener:</b> 8 caracteres, mayúscula, minúscula, número y símbolo.</div>
        <label class="form-label fw-bold">Permiso</label><select name="program_code" class="form-select mb-3"><option>DCI</option><option>MIA</option><option>AMBOS</option></select>
        <button class="btn btn-tecnm btn-rounded w-100">Crear editor</button>
      </form>
    </div>
    <div class="help-box mt-3">
      <h5 class="fw-bold"><i class="bi bi-info-circle"></i> Importante</h5>
      <p>En XAMPP, la función <b>mail()</b> necesita estar configurada para enviar correos reales. Si no está configurada, el sistema genera el enlace de verificación para copiarlo.</p>
      <p class="mb-0">Las notificaciones internas sí aparecen en la campana aunque el correo no esté configurado. Configura el correo en <b>Configuración &gt; Correo</b>.</p>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="table-panel">
      <div class="table-toolbar"><div><h5 class="card-title-tecnm mb-1">Usuarios registrados</h5><div class="text-muted fw-semibold">Editar, eliminar, verificar correo, permisos y recuperación de contraseña.</div></div></div>
      <div class="table-responsive"><table class="table table-modern align-middle"><thead><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Programa</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
      <?php foreach($usuarios as $u): ?>
        <tr>
          <td><b><?=e($u['username'])?></b><br><span class="text-muted small"><?=e($u['full_name'])?></span><?php if(!$u['active']): ?><br><span class="badge text-bg-secondary">Inactivo</span><?php endif; ?></td>
          <td><?=e($u['email'])?></td>
          <td><span class="badge text-bg-primary rounded-pill"><?=e($u['role'])?></span></td>
          <td><?=e($u['program_code'])?></td>
          <td><?php if(!empty($u['email_verified_at'])): ?><span class="verified"><i class="bi bi-check-circle-fill"></i> Verificado</span><?php else: ?><span class="not-verified"><i class="bi bi-x-circle-fill"></i> Pendiente</span><?php endif; ?></td>
          <td>
            <div class="d-flex gap-1 flex-wrap">
              <button class="btn btn-sm btn-outline-primary btn-rounded" data-bs-toggle="collapse" data-bs-target="#edit<?=$u['id']?>"><i class="bi bi-pencil-square"></i> Editar</button>
              <button class="btn btn-sm btn-outline-warning btn-rounded" data-bs-toggle="collapse" data-bs-target="#pass<?=$u['id']?>">Cambiar contraseña</button>
              <?php if(empty($u['email_verified_at'])): ?>
                <form method="post"><input type="hidden" name="action" value="resend"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn btn-sm btn-outline-primary btn-rounded">Reenviar</button></form>
                <form method="post"><input type="hidden" name="action" value="manual_verify"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn btn-sm btn-outline-success btn-rounded">Verificar</button></form>
              <?php else: ?>
                <form method="post"><input type="hidden" name="action" value="mark_unverified"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn btn-sm btn-outline-secondary btn-rounded">Marcar pendiente</button></form>
              <?php endif; ?>
              <?php if((int)$u['id'] !== (int)user()['id']): ?>
              <form method="post" onsubmit="return confirm('¿Eliminar este usuario? Esta acción no se puede deshacer.');"><input type="hidden" name="action" value="delete_user"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn btn-sm btn-outline-danger btn-rounded"><i class="bi bi-trash"></i> Eliminar</button></form>
              <?php endif; ?>
            </div>
            <div class="collapse mt-2" id="edit<?=$u['id']?>">
              <form method="post" class="p-3 rounded-4 bg-light border">
                <input type="hidden" name="action" value="update_user"><input type="hidden" name="id" value="<?=$u['id']?>">
                <div class="row g-2">
                  <div class="col-md-6"><label class="form-label small fw-bold">Nombre completo</label><input name="full_name" class="form-control form-control-sm" value="<?=e($u['full_name'])?>" required></div>
                  <div class="col-md-6"><label class="form-label small fw-bold">Usuario</label><input name="username" class="form-control form-control-sm" value="<?=e($u['username'])?>" required pattern="[A-Za-z0-9_.-]{4,60}"></div>
                  <div class="col-md-6"><label class="form-label small fw-bold">Correo</label><input name="email" type="email" class="form-control form-control-sm" value="<?=e($u['email'])?>" required></div>
                  <div class="col-md-3"><label class="form-label small fw-bold">Rol</label><select name="role" class="form-select form-select-sm"><option value="editor" <?=$u['role']==='editor'?'selected':''?>>editor</option><option value="admin" <?=$u['role']==='admin'?'selected':''?>>admin</option></select></div>
                  <div class="col-md-3"><label class="form-label small fw-bold">Programa</label><select name="program_code" class="form-select form-select-sm"><option <?=$u['program_code']==='DCI'?'selected':''?>>DCI</option><option <?=$u['program_code']==='MIA'?'selected':''?>>MIA</option><option <?=$u['program_code']==='AMBOS'?'selected':''?>>AMBOS</option></select></div>
                </div>
                <div class="d-flex flex-wrap gap-3 mt-2">
                  <label class="form-check"><input class="form-check-input" type="checkbox" name="active" value="1" <?=$u['active']?'checked':''?>> <span class="form-check-label">Usuario activo</span></label>
                  <label class="form-check"><input class="form-check-input" type="checkbox" name="verified" value="1" <?=!empty($u['email_verified_at'])?'checked':''?>> <span class="form-check-label">Correo verificado</span></label>
                </div>
                <button class="btn btn-sm btn-primary fw-bold mt-2">Guardar cambios del usuario</button>
              </form>
            </div>
            <div class="collapse mt-2" id="pass<?=$u['id']?>">
              <form method="post" class="p-2 rounded-4 bg-light border user-form">
                <input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="<?=$u['id']?>">
                <label class="form-label small fw-bold">Nueva contraseña segura</label>
                <input name="new_password" type="password" class="form-control form-control-sm mb-2 password-check" required>
                <div class="strength-wrap mb-2"><div class="strength-bar"><span></span></div><div class="strength-label text-muted">Seguridad: escribe una contraseña</div></div>
                <button class="btn btn-sm btn-warning fw-bold w-100">Actualizar contraseña</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
    </div>
  </div>
</div>
<script>
(function(){
  function scorePassword(p){
    let score=0;
    if(p.length>=8) score++;
    if(/[A-Z]/.test(p)) score++;
    if(/[a-z]/.test(p)) score++;
    if(/[0-9]/.test(p)) score++;
    if(/[^A-Za-z0-9]/.test(p)) score++;
    if(p.length>=12) score++;
    return score;
  }
  function update(input){
    const form=input.closest('.user-form') || input.parentElement;
    const wrap=form.querySelector('.strength-wrap');
    if(!wrap) return;
    const bar=wrap.querySelector('.strength-bar span');
    const label=wrap.querySelector('.strength-label');
    const p=input.value || '';
    const s=scorePassword(p);
    wrap.classList.remove('weak','medium','strong');
    if(!p){ bar.style.width='0%'; label.textContent='Seguridad: escribe una contraseña'; return; }
    if(s<=2){ wrap.classList.add('weak'); bar.style.width='33%'; label.textContent='Seguridad: débil'; }
    else if(s<=4){ wrap.classList.add('medium'); bar.style.width='66%'; label.textContent='Seguridad: media'; }
    else { wrap.classList.add('strong'); bar.style.width='100%'; label.textContent='Seguridad: fuerte'; }
  }
  document.querySelectorAll('.password-check').forEach(function(input){ input.addEventListener('input', function(){ update(input); }); update(input); });
})();
</script>
<?php footer_admin(); ?>
