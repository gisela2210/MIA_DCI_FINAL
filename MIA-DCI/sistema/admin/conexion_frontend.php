<?php require_once 'layout.php'; header_admin('Conexión Frontend');
$paginas=[]; $errores=[];
try{
  $st=$pdo->query('SELECT p.id,p.titulo,p.ruta_archivo,pr.codigo FROM paginas p JOIN programas pr ON p.programa_id=pr.id ORDER BY pr.codigo,p.orden');
  $paginas=$st->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){ $errores[]=$e->getMessage(); }
?>
<div class="main-card card p-4 mb-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
      <h4 class="card-title-tecnm mb-1"><i class="bi bi-link-45deg"></i> Conexión directa con FRONTEND-MIA y FRONTEND-DCI</h4>
      <p class="text-muted mb-0">El dashboard guarda directamente sobre los archivos reales de estas carpetas. Si un archivo no aparece como escribible, XAMPP no podrá guardar los cambios.</p>
    </div>
    <a href="pages.php" class="btn btn-tecnm btn-rounded"><i class="bi bi-pencil-square"></i> Editar páginas</a>
  </div>
</div>
<?php if($errores): ?><div class="alert alert-danger"><?=e(implode(' | ',$errores))?></div><?php endif; ?>
<div class="main-card card p-3">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Programa</th><th>Página</th><th>Archivo conectado</th><th>Existe</th><th>Permiso de escritura</th><th>Abrir</th></tr></thead>
      <tbody>
      <?php foreach($paginas as $p): $full=safe_page_path($p['ruta_archivo']); $ex=$full && file_exists($full); $wr=$ex && is_writable($full); ?>
        <tr>
          <td><span class="program-pill"><?=e($p['codigo'])?></span></td>
          <td class="fw-bold"><?=e($p['titulo'])?></td>
          <td><code><?=e($p['ruta_archivo'])?></code></td>
          <td><?= $ex?'<span class="badge text-bg-success">Sí</span>':'<span class="badge text-bg-danger">No</span>' ?></td>
          <td><?= $wr?'<span class="badge text-bg-success">Puede guardar</span>':'<span class="badge text-bg-warning">Sin permiso</span>' ?></td>
          <td><?php if($ex): ?><a class="btn btn-sm btn-outline-primary" target="_blank" href="../../<?=e($p['ruta_archivo'])?>"><i class="bi bi-box-arrow-up-right"></i></a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="alert alert-info mt-3">
  <b>Nota:</b> Si guardas un cambio y no lo ves en la página pública, actualiza con <b>Ctrl + F5</b>. También revisa que el archivo aparezca con permiso <b>Puede guardar</b>.
</div>
<?php footer_admin(); ?>
