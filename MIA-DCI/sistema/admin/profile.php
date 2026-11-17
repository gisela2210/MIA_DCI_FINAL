<?php require_once 'layout.php'; $msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $nombre=trim($_POST['full_name'] ?? '');
  if($nombre==='') $nombre=user()['full_name'];
  $pdo->prepare('UPDATE usuarios SET nombre_completo=? WHERE id=?')->execute([$nombre,user()['id']]);
  if(!empty($_POST['password'])){
    if(validate_password_rules($_POST['password'])){
      $pdo->prepare('UPDATE usuarios SET contrasena_hash=? WHERE id=?')->execute([password_hash($_POST['password'],PASSWORD_DEFAULT),user()['id']]);
    } else {
      $msg='La contraseña no cumple con la seguridad requerida: mínimo 8 caracteres, mayúscula, minúscula, número y símbolo.';
    }
  }
  if(!empty($_FILES['photo']['tmp_name'])){
    $ext=strtolower(pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION));
    if(in_array($ext,['png','jpg','jpeg','webp'])){
      $dir=__DIR__.'/../uploads'; if(!is_dir($dir)) mkdir($dir,0777,true);
      $namef='perfil_'.user()['id'].'_'.time().'.'.$ext;
      if(move_uploaded_file($_FILES['photo']['tmp_name'],$dir.'/'.$namef)){
        $pdo->prepare('UPDATE usuarios SET foto_perfil=? WHERE id=?')->execute(['sistema/uploads/'.$namef,user()['id']]);
      }
    } else { $msg='La foto debe ser PNG, JPG, JPEG o WEBP.'; }
  }
  $st=$pdo->prepare('SELECT id, nombre_usuario AS username, correo AS email, nombre_completo AS full_name, contrasena_hash AS password_hash, rol AS role, codigo_programa AS program_code, foto_perfil AS profile_photo, correo_verificado_en AS email_verified_at, token_verificacion_correo AS email_verification_token, activo AS active, creado_en AS created_at FROM usuarios WHERE id=?');
  $st->execute([user()['id']]); $_SESSION['user']=$st->fetch(PDO::FETCH_ASSOC);
  if(!$msg) $msg='Perfil actualizado correctamente.';
}
header_admin('Mi perfil'); $u=user(); $photo=!empty($u['profile_photo'])?'../../'.e($u['profile_photo']):''; ?>
<div class="row g-4 align-items-stretch">
  <div class="col-xl-4 col-lg-5">
    <div class="main-card card p-4 text-center profile-card-pro h-100">
      <div class="profile-photo-preview mx-auto mb-3">
        <?php if($photo): ?><img src="<?=$photo?>" alt="Foto de perfil"><?php else: ?><i class="bi bi-person-fill"></i><?php endif; ?>
      </div>
      <h3 class="fw-black mb-1"><?=e($u['full_name'])?></h3>
      <p class="text-muted fw-bold mb-2">Usuario: <?=e($u['username'])?></p>
      <span class="badge rounded-pill text-bg-primary px-3 py-2"><?=e($u['program_code'])?></span>
      <div class="help-box text-start mt-4">
        <b>Foto visible en el dashboard:</b><br>
        La imagen que subas aquí aparecerá en la esquina superior, en el menú lateral y en el panel de inicio.
      </div>
    </div>
  </div>
  <div class="col-xl-7 col-lg-7">
    <div class="main-card card p-4 h-100">
      <?php if($msg): ?><div class="alert alert-info rounded-4"><?=e($msg)?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <label class="form-label fw-bold">Nombre</label>
        <input name="full_name" class="form-control rounded-4 mb-3" value="<?=e($u['full_name'])?>">
        <label class="form-label fw-bold">Nueva contraseña</label>
        <input name="password" type="password" class="form-control rounded-4 mb-2" placeholder="Déjala vacía si no cambiarás">
        <div class="password-rules mb-3">Debe tener mínimo 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.</div>
        <label class="form-label fw-bold">Foto de perfil</label>
        <input name="photo" type="file" accept="image/*" class="form-control rounded-4 mb-3">
        <button class="btn btn-primary btn-rounded px-4"><i class="bi bi-save"></i> Guardar perfil</button>
      </form>
    </div>
  </div>
</div>
<?php footer_admin(); ?>