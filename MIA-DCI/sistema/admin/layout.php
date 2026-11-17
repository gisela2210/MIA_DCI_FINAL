<?php require_once __DIR__.'/auth.php'; require_login(); require_once __DIR__.'/notifier.php';
function nav_active($file){ return basename($_SERVER['PHP_SELF'])===$file ? ' active' : ''; }
function admin_setting($k,$d=''){ return function_exists('get_setting_value') ? get_setting_value($k,$d) : $d; }
function unread_notificaciones(){ global $pdo; try{ $st=$pdo->prepare('SELECT COUNT(*) FROM notificaciones WHERE usuario_id=? AND leida=0'); $st->execute([user()['id']]); return (int)$st->fetchColumn(); }catch(Exception $e){ return 0; } }
function recent_notificaciones(){ global $pdo; try{ $st=$pdo->prepare('SELECT id, titulo AS title, mensaje AS message, pagina_id AS page_id, leida AS is_read, creado_en AS created_at FROM notificaciones WHERE usuario_id=? ORDER BY id DESC LIMIT 6'); $st->execute([user()['id']]); return $st->fetchAll(PDO::FETCH_ASSOC); }catch(Exception $e){ return []; } }
function header_admin($title='Panel'){ $u=user(); $photo=!empty($u['profile_photo'])?'../../'.e($u['profile_photo']):''; $font=admin_setting('fuente_global_panel', admin_setting('fuente_global','Noto Sans')); $fontCss=str_replace(' ','+',$font); $unread=unread_notificaciones(); $notifs=recent_notificaciones(); ?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?></title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=<?=e($fontCss)?>:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"><link rel="stylesheet" href="admin.css"><style>:root{--admin-font:'<?=e($font)?>',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}*{font-family:var(--admin-font)!important}</style></head><body><div class="app-shell"><aside class="sidebar"><div class="brand-box"><img src="../../panel_logo.webp" alt="TecNM" class="brand-logo"><div class="brand-small">Campus Veracruz</div></div><nav class="side-nav"><a class="navlink<?=nav_active('index.php')?>" href="index.php"><i class="bi bi-house-door-fill"></i><span>Inicio</span></a><a class="navlink<?=nav_active('pages.php')?><?=nav_active('preview.php')?><?=nav_active('versions.php')?>" href="pages.php"><i class="bi bi-window-stack"></i><span>Páginas DCI/MIA</span></a><a class="navlink<?=nav_active('menu_sections.php')?>" href="menu_sections.php"><i class="bi bi-menu-button-wide-fill"></i><span>Secciones del menú</span></a><a class="navlink<?=nav_active('conexion_frontend.php')?>" href="conexion_frontend.php"><i class="bi bi-link-45deg"></i><span>Conexión Frontend</span></a><a class="navlink<?=nav_active('images.php')?>" href="images.php"><i class="bi bi-image-fill"></i><span>Imágenes</span></a><a class="navlink<?=nav_active('profile.php')?>" href="profile.php"><i class="bi bi-person-badge-fill"></i><span>Mi perfil</span></a><a class="navlink" href="cambiar_usuario.php"><i class="bi bi-arrow-left-right"></i><span>Cambiar usuario</span></a><?php if(is_admin()): ?><a class="navlink<?=nav_active('users.php')?>" href="users.php"><i class="bi bi-people-fill"></i><span>Usuarios</span></a><a class="navlink<?=nav_active('settings.php')?>" href="settings.php"><i class="bi bi-gear-fill"></i><span>Configuración</span></a><?php endif; ?><a class="navlink<?=nav_active('notifications.php')?>" href="notifications.php"><i class="bi bi-bell-fill"></i><span>Notificaciones</span><?php if($unread): ?><span class="side-badge"><?=$unread?></span><?php endif; ?></a><a class="navlink" href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Salir</span></a></nav><div class="sidebar-user"><div class="avatar"><?php if($photo): ?><img src="<?=$photo?>" alt="perfil"><?php else: ?><i class="bi bi-person-fill"></i><?php endif; ?></div><div><div class="fw-bold"><?=e($u['full_name'])?></div><small><?=e($u['username'])?> · <?=e($u['role'])?></small></div></div></aside><main class="main-area"><header class="topbar"><div><h1 class="page-heading"><?=e($title)?></h1><p class="page-subtitle">Gestión de contenido institucional DCI y MIA</p></div><div class="top-actions"><div class="dropdown"><button class="notif position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-bell"></i><?php if($unread): ?><span class="notif-count"><?=$unread?></span><?php endif; ?></button><div class="dropdown-menu dropdown-menu-end notif-menu p-2"><div class="fw-bold px-2 py-1 text-primary">Notificaciones</div><?php if(!$notifs): ?><div class="text-muted small px-2 py-2">No hay notificaciones.</div><?php else: foreach($notifs as $n): ?><a class="dropdown-item notif-item <?=$n['is_read']?'':'unread'?>" href="notifications.php"><b><?=e($n['title'])?></b><br><small><?=e($n['message'])?></small><br><span><?=e($n['created_at'])?></span></a><?php endforeach; endif; ?><div class="border-top mt-1 pt-1"><a class="dropdown-item text-center fw-bold" href="notifications.php">Ver todas</a></div></div></div><a class="top-user top-user-link" href="profile.php" title="Ver mi perfil"><div class="top-avatar"><?php if($photo): ?><img src="<?=$photo?>" alt="perfil"><?php else: ?><i class="bi bi-person-fill"></i><?php endif; ?></div><span class="fw-bold"><?=e($u['full_name'])?></span><span class="badge rounded-pill text-bg-primary"><?=e($u['program_code'])?></span></a></div></header><div class="content-wrap"><?php }
function footer_admin(){ $u=user(); $sessionKey='mia_dci_panel_'.($u['id'] ?? '0').'_'.substr(hash('sha256', $_SESSION['session_token'] ?? ''),0,16); ?></div></main></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const sessionKey = <?=json_encode($sessionKey)?>;
  const duplicateUrl = 'duplicate_session.php';
  const tabKey = 'mia_dci_admin_tab_id';
  const channelName = 'mia_dci_admin_single_tab_' + sessionKey;
  const tabId = sessionStorage.getItem(tabKey) || (Date.now().toString(36) + '_' + Math.random().toString(36).slice(2));
  sessionStorage.setItem(tabKey, tabId);
  let duplicateDetected = false;
  function goDuplicate(){
    if(duplicateDetected) return;
    duplicateDetected = true;
    window.location.replace(duplicateUrl);
  }
  try{
    if('BroadcastChannel' in window){
      const channel = new BroadcastChannel(channelName);
      channel.onmessage = function(ev){
        const data = ev.data || {};
        if(data.tabId === tabId) return;
        if(data.type === 'admin-tab-check'){
          channel.postMessage({type:'admin-tab-present', tabId:tabId});
        }
        if(data.type === 'admin-tab-present'){
          goDuplicate();
        }
      };
      setTimeout(function(){ channel.postMessage({type:'admin-tab-check', tabId:tabId}); }, 120);
      window.addEventListener('beforeunload', function(){ try{ channel.close(); }catch(e){} });
      return;
    }
  }catch(e){}

  // Respaldo para navegadores sin BroadcastChannel.
  const lockKey = channelName + '_lock';
  const now = Date.now();
  const raw = localStorage.getItem(lockKey);
  if(raw){
    try{
      const lock = JSON.parse(raw);
      if(lock.tabId && lock.tabId !== tabId && now - Number(lock.time || 0) < 6000){
        goDuplicate();
        return;
      }
    }catch(e){}
  }
  function refreshLock(){
    try{ localStorage.setItem(lockKey, JSON.stringify({tabId:tabId,time:Date.now()})); }catch(e){}
  }
  refreshLock();
  setInterval(refreshLock, 2500);
  window.addEventListener('storage', function(e){
    if(e.key !== lockKey || !e.newValue) return;
    try{
      const lock = JSON.parse(e.newValue);
      if(lock.tabId && lock.tabId !== tabId && Date.now() - Number(lock.time || 0) < 6000){ goDuplicate(); }
    }catch(err){}
  });
})();
</script></body></html><?php }
?>
