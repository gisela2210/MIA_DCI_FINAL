<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../config/db.php';

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
header('Location: login.php?msg='.urlencode('Sesión cerrada. Inicia sesión con otro usuario.'));
exit;
