<?php
require_once __DIR__.'/../config/db.php';

function get_setting_value($key, $default=''){
    global $pdo;
    try{
        $st=$pdo->prepare('SELECT valor_configuracion FROM configuracion_sitio WHERE clave_configuracion=?');
        $st->execute([$key]);
        $v=$st->fetchColumn();
        return $v!==false ? $v : $default;
    }catch(Exception $e){ return $default; }
}

function set_setting_value($key, $value){
    global $pdo;
    try{
        $st=$pdo->prepare('REPLACE INTO configuracion_sitio(clave_configuracion,valor_configuracion) VALUES(?,?)');
        $st->execute([$key,(string)$value]);
    }catch(Exception $e){ }
}

function log_activity($type,$description,$page_id=null,$user_id=null){
    global $pdo;
    try{
        $st=$pdo->prepare('INSERT INTO bitacora_actividad(tipo,descripcion,pagina_id,usuario_id,creado_en) VALUES(?,?,?,?,NOW())');
        $st->execute([$type,$description,$page_id,$user_id]);
    }catch(Exception $e){ }
}

function create_notification($user_id,$title,$message,$page_id=null){
    global $pdo;
    try{
        $st=$pdo->prepare('INSERT INTO notificaciones(usuario_id,titulo,mensaje,pagina_id,leida,creado_en) VALUES(?,?,?,?,0,NOW())');
        $st->execute([$user_id,$title,$message,$page_id]);
    }catch(Exception $e){ }
}

function notify_usuarios($title,$message,$page_id=null,$exclude_user_id=null){
    global $pdo;
    try{
        $st=$pdo->query("SELECT id FROM usuarios WHERE activo=1");
        foreach($st->fetchAll(PDO::FETCH_COLUMN) as $uid){
            if($exclude_user_id && (int)$uid===(int)$exclude_user_id) continue;
            create_notification($uid,$title,$message,$page_id);
        }
    }catch(Exception $e){ }
}

function mail_log($to,$subject,$body,$sent,$error='',$user_id=null){
    global $pdo;
    try{
        $st=$pdo->prepare('INSERT INTO notificaciones_correo(usuario_id,correo_destino,asunto,mensaje,enviado,error_envio,creado_en) VALUES(?,?,?,?,?,?,NOW())');
        $st->execute([$user_id,$to,$subject,$body,$sent?1:0,$error]);
    }catch(Exception $e){ }
    set_setting_value('ultimo_error_correo', $sent ? '' : $error);
    set_setting_value('ultimo_estado_correo', $sent ? 'Correo enviado correctamente.' : 'Error al enviar: '.$error);
}

function smtp_read($fp){
    $data='';
    while(!feof($fp) && ($line=fgets($fp, 515))!==false){
        $data.=$line;
        if(isset($line[3]) && $line[3]==' ') break;
    }
    $meta=stream_get_meta_data($fp);
    if(!empty($meta['timed_out'])) throw new Exception('Tiempo de espera agotado esperando respuesta del servidor SMTP.');
    if(trim($data)==='') throw new Exception('El servidor SMTP no respondió.');
    return $data;
}

function smtp_cmd($fp,$cmd,$expect){
    if($cmd!==null) fwrite($fp,$cmd."\r\n");
    $resp=smtp_read($fp);
    $code=(int)substr($resp,0,3);
    if(!in_array($code,(array)$expect,true)) throw new Exception(trim($resp));
    return $resp;
}

function smtp_dot_stuff($message){
    $message=str_replace(["\r\n","\r"],"\n",$message);
    $lines=explode("\n",$message);
    foreach($lines as &$line){
        if(isset($line[0]) && $line[0]==='.') $line='.'. $line;
    }
    return implode("\r\n",$lines);
}

function smtp_tls_method(){
    if(defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) return STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
    if(defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) return STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
    return STREAM_CRYPTO_METHOD_TLS_CLIENT;
}

