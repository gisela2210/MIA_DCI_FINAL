<?php
require_once 'layout.php';
require_once 'notifier.php';
if(!is_admin()) die('Solo admin');
$msg='';
$testResult='';
$testOk=null;
function sv($k,$d=''){
  global $pdo;
  try{$st=$pdo->prepare('SELECT valor_configuracion FROM configuracion_sitio WHERE clave_configuracion=?'); $st->execute([$k]); $v=$st->fetchColumn(); return $v!==false?$v:$d;}catch(Exception $e){return $d;}
}
function save_setting($k,$v){ global $pdo; $st=$pdo->prepare('REPLACE INTO configuracion_sitio(clave_configuracion,valor_configuracion) VALUES(?,?)'); $st->execute([$k,$v]); }
function apply_fuente_publica($font){
  $safe=preg_replace('/[^A-Za-z0-9 ]/','',$font);
  $fontUrl=str_replace(' ','+',$safe);
  $style="<link id=\"cms-global-font-link\" rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n    <link id=\"cms-global-font-style\" href=\"https://fonts.googleapis.com/css2?family={$fontUrl}:wght@400;500;600;700;800;900&display=swap\" rel=\"stylesheet\">\n    <style id=\"cms-global-font\">body, body *{font-family:'{$safe}', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;}</style>";
  foreach(['../../FRONTEND-DCI','../../FRONTEND-MIA'] as $dir){
    $base=realpath(__DIR__.'/'.$dir); if(!$base) continue;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base)) as $f){
      if(!$f->isFile() || !preg_match('/\.html?$/i',$f->getFilename())) continue;
      $path=$f->getPathname(); $html=file_get_contents($path);
      $html=preg_replace('/\s*<link[^>]*id=["\']cms-global-font-link["\'][^>]*>\s*/i','',$html);
      $html=preg_replace('/\s*<link[^>]*id=["\']cms-global-font-style["\'][^>]*>\s*/i','',$html);
      $html=preg_replace('/\s*<style[^>]*id=["\']cms-global-font["\'][\s\S]*?<\/style>\s*/i','',$html);
      $html=preg_replace('/<\/head>/i',"    {$style}\n  </head>",$html,1);
      file_put_contents($path,$html);
    }
  }
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action'] ?? 'save';
  foreach(['url_base','correo_remitente','nombre_remitente','notificar_cambios','modo_envio_correo','smtp_host','smtp_puerto','smtp_seguridad','smtp_usuario','fuente_global_panel','fuente_global_publica'] as $k){
    save_setting($k,trim($_POST[$k]??''));
  }
  if(!empty($_POST['limpiar_contrasena_smtp'])){
    save_setting('smtp_contrasena','');
  } elseif(isset($_POST['smtp_contrasena']) && trim($_POST['smtp_contrasena'])!==''){
    save_setting('smtp_contrasena',trim($_POST['smtp_contrasena']));
  }
  if(!empty($_POST['apply_fuente_publica'])) apply_fuente_publica($_POST['fuente_global_publica'] ?? 'Noto Sans');
  if($action==='test_mail'){
    $to=trim($_POST['correo_prueba'] ?? '');
    if(!filter_var($to,FILTER_VALIDATE_EMAIL)) $testResult='Escribe un correo de prueba válido.';
    else{
      $sent=cms_send_mail($to,'Prueba de correo - Panel TecNM','Este es un correo de prueba del panel DCI/MIA TecNM Veracruz. Si lo recibes, la configuración de envío está funcionando.',null);
      $testOk=$sent;
      if($sent){
        $testResult='Correo de prueba enviado correctamente.';
      }else{
        $detalle=get_last_mail_error();
        $testResult='No se pudo enviar el correo de prueba.'.($detalle?' Detalle: '.$detalle:' Revisa la configuración SMTP o mail() del servidor.');
      }
    }
  }
  $msg='Configuración guardada.';
  log_activity('configuracion','Configuración de fuente y correo actualizada',null,user()['id']);
  notify_usuarios('Configuración actualizada','El administrador actualizó fuentes, correo o notificaciones del sistema.',null,user()['id']);
}
header_admin('Configuración');
?>
<?php if($msg): ?><div class="alert alert-success rounded-4"><?=e($msg)?></div><?php endif; ?>
<?php if($testResult): ?><div class="alert <?= $testOk===false ? 'alert-danger' : 'alert-info' ?> rounded-4"><?=e($testResult)?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="main-card card p-4 mb-3">
      <h5 class="card-title-tecnm mb-3"><i class="bi bi-envelope-check"></i> Configuración de correos</h5>
      <form method="post">
        <input type="hidden" name="action" value="save">
        <label class="form-label fw-bold">URL base del sistema</label>
        <input name="url_base" class="form-control mb-3" value="<?=e(sv('url_base','http://localhost/MIA-DCI/sistema/admin'))?>">
        <div class="mail-config-grid">
          <div><label class="form-label fw-bold">Correo remitente</label><input name="correo_remitente" type="email" class="form-control" value="<?=e(sv('correo_remitente','no-reply@localhost'))?>"></div>
          <div><label class="form-label fw-bold">Nombre del remitente</label><input name="nombre_remitente" class="form-control" value="<?=e(sv('nombre_remitente','Sistema TecNM Posgrados'))?>"></div>
          <div><label class="form-label fw-bold">Notificar cambios por correo</label><select name="notificar_cambios" class="form-select"><option value="1" <?=sv('notificar_cambios','1')==='1'?'selected':''?>>Sí, notificar al administrador</option><option value="0" <?=sv('notificar_cambios','1')==='0'?'selected':''?>>No notificar por correo</option></select></div>
          <div><label class="form-label fw-bold">Modo de envío</label><select name="modo_envio_correo" class="form-select"><option value="mail" <?=sv('modo_envio_correo','mail')==='mail'?'selected':''?>>PHP mail()</option><option value="smtp" <?=sv('modo_envio_correo')==='smtp'?'selected':''?>>SMTP</option></select></div>
        </div>
        <hr>
        <h6 class="fw-bold text-primary"><i class="bi bi-hdd-network"></i> Datos SMTP opcionales</h6>
        <p class="text-muted small mb-2">Úsalos si <b>mail()</b> no envía. Los datos deben ser reales: servidor SMTP de tu correo, usuario completo y contraseña real/app password. Los textos como <b>smtp.dominio.com</b> son solo ejemplo y no envían.</p>
        <div class="mail-config-grid">
          <div><label class="form-label fw-bold">Servidor SMTP</label><input name="smtp_host" class="form-control" placeholder="smtp.tudominio.com" value="<?=e(sv('smtp_host',''))?>"><div class="form-text">No uses el texto de ejemplo; debe ser el servidor real.</div></div>
          <div><label class="form-label fw-bold">Puerto</label><input name="smtp_puerto" type="number" class="form-control" value="<?=e(sv('smtp_puerto','587'))?>"><div class="form-text">587 para TLS/STARTTLS, 465 para SSL.</div></div>
          <div><label class="form-label fw-bold">Seguridad</label><select name="smtp_seguridad" class="form-select"><option value="tls" <?=sv('smtp_seguridad','tls')==='tls'?'selected':''?>>TLS / STARTTLS 587</option><option value="ssl" <?=sv('smtp_seguridad')==='ssl'?'selected':''?>>SSL 465</option><option value="none" <?=sv('smtp_seguridad')==='none'?'selected':''?>>Sin seguridad</option></select></div>
          <div><label class="form-label fw-bold">Usuario SMTP</label><input name="smtp_usuario" class="form-control" placeholder="correo@tudominio.com" value="<?=e(sv('smtp_usuario',''))?>"><div class="form-text">Normalmente es el correo completo, no solo “admin”.</div></div>
          <div class="grid-span"><label class="form-label fw-bold">Contraseña SMTP</label><input name="smtp_contrasena" type="password" class="form-control" placeholder="Dejar vacía para conservar la actual"><div class="form-text">Por seguridad no se muestra la contraseña guardada.</div><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="limpiar_contrasena_smtp" value="1" id="limpiarSmtp"><label class="form-check-label" for="limpiarSmtp">Borrar contraseña SMTP guardada</label></div></div>
          <div><label class="form-label fw-bold">Correo de prueba</label><input name="correo_prueba" type="email" class="form-control" placeholder="tu_correo@dominio.com"></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <button class="btn btn-tecnm btn-rounded">Guardar configuración</button>
          <button name="action" value="test_mail" class="btn btn-outline-primary btn-rounded"><i class="bi bi-send"></i> Guardar y enviar prueba</button>
        </div>
      </form>
    </div>
    <div class="main-card card p-4">
      <h5 class="fw-bold text-primary"><i class="bi bi-type"></i> Fuentes de texto</h5>
      <form method="post">
        <input type="hidden" name="action" value="save">
        <?php foreach(['url_base','correo_remitente','nombre_remitente','notificar_cambios','modo_envio_correo','smtp_host','smtp_puerto','smtp_seguridad','smtp_usuario'] as $hidden): ?><input type="hidden" name="<?=$hidden?>" value="<?=e(sv($hidden,''))?>"><?php endforeach; ?>
        <label class="form-label fw-bold">Fuente global del panel</label><select name="fuente_global_panel" class="form-select mb-3"><option <?=sv('fuente_global_panel','Noto Sans')==='Noto Sans'?'selected':''?>>Noto Sans</option><option <?=sv('fuente_global_panel')==='Montserrat'?'selected':''?>>Montserrat</option><option <?=sv('fuente_global_panel')==='Roboto'?'selected':''?>>Roboto</option><option <?=sv('fuente_global_panel')==='Open Sans'?'selected':''?>>Open Sans</option><option <?=sv('fuente_global_panel')==='Poppins'?'selected':''?>>Poppins</option></select>
        <label class="form-label fw-bold">Fuente global de las páginas públicas DCI/MIA</label><select name="fuente_global_publica" class="form-select mb-2"><option <?=sv('fuente_global_publica','Noto Sans')==='Noto Sans'?'selected':''?>>Noto Sans</option><option <?=sv('fuente_global_publica')==='Montserrat'?'selected':''?>>Montserrat</option><option <?=sv('fuente_global_publica')==='Roboto'?'selected':''?>>Roboto</option><option <?=sv('fuente_global_publica')==='Open Sans'?'selected':''?>>Open Sans</option><option <?=sv('fuente_global_publica')==='Poppins'?'selected':''?>>Poppins</option></select>
        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" value="1" name="apply_fuente_publica" id="applyfont"><label class="form-check-label fw-bold" for="applyfont">Aplicar esta fuente a todas las páginas públicas DCI y MIA</label></div>
        <button class="btn btn-tecnm btn-rounded">Guardar fuentes</button>
      </form>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="help-box mb-3">
      <h5 class="fw-bold"><i class="bi bi-info-circle"></i> Importante</h5>
      <p>En XAMPP, <b>mail()</b> casi siempre falla si no está configurado el servidor de correo. Para pruebas locales usa SMTP real o copia el enlace de verificación manual.</p>
      <p>Para SMTP necesitas: servidor, puerto, seguridad, usuario completo y contraseña. Si uno de esos datos es de ejemplo, no enviará.</p>
      <p class="mb-0">Las notificaciones internas sí aparecen en la campana aunque el correo no esté configurado.</p>
    </div>
    <div class="main-card card p-4 mb-3">
      <h5 class="fw-bold text-primary"><i class="bi bi-bug"></i> Diagnóstico de correo</h5>
      <p class="mb-1"><b>Modo actual:</b> <?=e(sv('modo_envio_correo','mail'))?></p>
      <p class="mb-1"><b>Servidor SMTP:</b> <?=e(sv('smtp_host','No configurado'))?></p>
      <p class="mb-1"><b>Último error:</b></p>
      <div class="alert alert-light border rounded-4 small mb-0"><?=e(sv('ultimo_error_correo','Sin errores registrados todavía.'))?></div>
    </div>
    <div class="font-preview" style="font-family:'<?=e(sv('fuente_global_panel','Noto Sans'))?>' !important"><h3>Vista previa de fuente</h3><p>Este apartado permite cambiar la fuente del panel y aplicar una fuente global a las páginas públicas.</p><button class="btn btn-primary btn-rounded">Ejemplo de botón</button></div>
  </div>
</div>
<?php footer_admin(); ?>
