<?php require_once 'layout.php'; header_admin('Panel de Inicio');
$allowed = is_admin()? '' : ' WHERE pr.codigo='. $pdo->quote(user()['program_code']);
$stats=$pdo->query("SELECT COUNT(*) paginas, COALESCE(SUM(ps.vistas),0) views, COALESCE(SUM(ps.ediciones),0) edits FROM paginas p JOIN programas pr ON p.programa_id=pr.id LEFT JOIN estadisticas_pagina ps ON ps.pagina_id=p.id $allowed")->fetch(PDO::FETCH_ASSOC);
$imgCount=$pdo->query("SELECT COUNT(*) FROM imagenes_pagina im JOIN programas pr ON im.programa_id=pr.id".(is_admin()?"":" WHERE pr.codigo=".$pdo->quote(user()['program_code'])))->fetchColumn();
$userCount=is_admin() ? $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() : 1;
$programas=$pdo->query("SELECT pr.codigo AS code, pr.nombre AS name, COUNT(p.id) total, COALESCE(SUM(ps.vistas),0) views, COALESCE(SUM(ps.ediciones),0) edits FROM programas pr LEFT JOIN paginas p ON p.programa_id=pr.id LEFT JOIN estadisticas_pagina ps ON ps.pagina_id=p.id GROUP BY pr.id ORDER BY pr.codigo")->fetchAll(PDO::FETCH_ASSOC);
try{ $activity=$pdo->query("SELECT al.id, al.tipo AS type, al.descripcion AS description, al.pagina_id AS page_id, al.usuario_id AS user_id, al.creado_en AS created_at, u.nombre_usuario AS username, p.titulo AS title FROM bitacora_actividad al LEFT JOIN usuarios u ON al.usuario_id=u.id LEFT JOIN paginas p ON al.pagina_id=p.id ORDER BY al.id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC); }catch(Exception $e){ $activity=[]; }
?>
<section class="dashboard-hero">
  <div class="hero-content">
    <span class="hero-kicker"><i class="bi bi-shield-check"></i> Tecnológico Nacional de México · Veracruz</span>
    <h2 class="hero-title">Panel de Inicio</h2>
    <div class="hero-sub">Gestión de contenido institucional</div>
    <div class="hero-line"></div>
    <p class="hero-text">Administra DCI y MIA desde una vista previa editable, sin cambiar el diseño público original. Cada edición crea respaldo automático y puede notificarse al administrador por correo.</p>
    <div class="profile-welcome">
      <div class="big-avatar"><?php if(!empty(user()['profile_photo'])): ?><img src="../../<?=e(user()['profile_photo'])?>" alt="Foto de perfil"><?php else: ?><i class="bi bi-person-fill"></i><?php endif; ?></div>
      <div>
        <div class="fw-bold fs-5"><?=e(user()['full_name'])?></div>
        <div class="opacity-75">Usuario: <?=e(user()['username'])?> · <?=e(user()['role'])?> · <?=e(user()['program_code'])?></div>
        <a href="profile.php" class="btn btn-light btn-sm btn-rounded mt-2"><i class="bi bi-person-gear"></i> Editar mi perfil</a>
      </div>
    </div>
  </div>
</section>

<div class="row g-3 dash-cards mb-4">
  <div class="col-md-6 col-xl"><div class="metric-card"><div class="metric-icon"><i class="bi bi-mortarboard-fill"></i></div><div><div class="metric-label">Páginas registradas</div><div class="metric-number"><?=e($stats['paginas'])?></div><a href="pages.php" class="metric-link">Ver páginas <i class="bi bi-arrow-right"></i></a></div></div></div>
  <div class="col-md-6 col-xl"><div class="metric-card"><div class="metric-icon cyan"><i class="bi bi-eye-fill"></i></div><div><div class="metric-label">Visualizaciones</div><div class="metric-number"><?=e($stats['views'])?></div><a href="pages.php" class="metric-link">Ver detalles <i class="bi bi-arrow-right"></i></a></div></div></div>
  <div class="col-md-6 col-xl"><div class="metric-card"><div class="metric-icon green"><i class="bi bi-pencil-square"></i></div><div><div class="metric-label">Ediciones guardadas</div><div class="metric-number"><?=e($stats['edits'])?></div><a href="pages.php" class="metric-link">Editar ahora <i class="bi bi-arrow-right"></i></a></div></div></div>
  <div class="col-md-6 col-xl"><div class="metric-card"><div class="metric-icon purple"><i class="bi bi-images"></i></div><div><div class="metric-label">Imágenes cargadas</div><div class="metric-number"><?=e($imgCount)?></div><a href="images.php" class="metric-link">Ver galería <i class="bi bi-arrow-right"></i></a></div></div></div>
  <div class="col-md-6 col-xl"><div class="metric-card"><div class="metric-icon"><i class="bi bi-people-fill"></i></div><div><div class="metric-label">Usuarios</div><div class="metric-number"><?=e($userCount)?></div><a href="<?=is_admin()?'users.php':'profile.php'?>" class="metric-link"><?=is_admin()?'Ver usuarios':'Mi perfil'?> <i class="bi bi-arrow-right"></i></a></div></div></div>
</div>

<div class="dashboard-grid">
  <section class="feature-panel">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
      <div><h5 class="card-title-tecnm mb-1"><i class="bi bi-bookmark"></i> Contenido destacado</h5><div class="text-muted fw-semibold">Acceso rápido a la edición visual.</div></div>
      <span class="program-pill"><i class="bi bi-type"></i> Noto Sans</span>
    </div>
    <div class="featured-content">
      <img src="../../panel_egresados.png" alt="Campus TecNM">
      <div>
        <div class="text-uppercase fw-black fw-bold text-primary small mb-2">Página principal</div>
        <h4 class="fw-bold">Bienvenidos al TecNM Veracruz</h4>
        <p class="text-muted">Edita contenido, cambia imágenes, agrega tablas y conserva los botones originales como “Ver más” o “Ver perfil”.</p>
        <span class="pill-success"><i class="bi bi-check-circle-fill"></i> Publicado</span>
      </div>
    </div>
    <div class="row g-2 mt-4">
      <div class="col-md-4"><a class="btn btn-outline-primary btn-rounded w-100" href="pages.php"><i class="bi bi-eye"></i> Vista previa</a></div>
      <div class="col-md-4"><a class="btn btn-tecnm btn-rounded w-100" href="pages.php"><i class="bi bi-pencil"></i> Editar</a></div>
      <div class="col-md-4"><a class="btn btn-outline-primary btn-rounded w-100" href="images.php"><i class="bi bi-image"></i> Subir imagen</a></div>
    </div>
  </section>
  <section class="activity-panel">
    <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="card-title-tecnm mb-0"><i class="bi bi-clock-history"></i> Actividad reciente</h5><a href="pages.php" class="metric-link">Ver todo <i class="bi bi-arrow-right"></i></a></div>
    <?php if(!$activity): ?>
      <div class="activity-item"><div class="activity-dot"><i class="bi bi-info"></i></div><div class="flex-grow-1"><div class="activity-desc">Aún no hay actividad registrada</div><div class="activity-meta">Cuando edites páginas o subas imágenes aparecerá aquí</div></div><span class="activity-meta">Ahora</span></div>
    <?php else: foreach($activity as $a): ?>
      <div class="activity-item"><div class="activity-dot"><i class="bi bi-activity"></i></div><div class="flex-grow-1"><div class="activity-desc"><?=e($a['description'])?></div><div class="activity-meta"><?=e($a['title'] ?? $a['type'])?> · <?=e($a['username'] ?? 'Sistema')?></div></div><span class="activity-meta"><?=e($a['created_at'])?></span></div>
    <?php endforeach; endif; ?>
  </section>
</div>

<div class="main-card card p-4 mt-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div><h5 class="card-title-tecnm mb-1">Resumen por programa</h5><div class="text-muted">Estadísticas rápidas de DCI y MIA.</div></div>
  </div>
  <div class="row g-3">
  <?php foreach($programas as $pr): ?>
    <div class="col-md-6"><div class="help-box h-100"><div class="d-flex justify-content-between align-items-start"><div><span class="program-pill mb-2"><i class="bi bi-mortarboard"></i> <?=e($pr['code'])?></span><h6 class="fw-bold mb-1"><?=e($pr['name'])?></h6></div><a class="btn btn-sm btn-primary btn-rounded" href="pages.php">Editar</a></div><div class="mt-2 small"><b><?=e($pr['total'])?></b> páginas · <b><?=e($pr['views'])?></b> vistas · <b><?=e($pr['edits'])?></b> ediciones</div></div></div>
  <?php endforeach; ?>
  </div>
</div>
<?php footer_admin(); ?>
