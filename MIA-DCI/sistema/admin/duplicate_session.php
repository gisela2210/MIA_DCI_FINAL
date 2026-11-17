<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__.'/../config/db.php';
$usuario = $_SESSION['user']['username'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panel ya abierto</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>
<body class="login-bg">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-7 col-lg-5">
        <div class="glass p-4 p-lg-5 text-center">
          <img src="../../panel_logo.webp" style="max-width:210px" class="mb-4" alt="TecNM">
          <div class="display-6 text-primary mb-3"><i class="bi bi-shield-lock-fill"></i></div>
          <h3 class="fw-bold text-primary">El panel ya está abierto</h3>
          <p class="text-muted">Por seguridad, este usuario no puede tener dos pestañas del dashboard abiertas con la misma sesión.</p>
          <?php if($usuario): ?><div class="alert alert-info rounded-4">Sesión actual: <b><?=htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8')?></b></div><?php endif; ?>
          <div class="d-grid gap-2">
            <button class="btn btn-primary rounded-4 fw-bold" onclick="window.close()"><i class="bi bi-x-circle"></i> Cerrar esta pestaña</button>
            <a class="btn btn-outline-primary rounded-4 fw-bold" href="cambiar_usuario.php"><i class="bi bi-arrow-left-right"></i> Cambiar usuario</a>
            <a class="btn btn-light rounded-4 fw-bold" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
          </div>
          <p class="small text-muted mt-4 mb-0">Usa la pestaña que ya estaba abierta para continuar trabajando.</p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
