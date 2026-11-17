<?php require_once 'layout.php';
try{ $pdo->prepare('UPDATE notificaciones SET leida=1 WHERE usuario_id=?')->execute([user()['id']]); }catch(Exception $e){}
try{ $st=$pdo->prepare('SELECT n.id, n.titulo AS title, n.mensaje AS message, n.pagina_id AS page_id, n.leida AS is_read, n.creado_en AS created_at, p.titulo AS title_page FROM notificaciones n LEFT JOIN paginas p ON n.pagina_id=p.id WHERE n.usuario_id=? ORDER BY n.id DESC LIMIT 100'); $st->execute([user()['id']]); $rows=$st->fetchAll(PDO::FETCH_ASSOC); }catch(Exception $e){ $rows=[]; }
header_admin('Notificaciones'); ?>
<div class="main-card card p-4">
  <h4 class="card-title-tecnm mb-3"><i class="bi bi-bell-fill"></i> Notificaciones del sistema</h4>
  <?php if(!$rows): ?><div class="alert alert-info rounded-4">No tienes notificaciones.</div><?php else: ?>
  <div class="list-group list-group-flush">
  <?php foreach($rows as $n): ?>
    <div class="list-group-item px-0 py-3"><div class="d-flex justify-content-between gap-3"><div><h6 class="fw-bold mb-1"><?=e($n['title'])?></h6><div><?=e($n['message'])?></div><small class="text-muted"><?=e($n['title'] ?? '')?></small></div><small class="text-muted"><?=e($n['created_at'])?></small></div></div>
  <?php endforeach; ?></div><?php endif; ?>
</div>
<?php footer_admin(); ?>
