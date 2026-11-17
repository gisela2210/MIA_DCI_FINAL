<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__.'/../config/db.php';
$error='';
$msg=trim($_GET['msg']??'');

function limpiar_sesion_actual_para_cambio($pdo){
  if(!empty($_SESSION['user']['id']) && !empty($_SESSION['session_token'])){
    try{
      $st=$pdo->prepare('UPDATE usuarios SET token_sesion_actual=NULL, id_sesion_actual=NULL WHERE id=? AND token_sesion_actual=?');
      $st->execute([$_SESSION['user']['id'], $_SESSION['session_token']]);
    }catch(Throwable $e){}
  }
  $_SESSION=[];
  if (ini_get('session.use_cookies')) {
    $params=session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}

// Permite entrar al login para usar OTRO usuario desde el mismo navegador.
// Esto NO permite duplicar sesión del mismo usuario; solo limpia la sesión local actual.
if(isset($_GET['cambiar_usuario'])){
  limpiar_sesion_actual_para_cambio($pdo);
  header('Location: login.php?msg='.urlencode('Sesión anterior cerrada. Ahora puedes iniciar con otro usuario.'));
  exit;
}

$usuarioActivo = $_SESSION['user'] ?? null;

if($_SERVER['REQUEST_METHOD']==='POST'){
  // Si había una sesión local abierta y el usuario manda un login nuevo, se reemplaza de forma segura.
  if(!empty($_SESSION['user'])){
    limpiar_sesion_actual_para_cambio($pdo);
    if (session_status() === PHP_SESSION_NONE) session_start();
  }

  $u=trim($_POST['username']??'');
  $p=$_POST['password']??'';
  $st=$pdo->prepare('SELECT id, nombre_usuario AS username, correo AS email, nombre_completo AS full_name, contrasena_hash AS password_hash, rol AS role, codigo_programa AS program_code, foto_perfil AS profile_photo, correo_verificado_en AS email_verified_at, token_verificacion_correo AS email_verification_token, activo AS active, token_sesion_actual AS session_token FROM usuarios WHERE nombre_usuario=? AND activo=1 LIMIT 1');
  $st->execute([$u]);
  $row=$st->fetch(PDO::FETCH_ASSOC);

  if($row && password_verify($p,$row['password_hash'])){
    if(empty($row['email_verified_at'])) {
      $error='Tu correo aún no está verificado. Solicita al administrador reenviar la verificación.';
    } else {
      session_regenerate_id(true);
      $token = bin2hex(random_bytes(32));
      $pdo->prepare('UPDATE usuarios SET token_sesion_actual=?, id_sesion_actual=?, ultimo_inicio_sesion_en=NOW(), ultima_actividad_sesion_en=NOW() WHERE id=?')
          ->execute([$token, session_id(), $row['id']]);

      unset($row['password_hash'], $row['session_token']);
      $_SESSION['user']=$row;
      $_SESSION['session_token']=$token;
      header('Location: index.php');
      exit;
    }
  } else {
    $error='Usuario o contraseña incorrectos.';
  }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login CMS TecNM</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"><link rel="stylesheet" href="admin.css"></head><body class="login-bg"><div class="container"><div class="row justify-content-center"><div class="col-md-5 col-lg-4"><div class="glass p-4 p-lg-5"><div class="text-center mb-4"><img src="../../panel_logo.webp" style="max-width:230px" class="mb-3" alt="TecNM"><div class="badge text-bg-primary rounded-pill mb-3 px-3 py-2">TecNM Veracruz</div><h3 class="fw-bold text-primary">Panel Administrativo</h3><p class="text-muted mb-0">DCI y MIA</p></div><?php if($msg): ?><div class="alert alert-warning rounded-4"><?=e($msg)?></div><?php endif; ?><?php if($error): ?><div class="alert alert-danger rounded-4"><?=e($error)?></div><?php endif; ?><?php if($usuarioActivo): ?><div class="alert alert-info rounded-4"><div class="fw-bold mb-1">Ya hay una sesión abierta</div><div>Usuario actual: <b><?=e($usuarioActivo['username'] ?? '')?></b></div><div class="small mt-1">Para entrar con otro usuario, primero cierra esta sesión local.</div><a class="btn btn-outline-primary rounded-4 w-100 fw-bold mt-3" href="login.php?cambiar_usuario=1"><i class="bi bi-arrow-left-right"></i> Cambiar de usuario</a><a class="btn btn-primary rounded-4 w-100 fw-bold mt-2" href="index.php"><i class="bi bi-speedometer2"></i> Continuar al panel</a></div><?php else: ?><form method="post"><label class="form-label fw-semibold">Nombre de usuario</label><div class="input-group input-group-lg mb-3"><span class="input-group-text rounded-start-4"><i class="bi bi-person"></i></span><input class="form-control rounded-end-4" name="username" required autofocus autocomplete="username"></div><label class="form-label fw-semibold">Contraseña</label><div class="input-group input-group-lg mb-4"><span class="input-group-text rounded-start-4"><i class="bi bi-lock"></i></span><input class="form-control rounded-end-4" type="password" name="password" required autocomplete="current-password"></div><button class="btn btn-primary btn-lg w-100 rounded-4 fw-bold">Entrar</button></form><?php endif; ?><div class="small text-muted mt-4 text-center">Seguridad: no se permite duplicar sesión del mismo usuario. Puedes cambiar a otro usuario cerrando la sesión actual.</div></div></div></div></div></body></html>
