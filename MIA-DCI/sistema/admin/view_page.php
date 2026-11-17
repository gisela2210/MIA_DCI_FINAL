<?php require_once __DIR__.'/auth.php'; require_once __DIR__.'/notifier.php'; require_login();
$id=(int)($_GET['id']??0); $st=$pdo->prepare('SELECT p.id, p.programa_id AS program_id, p.titulo AS title, p.ruta_archivo AS file_path, p.orden, p.visible, pr.codigo AS code FROM paginas p JOIN programas pr ON p.programa_id=pr.id WHERE p.id=?'); $st->execute([$id]); $p=$st->fetch(PDO::FETCH_ASSOC); if(!$p || !can_program($p['code'])) die('Sin permiso');
$pdo->prepare('UPDATE estadisticas_pagina SET vistas=vistas+1,ultima_vista_en=NOW() WHERE pagina_id=?')->execute([$id]);
$file=safe_page_path($p['file_path']); if(!$file) die('Archivo no permitido');
$html=file_get_contents($file); $base='../../'.dirname($p['file_path']).'/';

function cms_previsualizacion_sin_gobmx($html){
    /*
      En el modo edición NO cargamos gobmx.js.
      Ese script del Gobierno genera encabezado y pie de página en tiempo de ejecución.
      Si se deja activo dentro del iframe, parece que el encabezado se duplica y además puede guardarse por error.
      La página pública original NO se modifica: solo se limpia la previsualización editable.
    */
    $html=preg_replace('/<script\b[^>]*src=["\'][^"\']*gobmx\.js[^"\']*["\'][^>]*>\s*<\/script>/i','',$html);
    $html=preg_replace('/<script\b[^>]*src=["\'][^"\']*framework-gb\.cdn\.gob\.mx[^"\']*["\'][^>]*>\s*<\/script>/i','',$html);
    return $html;
}

