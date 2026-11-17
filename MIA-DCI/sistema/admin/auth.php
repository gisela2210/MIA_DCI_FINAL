<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__.'/../config/db.php';

function cerrar_sesion_local($motivo=''){
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
  $q = $motivo ? ('?msg='.urlencode($motivo)) : '';
  header('Location: login.php'.$q);
  exit;
}

function require_login(){
  global $pdo;
  if (empty($_SESSION['user']) || empty($_SESSION['session_token'])) {
    header('Location: login.php');
    exit;
  }

  $userId = (int)($_SESSION['user']['id'] ?? 0);
  $token = (string)($_SESSION['session_token'] ?? '');
  if ($userId <= 0 || $token === '') {
    cerrar_sesion_local('Sesión inválida. Inicia sesión nuevamente.');
  }

  try {
    $st = $pdo->prepare('SELECT id, nombre_usuario AS username, correo AS email, nombre_completo AS full_name, contrasena_hash AS password_hash, rol AS role, codigo_programa AS program_code, foto_perfil AS profile_photo, correo_verificado_en AS email_verified_at, token_sesion_actual AS session_token, activo AS active FROM usuarios WHERE id=? AND activo=1 LIMIT 1');
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
      cerrar_sesion_local('Tu usuario fue desactivado.');
    }
    if (!hash_equals((string)($row['session_token'] ?? ''), $token)) {
      cerrar_sesion_local('Tu sesión se cerró porque tu usuario inició sesión en otro equipo o navegador.');
    }

    // Refresca datos visibles del usuario sin tocar el token.
    unset($row['password_hash'], $row['session_token']);
    $_SESSION['user'] = $row;
    $pdo->prepare('UPDATE usuarios SET ultima_actividad_sesion_en=NOW() WHERE id=?')->execute([$userId]);
  } catch (Throwable $e) {
    cerrar_sesion_local('Error validando sesión. Importa nuevamente database.sql.');
  }
}

function user(){ return $_SESSION['user'] ?? null; }
function is_admin(){ return (user()['role'] ?? '')==='admin'; }
function can_program($code){ $u=user(); return $u && ($u['role']==='admin' || $u['program_code']===$code || $u['program_code']==='AMBOS'); }
function validate_password_rules($p){ return strlen($p)>=8 && preg_match('/[A-Z]/',$p) && preg_match('/[a-z]/',$p) && preg_match('/[0-9]/',$p) && preg_match('/[^A-Za-z0-9]/',$p); }
?>
