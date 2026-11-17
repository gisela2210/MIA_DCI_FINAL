<?php require_once 'layout.php'; header_admin('Páginas DCI/MIA');
$sql="SELECT p.id, p.programa_id AS program_id, p.titulo AS title, p.ruta_archivo AS file_path, p.orden, p.visible, p.actualizado_en AS updated_at, pr.codigo AS code, pr.nombre AS program, COALESCE(ps.vistas,0) views, COALESCE(ps.ediciones,0) edits FROM paginas p JOIN programas pr ON p.programa_id=pr.id LEFT JOIN estadisticas_pagina ps ON ps.pagina_id=p.id";
$params=[]; if(!is_admin()){ $sql.=' WHERE pr.codigo=?'; $params[]=user()['program_code']; } $sql.=' ORDER BY pr.codigo,p.orden,p.titulo'; $st=$pdo->prepare($sql); $st->execute($params); $rows=$st->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="table-panel">
  <div class="table-toolbar">
    <div>
      <h5 class="card-title-tecnm mb-1"><i class="bi bi-window-sidebar"></i> Páginas originales disponibles</h5>
      <div class="text-muted fw-semibold">Selecciona DCI o MIA. Las páginas públicas se mantienen tal como venían en el ZIP original.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <span class="badge text-bg-primary rounded-pill px-3 py-2"><i class="bi bi-type"></i> Noto Sans</span>
      <a href="menu_sections.php" class="btn btn-tecnm btn-rounded"><i class="bi bi-plus-circle"></i> Agregar sección al menú</a><a href="repair_headers.php" class="btn btn-outline-danger btn-rounded" onclick="return confirm('Se limpiarán encabezados duplicados de las páginas. ¿Continuar?')"><i class="bi bi-tools"></i> Reparar encabezados</a>
      <a href="images.php" class="btn btn-outline-primary btn-rounded"><i class="bi bi-image"></i> Imágenes</a>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-modern align-middle">
      <thead><tr><th>Programa</th><th>Página</th><th>Archivo original</th><th>Vistas</th><th>Ediciones</th><th class="text-center">Acciones</th></tr></thead>
      <tbody><?php foreach($rows as $r): ?>
        <tr>
          <td><span class="program-pill"><i class="bi bi-mortarboard"></i> <?=e($r['code'])?></span></td>
          <td><div class="page-title-cell"><?=e($r['title'])?></div><div class="page-sub"><i class="bi bi-shield-check"></i> Diseño original conservado</div></td>
          <td><span class="file-code"><?=e($r['file_path'])?></span></td>
          <td><span class="badge-soft"><i class="bi bi-eye"></i> <?=e($r['views'])?></span></td>
          <td><span class="badge text-bg-success rounded-pill px-3 py-2"><i class="bi bi-pencil-square"></i> <?=e($r['edits'])?></span></td>
          <td><div class="action-group justify-content-center"><a class="btn btn-tecnm" href="preview.php?id=<?=$r['id']?>"><i class="bi bi-eye-fill"></i> Vista previa editable</a><a class="btn btn-outline-secondary" target="_blank" href="view_page.php?id=<?=$r['id']?>"><i class="bi bi-box-arrow-up-right"></i> Ver</a><a class="btn btn-outline-primary" href="versions.php?id=<?=$r['id']?>"><i class="bi bi-clock-history"></i> Versiones</a></div></td>
        </tr>
      <?php endforeach; ?></tbody>
    </table>
  </div>
</div>
<?php footer_admin(); ?>