function cms_send_mail($to,$subject,$body,$user_id=null){
    $driver=get_setting_value('modo_envio_correo','mail');
    $from=trim(get_setting_value('correo_remitente','no-reply@localhost'));
    $fromName=trim(get_setting_value('nombre_remitente','Sistema TecNM Posgrados'));
    $subjectFull=$subject;
    $sent=false; $error='';

    if(!filter_var($to,FILTER_VALIDATE_EMAIL)){
        mail_log($to,$subjectFull,$body,false,'Correo destino inválido',$user_id);
        return false;
    }
    if(!filter_var($from,FILTER_VALIDATE_EMAIL)){
        mail_log($to,$subjectFull,$body,false,'El correo remitente no es válido. Usa un correo real, por ejemplo usuario@tudominio.com.',$user_id);
        return false;
    }

    try{
        if($driver==='smtp'){
            $host=trim(get_setting_value('smtp_host',''));
            $port=(int)get_setting_value('smtp_puerto','587');
            $secure=get_setting_value('smtp_seguridad','tls');
            $smtpUser=trim(get_setting_value('smtp_usuario',''));
            $smtpPass=get_setting_value('smtp_contrasena','');

            if($host==='' || stripos($host,'dominio.com')!==false) throw new Exception('Servidor SMTP inválido o de ejemplo. Debes poner el servidor real de tu correo.');
            if($port<=0) throw new Exception('Puerto SMTP inválido. Usa 587 para TLS/STARTTLS o 465 para SSL.');
            if($smtpUser==='' || $smtpPass==='') throw new Exception('Usuario o contraseña SMTP vacíos. El usuario normalmente es el correo completo.');

            $remote=($secure==='ssl'?'ssl://':'').$host;
            $fp=@fsockopen($remote,$port,$errno,$errstr,20);
            if(!$fp) throw new Exception("No se pudo conectar al SMTP $host:$port. Detalle: $errstr ($errno)");
            stream_set_timeout($fp,20);

            smtp_cmd($fp,null,220);
            smtp_cmd($fp,'EHLO '.($_SERVER['SERVER_NAME'] ?? 'localhost'),250);
            if($secure==='tls'){
                smtp_cmd($fp,'STARTTLS',220);
                if(!@stream_socket_enable_crypto($fp,true,smtp_tls_method())) throw new Exception('No se pudo activar STARTTLS. Revisa puerto/seguridad o que openssl esté activo en PHP.');
                smtp_cmd($fp,'EHLO '.($_SERVER['SERVER_NAME'] ?? 'localhost'),250);
            }
            if($secure==='none'){
                // Continúa sin TLS solo si el servidor lo permite.
            }

            smtp_cmd($fp,'AUTH LOGIN',334);
            smtp_cmd($fp,base64_encode($smtpUser),334);
            smtp_cmd($fp,base64_encode($smtpPass),235);

            smtp_cmd($fp,'MAIL FROM:<'.$from.'>',250);
            smtp_cmd($fp,'RCPT TO:<'.$to.'>',[250,251]);
            smtp_cmd($fp,'DATA',354);

            $encodedSubject='=?UTF-8?B?'.base64_encode($subjectFull).'?=';
            $encodedFromName='=?UTF-8?B?'.base64_encode($fromName).'?=';
            $headers=[];
            $headers[]='Date: '.date('r');
            $headers[]='From: '.$encodedFromName.' <'.$from.'>';
            $headers[]='Reply-To: '.$from;
            $headers[]='To: <'.$to.'>';
            $headers[]='Subject: '.$encodedSubject;
            $headers[]='MIME-Version: 1.0';
            $headers[]='Content-Type: text/plain; charset=UTF-8';
            $headers[]='Content-Transfer-Encoding: 8bit';
            $headers[]='X-Mailer: TecNM CMS';
            $message=smtp_dot_stuff(implode("\r\n",$headers)."\r\n\r\n".$body)."\r\n.";
            smtp_cmd($fp,$message,250);
            @fwrite($fp,"QUIT\r\n");
            @fclose($fp);
            $sent=true;
        }else{
            $encodedSubject='=?UTF-8?B?'.base64_encode($subjectFull).'?=';
            $headers=[];
            $headers[]='MIME-Version: 1.0';
            $headers[]='Content-type: text/plain; charset=UTF-8';
            $headers[]='From: '.$fromName.' <'.$from.'>';
            $headers[]='Reply-To: '.$from;
            $sent=@mail($to,$encodedSubject,$body,implode("\r\n",$headers));
            if(!$sent) $error='mail() no pudo enviar. En XAMPP normalmente no funciona hasta configurar sendmail/SMTP; usa SMTP real o copia el enlace de verificación manual.';
        }
    }catch(Exception $e){
        $sent=false; $error=$e->getMessage();
    }
    mail_log($to,$subjectFull,$body,$sent,$error,$user_id);
    return $sent;
}

function get_last_mail_error(){
    return get_setting_value('ultimo_error_correo','');
}

function notify_admins($subject,$message){
    global $pdo;
    $enabled=get_setting_value('notificar_cambios','1');
    if($enabled!=='1') return false;
    try{
        $admins=$pdo->query("SELECT id, correo FROM usuarios WHERE rol='admin' AND activo=1 AND correo_verificado_en IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    }catch(Exception $e){ $admins=[]; }
    $sent=false;
    foreach($admins as $a){
        $sent = cms_send_mail($a['correo'],'[TecNM CMS] '.$subject,$message,$a['id']) || $sent;
    }
    return $sent;
}

function send_verification_email($email,$username,$token,$user_id=null){
    $base=get_setting_value('url_base','http://localhost/MIA-DCI/sistema/admin');
    $link=rtrim($base,'/').'/verify_email.php?token='.urlencode($token);
    $body="Hola $username,\n\nSe creó o actualizó tu cuenta para el panel de posgrados TecNM Veracruz.\n\nVerifica tu correo entrando a este enlace:\n$link\n\nSi no solicitaste esta cuenta, ignora este mensaje.\n";
    cms_send_mail($email,'Verificación de correo - Panel TecNM',$body,$user_id);
    return $link;
}
?>