// En modo vista previa se carga la página original tal cual.
// Ya no guardamos el DOM completo, así que gobmx.js no puede duplicarse al guardar.
// $html=cms_previsualizacion_sin_gobmx($html);
$html=preg_replace('/<head([^>]*)>/i','<head$1><base href="'.$base.'"><script>window.__CMS_PREVIEW__=true;</script><style id="cms-gobmx-one-style">body > .navbar.navbar-inverse ~ .navbar.navbar-inverse, body > .navbar.navbar-gobmx ~ .navbar.navbar-gobmx, body > .gobmx-header ~ .gobmx-header, body > header.gobmx-header ~ header.gobmx-header, body > .main-footer ~ .main-footer, body > footer.main-footer ~ footer.main-footer, body > .footer-pleca ~ .footer-pleca{display:none!important}</style><style id="cms-preview-fit-style">html.cms-fit-preview{zoom:.82;} @supports not (zoom:1){html.cms-fit-preview body{transform:scale(.82);transform-origin:top left;width:122%;}}</style><script>document.documentElement.classList.add("cms-fit-preview");</script>',$html,1);
$inject=<<<'JS'
<script id="cms-preview-tools">
(function(){
  function cmsMantenerSoloUnGobmx(){
    const grupos=[
      '.navbar.navbar-inverse, .navbar.navbar-gobmx, .gobmx-header, header.gobmx-header',
      '.main-footer, footer.main-footer',
      '.footer-pleca'
    ];
    grupos.forEach(sel=>{
      const nodos=[...document.querySelectorAll(sel)].filter(n=>!n.closest('.site-header,.site-footer'));
      nodos.forEach((n,i)=>{ if(i>0){ n.remove(); } });
    });
  }
  window.addEventListener('load',()=>{ cmsMantenerSoloUnGobmx(); setTimeout(cmsMantenerSoloUnGobmx,400); setTimeout(cmsMantenerSoloUnGobmx,1200); });
  const obs=new MutationObserver(()=>cmsMantenerSoloUnGobmx());
  try{ obs.observe(document.documentElement,{childList:true,subtree:true}); }catch(e){}
  let editMode=false, selected=null;
  const protectedSel='header,footer,.brand-strip,.site-header,.site-footer,.gobmx-header,.navbar,.nav,.site-nav,[role="banner"],script,style,link,meta,base';
  function cssPath(el){let a=[];while(el&&el.nodeType===1&&el.tagName!=="HTML"){let n=el.tagName.toLowerCase();if(el.id){n+="#"+CSS.escape(el.id);a.unshift(n);break;}let i=1,s=el;while(s=s.previousElementSibling){if(s.tagName===el.tagName)i++;}n+=`:nth-of-type(${i})`;a.unshift(n);el=el.parentElement;}return a.join(">");}
  function clean(){document.querySelectorAll('.__cmsSel').forEach(x=>x.classList.remove('__cmsSel'));}
  function selectable(el){ if(!el || el===document.body) return false; if(el.closest(protectedSel)) return false; return true; }
  function select(el,ev){ if(!editMode) return; if(!selectable(el)){ parent.postMessage({type:'protectedArea'},'*'); return; } ev.preventDefault(); ev.stopPropagation(); clean(); selected=el; selected.classList.add('__cmsSel'); if(selected.tagName==='IMG'){ parent.postMessage({type:'selectImage', path:cssPath(selected), src:selected.getAttribute('src')||'', alt:selected.getAttribute('alt')||''},'*'); return; } selected.setAttribute('contenteditable','true'); selected.focus(); parent.postMessage({type:'selectText',text:selected.innerText||selected.textContent||'',html:selected.innerHTML||'',tag:selected.tagName,path:cssPath(selected)},'*'); }
  document.addEventListener('click',function(ev){ if(editMode) select(ev.target,ev); },true);
  document.addEventListener('input',function(){ if(selected && selected.tagName!=='IMG'){ parent.postMessage({type:'liveText',text:selected.innerText||selected.textContent||'',html:selected.innerHTML||'',path:cssPath(selected)},'*'); }});
  window.addEventListener('message',function(ev){ const d=ev.data||{}; if(d.type==='cmsEnableEdit'){ editMode=true; document.body.classList.add('__cmsEditOn'); parent.postMessage({type:'editModeStatus',enabled:true},'*'); }
    if(d.type==='cmsDisableEdit'){ editMode=false; document.body.classList.remove('__cmsEditOn'); document.querySelectorAll('[contenteditable="true"]').forEach(x=>x.removeAttribute('contenteditable')); clean(); parent.postMessage({type:'editModeStatus',enabled:false},'*'); }
    if(d.type==='cmsToggleFit'){ document.documentElement.classList.toggle('cms-fit-preview'); }
    if(d.type==='cmsAddSlideInicio' && d.html){ const vp=document.querySelector('[data-js="announcements-slider"] .announcements-slider__viewport') || document.querySelector('.announcements-slider__viewport'); if(vp){ vp.insertAdjacentHTML('beforeend',d.html); } else { parent.postMessage({type:'protectedArea'},'*'); } }
    if(d.type==='cmsSetText' && d.path){ const el=document.querySelector(d.path); if(el && selectable(el)){ el.innerText=d.text||''; select(el,{preventDefault(){},stopPropagation(){}}); } }
    if(d.type==='cmsInsertTable' && d.path){ const el=document.querySelector(d.path); if(el && selectable(el)){ el.insertAdjacentHTML('afterend',d.html); } }
    if(d.type==='cmsInsertContent' && d.path){ const el=document.querySelector(d.path); if(el && selectable(el)){ if(d.position==='inside') el.insertAdjacentHTML('beforeend',d.html); else el.insertAdjacentHTML('afterend',d.html); } }
    if(d.type==='cmsInsertSection'){
      let el=d.path?document.querySelector(d.path):null;
      if(el && selectable(el)){ el.insertAdjacentHTML('afterend',d.html); }
      else {
        const main=document.querySelector('main') || document.querySelector('.main-content') || document.body;
        const footer=document.querySelector('footer,.site-footer,.main-footer');
        if(footer && footer.parentNode===main) footer.insertAdjacentHTML('beforebegin',d.html); else main.insertAdjacentHTML('beforeend',d.html);
      }
    }
    if(d.type==='cmsReplaceImage' && d.path){ const el=document.querySelector(d.path); if(el && el.tagName==='IMG' && selectable(el)){ el.setAttribute('src',d.src); if(d.alt!==undefined) el.setAttribute('alt',d.alt); select(el,{preventDefault(){},stopPropagation(){}}); } }
    if(d.type==='cmsDeleteElement' && d.path){ const el=document.querySelector(d.path); if(el && selectable(el)){ clean(); el.remove(); } }
  });
  const st=document.createElement('style'); st.id='cms-preview-style'; st.textContent='body.__cmsEditOn *{cursor:crosshair!important} body.__cmsEditOn header,body.__cmsEditOn footer,body.__cmsEditOn .brand-strip,body.__cmsEditOn .site-header,body.__cmsEditOn .site-footer{cursor:not-allowed!important} body.__cmsEditOn .__cmsSel{outline:4px solid #0d6efd!important;outline-offset:3px;background:rgba(13,110,253,.10)!important;box-shadow:0 0 0 8px rgba(13,110,253,.08)!important} body.__cmsEditOn [contenteditable="true"]{cursor:text!important}'; document.head.appendChild(st);
})();
</script>
JS;
$html=str_ireplace('</body>',$inject.'</body>',$html);
echo $html;
?>
