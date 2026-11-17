<?php require_once 'layout.php';
$id=(int)($_GET['id']??0); $st=$pdo->prepare('SELECT p.id, p.programa_id AS program_id, p.titulo AS title, p.ruta_archivo AS file_path, p.orden, p.visible, p.actualizado_en AS updated_at, pr.codigo AS code, pr.nombre AS program FROM paginas p JOIN programas pr ON p.programa_id=pr.id WHERE p.id=?'); $st->execute([$id]); $p=$st->fetch(PDO::FETCH_ASSOC); if(!$p || !can_program($p['code'])) die('Sin permiso');
$s=$pdo->prepare('SELECT ps.id, ps.pagina_id AS page_id, ps.usuario_id AS user_id, ps.html, ps.nota AS note, ps.creado_en AS created_at, u.nombre_usuario AS username FROM versiones_pagina ps LEFT JOIN usuarios u ON ps.usuario_id=u.id WHERE ps.pagina_id=? ORDER BY ps.id DESC LIMIT 50'); $s->execute([$id]); $versions=$s->fetchAll(PDO::FETCH_ASSOC); header_admin('Versiones de página'); ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div><a href="preview.php?id=<?=$p['id']?>" class="btn btn-light btn-rounded"><i class="bi bi-arrow-left"></i> Regresar a vista previa</a></div>
  <span class="program-pill"><i class="bi bi-mortarboard"></i> <?=e($p['code'])?></span>
</div>
<div class="main-card card p-4 mb-3">
  <h4 class="card-title-tecnm mb-1"><i class="bi bi-clock-history"></i> Regresar versiones</h4>
  <p class="text-muted mb-0">Cada vez que guardas cambios se conserva una versión anterior. Puedes restaurarla sin tocar los encabezados manualmente.</p>
</div>
<?php if(!$versions): ?>
<div class="alert alert-info rounded-4">Aún no hay versiones guardadas para esta página. Guarda un cambio para generar la primera versión.</div>
<?php else: ?>
<div class="row g-3">
<?php foreach($versions as $v): ?>
  <div class="col-lg-6"><div class="version-card h-100">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-2"><div><h5 class="fw-bold mb-1"><?=e($v['note'] ?: 'Versión guardada')?></h5><div class="text-muted small"><i class="bi bi-person"></i> <?=e($v['username'] ?: 'Sistema')?> · <i class="bi bi-calendar"></i> <?=e($v['created_at'])?></div></div><span class="badge text-bg-primary rounded-pill">#<?=e($v['id'])?></span></div>
    <div class="d-flex gap-2 mt-3 flex-wrap">
      <form method="post" action="restore_version.php" onsubmit="return confirm('¿Restaurar esta versión? Se creará un respaldo de la versión actual.');"><input type="hidden" name="snapshot_id" value="<?=$v['id']?>"><button class="btn btn-tecnm btn-rounded"><i class="bi bi-arrow-counterclockwise"></i> Restaurar</button></form>
      <button class="btn btn-outline-secondary btn-rounded" type="button" data-bs-toggle="collapse" data-bs-target="#v<?=$v['id']?>"><i class="bi bi-eye"></i> Ver código</button>
    </div>
    <div class="collapse mt-3" id="v<?=$v['id']?>"><textarea class="form-control" rows="8" readonly><?=e(substr($v['html'],0,5000))?></textarea></div>
  </div></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php footer_admin(); ?>
