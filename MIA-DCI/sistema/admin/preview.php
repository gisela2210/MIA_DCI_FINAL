<?php require_once 'layout.php';
$id=(int)($_GET['id']??0); $st=$pdo->prepare('SELECT p.id, p.programa_id AS program_id, p.titulo AS title, p.ruta_archivo AS file_path, p.orden, p.visible, p.actualizado_en AS updated_at, pr.codigo AS code, pr.nombre AS program FROM paginas p JOIN programas pr ON p.programa_id=pr.id WHERE p.id=?'); $st->execute([$id]); $p=$st->fetch(PDO::FETCH_ASSOC); if(!$p || !can_program($p['code'])) die('Sin permiso'); header_admin('Vista previa editable');
$imgs=$pdo->prepare('SELECT id, programa_id AS program_id, pagina_id AS page_id, etiqueta AS label, ruta_imagen AS image_path, nombre_original AS original_name, seccion_referencia AS section_hint, creado_en AS created_at FROM imagenes_pagina WHERE programa_id=? ORDER BY id DESC LIMIT 60'); $imgs->execute([$p['program_id']]); $imgs=$imgs->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="row g-3">
  <div class="col-xxl-9 col-xl-8">
    <div class="preview-shell">
      <div class="preview-toolbar">
        <a href="pages.php" class="btn btn-light btn-rounded"><i class="bi bi-arrow-left"></i></a>
        <span class="program-pill"><i class="bi bi-mortarboard"></i> <?=e($p['code'])?></span>
        <strong class="me-auto"><?=e($p['title'])?></strong>
        <button class="btn btn-success btn-rounded" id="enableEdit"><i class="bi bi-pencil-square"></i> Activar edición</button>
        <button class="btn btn-warning btn-rounded" id="disableEdit"><i class="bi bi-cursor"></i> Navegar normal</button>
        <a class="btn btn-outline-primary btn-rounded" href="versions.php?id=<?=$p['id']?>"><i class="bi bi-clock-history"></i> Versiones</a>
        <button class="btn btn-outline-secondary btn-rounded" id="toggleVistaCompleta"><i class="bi bi-arrows-fullscreen"></i> Vista completa</button>
        <button class="btn btn-tecnm" id="savePage"><i class="bi bi-save"></i> Guardar cambios</button>
      </div>
      <iframe id="frame" class="preview-frame" src="view_page.php?id=<?=$p['id']?>"></iframe>
    </div>
  </div>
  <div class="col-xxl-3 col-xl-4">
    <div class="editor-side">
      <div id="modeBox" class="edit-mode-off mb-3"><i class="bi bi-info-circle"></i> Modo navegación normal: los botones Ver más / Ver perfil funcionan.</div>
      <div class="main-card card p-3 mb-3">
        <h5 class="card-title-tecnm"><i class="bi bi-magic"></i> Editar en la previsualización</h5>
        <div class="help-box mb-3">Primero presiona <b>Activar edición</b>. Después da clic sobre un texto o imagen dentro de la página. Puedes escribir directamente en la vista previa o editar aquí.</div>
        <input type="hidden" id="selector">
        <label class="form-label fw-bold">Elemento seleccionado</label>
        <input id="selectedInfo" class="form-control mb-2" readonly placeholder="Aún no seleccionas nada">
        <label class="form-label fw-bold">Texto</label>
        <textarea id="textEdit" rows="5" class="form-control mb-3" placeholder="Selecciona un texto en la vista previa"></textarea>
        <div class="d-grid gap-2">
          <button class="btn btn-primary btn-rounded" id="applyText"><i class="bi bi-check2-circle"></i> Aplicar texto</button>
          <button class="btn btn-outline-info btn-rounded" id="insertTable"><i class="bi bi-table"></i> Agregar tabla debajo</button>
          <button class="btn btn-outline-danger btn-rounded" id="deleteSelected"><i class="bi bi-trash"></i> Eliminar elemento seleccionado</button>
        </div>
      </div>

      <div class="main-card card p-3 mb-3">
        <h5 class="card-title-tecnm"><i class="bi bi-plus-square"></i> Agregar contenido</h5>
        <div class="cms-note mb-2">Selecciona una sección o un texto en la previsualización. Aquí puedes agregar títulos, subtítulos, fichas, imágenes, tablas, listas, botones y texto sin tocar encabezados, menú ni pie.</div>
        <label class="form-label fw-bold">Tipo de contenido</label>
        <select id="tipoContenido" class="form-select mb-2">
          <option value="parrafo">Párrafo</option>
          <option value="titulo">Título principal</option>
          <option value="subtitulo">Subtítulo</option>
          <option value="ficha">Ficha / tarjeta con texto</option>
          <option value="ficha_imagen">Ficha con imagen y texto</option>
          <option value="grid_fichas">Grupo de 3 fichas</option>
          <option value="lista">Lista</option>
          <option value="boton">Botón / enlace</option>
          <option value="imagen">Imagen</option>
          <option value="tabla">Tabla</option>
          <option value="slide_inicio">Slide del inicio / carrusel</option>
        </select>
        <label class="form-label fw-bold">Contenido</label>
        <textarea id="nuevoContenido" rows="4" class="form-control mb-2" placeholder="Texto nuevo. Para lista o fichas usa una línea por elemento."></textarea>
        <div class="row g-2 mb-2">
          <div class="col-8"><input id="nuevoUrl" class="form-control" placeholder="URL o ruta de imagen/enlace"></div>
          <div class="col-4"><input id="nuevoBoton" class="form-control" placeholder="Botón"></div>
        </div>
        <div class="row g-2 mb-2">
          <div class="col-6"><input id="tablaFilas" type="number" min="1" value="3" class="form-control" placeholder="Filas"></div>
          <div class="col-6"><input id="tablaColumnas" type="number" min="1" value="3" class="form-control" placeholder="Columnas"></div>
        </div>
        <div class="d-grid gap-2">
          <button class="btn btn-outline-primary btn-rounded" id="agregarDentro"><i class="bi bi-box-arrow-in-down"></i> Agregar dentro de la sección seleccionada</button>
          <button class="btn btn-outline-success btn-rounded" id="agregarDebajo"><i class="bi bi-arrow-down-square"></i> Agregar debajo del elemento seleccionado</button>
        </div>
      </div>
      <div class="main-card card p-3 mb-3">
        <h5 class="card-title-tecnm"><i class="bi bi-layout-text-window-reverse"></i> Agregar bloque dentro de la página</h5>
        <div class="cms-note mb-2">Esto NO agrega una opción al menú azul. Solo agrega un bloque de contenido dentro de esta página. Para agregar una sección como Inicio, Perfil de ingreso o Contacto, usa <b>Secciones del menú</b>.</div>
        <input id="seccionTitulo" class="form-control mb-2" placeholder="Título de la nueva sección">
        <input id="seccionSubtitulo" class="form-control mb-2" placeholder="Subtítulo opcional">
        <textarea id="seccionTexto" rows="4" class="form-control mb-2" placeholder="Contenido de la nueva sección"></textarea>
        <input id="seccionImagen" class="form-control mb-2" placeholder="Ruta de imagen opcional">
        <button class="btn btn-tecnm w-100" id="agregarSeccion"><i class="bi bi-plus-circle"></i> Agregar bloque a esta página</button>
        <a href="menu_sections.php" class="btn btn-outline-primary w-100 mt-2"><i class="bi bi-menu-button-wide"></i> Agregar sección al menú azul</a>
      </div>
      <div class="main-card card p-3 mb-3 slider-card-admin">
        <h5 class="card-title-tecnm"><i class="bi bi-images"></i> Agregar slide al inicio</h5>
        <div class="cms-note mb-2">Para la pantalla principal. Agrega otro anuncio al carrusel sin cambiar el diseño del frontend.</div>
        <input id="slideEyebrow" class="form-control mb-2" placeholder="Etiqueta: Nueva convocatoria">
        <input id="slideTitulo" class="form-control mb-2" placeholder="Título del slide">
        <textarea id="slideTexto" rows="3" class="form-control mb-2" placeholder="Texto descriptivo del slide"></textarea>
        <input id="slideImagen" class="form-control mb-2" placeholder="Ruta de imagen. Ej: ./assets/images/anuncios/nueva.png">
        <div class="row g-2 mb-2">
          <div class="col-6"><input id="slideBoton1" class="form-control" placeholder="Botón 1"></div>
          <div class="col-6"><input id="slideLink1" class="form-control" placeholder="Link 1"></div>
          <div class="col-6"><input id="slideBoton2" class="form-control" placeholder="Botón 2"></div>
          <div class="col-6"><input id="slideLink2" class="form-control" placeholder="Link 2"></div>
        </div>
        <button class="btn btn-warning btn-rounded w-100" id="agregarSlideInicio"><i class="bi bi-plus-circle"></i> Agregar slide al carrusel</button>
      </div>
      <div class="main-card card p-3 mb-3">
        <h5 class="card-title-tecnm"><i class="bi bi-image"></i> Cambiar imagen seleccionada</h5>
        <div class="cms-note mb-2">Selecciona una imagen en la previsualización y después pega/escribe una ruta o usa una imagen subida.</div>
        <input id="imgSelector" type="hidden">
        <label class="form-label fw-bold">Ruta de imagen</label>
        <input id="imgSrc" class="form-control mb-2" placeholder="assets/images/imagen.png">
        <label class="form-label fw-bold">Texto alternativo</label>
        <input id="imgAlt" class="form-control mb-2" placeholder="Descripción de imagen">
        <button class="btn btn-info text-white btn-rounded w-100" id="applyImage"><i class="bi bi-arrow-repeat"></i> Reemplazar imagen</button>
      </div>
      <div class="main-card card p-3 mb-3">
        <h5 class="card-title-tecnm"><i class="bi bi-cloud-arrow-up"></i> Subir imagen nueva</h5>
        <form class="upload-zone" method="post" action="upload_image.php" enctype="multipart/form-data">
          <input type="hidden" name="page_id" value="<?=$p['id']?>"><input type="hidden" name="program_id" value="<?=$p['program_id']?>">
          <input type="file" name="image" accept="image/*" class="form-control mb-2" required>
          <input name="label" class="form-control mb-2" placeholder="Nombre de la imagen">
          <button class="btn btn-tecnm w-100"><i class="bi bi-cloud-arrow-up"></i> Subir imagen</button>
        </form>
      </div>
      <div class="main-card card p-3 section-list">
        <h5 class="card-title-tecnm"><i class="bi bi-images"></i> Imágenes disponibles</h5>
        <?php foreach($imgs as $im): ?>
          <div class="d-flex align-items-center gap-2 border-bottom py-2">
            <img class="mini-img" src="../../<?=e($im['image_path'])?>">
            <div class="small flex-grow-1"><b><?=e($im['label'])?></b><br><div class="image-path"><?=e($im['image_path'])?></div></div>
            <button class="btn btn-sm btn-outline-primary" onclick="useImg('<?=e($im['image_path'])?>')">Usar</button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<script>
const frame=document.getElementById('frame');
document.getElementById('toggleVistaCompleta').onclick=()=>{ document.body.classList.toggle('preview-wide-mode'); setTimeout(()=>frame.contentWindow.postMessage({type:'cmsToggleFit'},'*'),100); };
let currentSelector='';
let currentImgSelector='';
const cambios = new Map();
function frameReady(){ return frame.contentWindow; }
function registrarCambio(cambio){
  if(!cambio || !cambio.selector) return;
  cambios.set(cambio.selector+'|'+cambio.tipo, cambio);
}
function setMode(on){
  frameReady().postMessage({type:on?'cmsEnableEdit':'cmsDisableEdit'},'*');
  document.getElementById('modeBox').className=on?'edit-mode-on mb-3':'edit-mode-off mb-3';
  document.getElementById('modeBox').innerHTML=on?'<i class="bi bi-pencil-square"></i> Modo edición activo: selecciona texto o imagen y edita. Los encabezados se ven, pero están protegidos.':'<i class="bi bi-info-circle"></i> Modo navegación normal: los botones Ver más / Ver perfil funcionan.';
}
document.getElementById('enableEdit').onclick=()=>setMode(true);
document.getElementById('disableEdit').onclick=()=>setMode(false);
window.addEventListener('message',ev=>{
 const d=ev.data||{};
 if(d.type==='selectText'){
   currentSelector=d.path;
   document.getElementById('selector').value=d.path;
   document.getElementById('selectedInfo').value=(d.tag||'TEXTO')+' · '+d.path;
   document.getElementById('textEdit').value=d.text||'';
 }
 if(d.type==='liveText'){
   if(d.path===currentSelector){
     document.getElementById('textEdit').value=d.text||'';
     registrarCambio({tipo:'texto',selector:d.path,texto:d.text||''});
   }
 }
 if(d.type==='protectedArea'){
   alert('Los encabezados, menús y pies de página están protegidos. Se ven igual que en la página, pero no se editan para evitar duplicados.');
 }
 if(d.type==='selectImage'){
   currentImgSelector=d.path;
   currentSelector=d.path;
   document.getElementById('selector').value=d.path;
   document.getElementById('imgSelector').value=d.path;
   document.getElementById('selectedInfo').value='IMG · '+d.path;
   document.getElementById('imgSrc').value=d.src||'';
   document.getElementById('imgAlt').value=d.alt||'';
 }
});
document.getElementById('applyText').onclick=()=>{
 if(!currentSelector) return alert('Primero activa edición y selecciona un texto en la previsualización.');
 const texto=document.getElementById('textEdit').value;
 registrarCambio({tipo:'texto',selector:currentSelector,texto});
 frameReady().postMessage({type:'cmsSetText',path:currentSelector,text:texto},'*');
};
document.getElementById('insertTable').onclick=()=>{
 if(!currentSelector) return alert('Primero selecciona la zona donde irá la tabla.');
 const rows=parseInt(prompt('¿Cuántas filas?', '3')||'3',10);
 const cols=parseInt(prompt('¿Cuántas columnas?', '3')||'3',10);
 let html='<div class="table-responsive my-3"><table class="table table-bordered table-striped align-middle"><tbody>';
 for(let i=0;i<rows;i++){html+='<tr>'; for(let j=0;j<cols;j++){html+= i==0?'<th>Encabezado</th>':'<td>Dato</td>'; } html+='</tr>'; }
 html+='</tbody></table></div>';
 registrarCambio({tipo:'tabla',selector:currentSelector,html});
 frameReady().postMessage({type:'cmsInsertTable',path:currentSelector,html},'*');
};
document.getElementById('applyImage').onclick=()=>{
 if(!currentImgSelector) return alert('Primero activa edición y selecciona una imagen en la previsualización.');
 const src=document.getElementById('imgSrc').value;
 const alt=document.getElementById('imgAlt').value;
 registrarCambio({tipo:'imagen',selector:currentImgSelector,src,alt});
 frameReady().postMessage({type:'cmsReplaceImage',path:currentImgSelector,src,alt},'*');
};
document.getElementById('deleteSelected').onclick=()=>{
 if(!currentSelector && !currentImgSelector) return alert('Primero selecciona un elemento dentro de la vista previa.');
 const selector=currentImgSelector || currentSelector;
 if(!confirm('¿Eliminar este elemento seleccionado? No se permite borrar encabezado, menú ni pie.')) return;
 registrarCambio({tipo:'eliminar',selector});
 frameReady().postMessage({type:'cmsDeleteElement',path:selector},'*');
 currentSelector=''; currentImgSelector='';
 document.getElementById('selectedInfo').value='Elemento marcado para eliminar';
 document.getElementById('textEdit').value='';
};
function useImg(path){
 document.getElementById('imgSrc').value='../../'+path;
 if(currentImgSelector){
   const src='../../'+path;
   const alt=document.getElementById('imgAlt').value;
   registrarCambio({tipo:'imagen',selector:currentImgSelector,src,alt});
   frameReady().postMessage({type:'cmsReplaceImage',path:currentImgSelector,src,alt},'*');
 }
}

function escaparHtml(v){
 return String(v||'').replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}
function construirTabla(filas,columnas){
 filas=Math.max(1,parseInt(filas||3,10)); columnas=Math.max(1,parseInt(columnas||3,10));
 let html='<div class="table-responsive my-4"><table class="table table-bordered table-striped align-middle"><tbody>';
 for(let i=0;i<filas;i++){ html+='<tr>'; for(let j=0;j<columnas;j++){ html+= i===0?'<th>Encabezado</th>':'<td>Dato</td>'; } html+='</tr>'; }
 return html+'</tbody></table></div>';
}
function construirContenido(){
 const tipo=document.getElementById('tipoContenido').value;
 const texto=document.getElementById('nuevoContenido').value.trim();
 const url=document.getElementById('nuevoUrl').value.trim();
 const boton=document.getElementById('nuevoBoton').value.trim() || 'Ver más';
 if(tipo==='titulo') return '<h2 class="cms-titulo-agregado">'+escaparHtml(texto||'Nuevo título')+'</h2>';
 if(tipo==='subtitulo') return '<h3 class="cms-subtitulo-agregado">'+escaparHtml(texto||'Nuevo subtítulo')+'</h3>';
 if(tipo==='ficha') return '<div class="cms-ficha card shadow-sm border-0 my-3"><div class="card-body"><h4>'+escaparHtml(boton||'Título de la ficha')+'</h4><p>'+escaparHtml(texto||'Contenido de la ficha').replace(/\n/g,'<br>')+'</p></div></div>';
 if(tipo==='ficha_imagen') return '<div class="cms-ficha card shadow-sm border-0 my-3 overflow-hidden"><div class="row g-0 align-items-center"><div class="col-md-4"><img class="img-fluid w-100" src="'+escaparHtml(url||'assets/images/imagen.png')+'" alt="'+escaparHtml(boton||'Imagen de ficha')+'"></div><div class="col-md-8"><div class="card-body"><h4>'+escaparHtml(boton||'Título de la ficha')+'</h4><p>'+escaparHtml(texto||'Contenido de la ficha').replace(/\n/g,'<br>')+'</p></div></div></div></div>';
 if(tipo==='grid_fichas'){
   const lineas=(texto||'Ficha 1: Descripción de la ficha 1\nFicha 2: Descripción de la ficha 2\nFicha 3: Descripción de la ficha 3').split(/\r?\n/).filter(Boolean);
   let html='<div class="row g-3 my-4 cms-grid-fichas">';
   lineas.slice(0,6).forEach((linea,i)=>{const partes=linea.split(':'); const tit=partes.shift()||('Ficha '+(i+1)); const desc=partes.join(':')||'Descripción'; html+='<div class="col-md-4"><div class="card h-100 shadow-sm border-0"><div class="card-body"><h4>'+escaparHtml(tit.trim())+'</h4><p>'+escaparHtml(desc.trim())+'</p></div></div></div>';});
   return html+'</div>';
 }
 if(tipo==='lista'){
   const items=(texto||'Elemento 1\nElemento 2').split(/\r?\n/).filter(Boolean).map(x=>'<li>'+escaparHtml(x)+'</li>').join('');
   return '<ul class="cms-lista-agregada">'+items+'</ul>';
 }
 if(tipo==='boton') return '<p><a class="btn btn-primary" href="'+escaparHtml(url||'#')+'">'+escaparHtml(texto||boton)+'</a></p>';
 if(tipo==='imagen') return '<figure class="my-4"><img class="img-fluid rounded" src="'+escaparHtml(url||'assets/images/imagen.png')+'" alt="'+escaparHtml(texto||'Imagen')+'"><figcaption>'+escaparHtml(texto||'')+'</figcaption></figure>';
 if(tipo==='tabla') return construirTabla(document.getElementById('tablaFilas').value, document.getElementById('tablaColumnas').value);
 if(tipo==='slide_inicio') return construirSlideInicio({eyebrow:boton||'Nuevo anuncio', titulo:texto.split('\n')[0]||'Nuevo slide', texto:texto.split('\n').slice(1).join('\n')||'Descripción del anuncio.', imagen:url||'./assets/images/anuncios/nueva-convocatoria.png', boton1:'Ver más', link1:'#', boton2:'Requisitos', link2:'#'});
 return '<p>'+escaparHtml(texto||'Nuevo contenido')+'</p>';
}
function construirSlideInicio(datos){
 const eyebrow=escaparHtml(datos.eyebrow||'Nuevo anuncio');
 const titulo=escaparHtml(datos.titulo||'Nuevo slide');
 const texto=escaparHtml(datos.texto||'Descripción del anuncio').replace(/\n/g,'<br>');
 const imagen=escaparHtml(datos.imagen||'./assets/images/anuncios/nueva-convocatoria.png');
 const boton1=escaparHtml(datos.boton1||'Ver más');
 const link1=escaparHtml(datos.link1||'#');
 const boton2=escaparHtml(datos.boton2||'Requisitos');
 const link2=escaparHtml(datos.link2||'#');
 return '<article class="announcements-slide hero-slide hero-slide--announcement" data-js="announcement-slide">'
   + '<img src="'+imagen+'" alt="'+titulo+'">'
   + '<div class="container hero__content">'
   + '<p class="eyebrow">'+eyebrow+'</p>'
   + '<h1>'+titulo+'</h1>'
   + '<p class="lead">'+texto+'</p>'
   + '<div class="button-row"><a class="button button--primary" href="'+link1+'">'+boton1+'</a><a class="button button--secondary" href="'+link2+'">'+boton2+'</a></div>'
   + '</div></article>';
}
function construirSeccion(){
 const titulo=document.getElementById('seccionTitulo').value.trim() || 'Nueva sección';
 const subtitulo=document.getElementById('seccionSubtitulo').value.trim();
 const texto=document.getElementById('seccionTexto').value.trim() || 'Escribe aquí el contenido de la nueva sección.';
 const img=document.getElementById('seccionImagen').value.trim();
 let html='<section class="cms-seccion-agregada py-5"><div class="container">';
 html+='<h2>'+escaparHtml(titulo)+'</h2>';
 if(subtitulo) html+='<h4>'+escaparHtml(subtitulo)+'</h4>';
 if(img) html+='<img class="img-fluid rounded my-3" src="'+escaparHtml(img)+'" alt="'+escaparHtml(titulo)+'">';
 html+='<p>'+escaparHtml(texto).replace(/\n/g,'<br>')+'</p>';
 html+='</div></section>';
 return html;
}
function agregarContenido(posicion){
 if(!currentSelector) return alert('Primero activa edición y selecciona la sección o elemento donde quieres agregar contenido.');
 const html=construirContenido();
 const tipo= posicion==='inside' ? 'contenido_dentro' : 'contenido_debajo';
 registrarCambio({tipo, selector:currentSelector, html});
 frameReady().postMessage({type:'cmsInsertContent',path:currentSelector,html,position:posicion},'*');
}
document.getElementById('agregarDentro').onclick=()=>agregarContenido('inside');
document.getElementById('agregarDebajo').onclick=()=>agregarContenido('after');
document.getElementById('agregarSeccion').onclick=()=>{
 const html=construirSeccion();
 const selector=currentSelector || 'main:nth-of-type(1)';
 registrarCambio({tipo:'seccion_nueva', selector, html});
 frameReady().postMessage({type:'cmsInsertSection',path:selector,html},'*');
};

document.getElementById('agregarSlideInicio').onclick=()=>{
 const html=construirSlideInicio({
   eyebrow:document.getElementById('slideEyebrow').value.trim()||'Nuevo anuncio',
   titulo:document.getElementById('slideTitulo').value.trim()||'Nuevo slide del inicio',
   texto:document.getElementById('slideTexto').value.trim()||'Agrega aquí la información del anuncio principal.',
   imagen:document.getElementById('slideImagen').value.trim()||'./assets/images/anuncios/nueva-convocatoria.png',
   boton1:document.getElementById('slideBoton1').value.trim()||'Ver más',
   link1:document.getElementById('slideLink1').value.trim()||'#',
   boton2:document.getElementById('slideBoton2').value.trim()||'Requisitos',
   link2:document.getElementById('slideLink2').value.trim()||'#'
 });
 const selector='main#main-content>section:nth-of-type(1)>div:nth-of-type(1)';
 registrarCambio({tipo:'slide_inicio', selector, html});
 frameReady().postMessage({type:'cmsAddSlideInicio', html},'*');
 alert('Slide agregado en la vista previa. Presiona Guardar cambios para reflejarlo en FRONTEND-DCI / FRONTEND-MIA.');
};
document.getElementById('savePage').onclick=async()=>{
 if(cambios.size===0) return alert('No hay cambios pendientes. Edita un texto, cambia una imagen o agrega una tabla.');
 if(!confirm('Se guardarán solo los elementos editados. No se tocarán encabezados, menú ni pie de página. ¿Continuar?')) return;
 frameReady().postMessage({type:'cmsDisableEdit'},'*');
 const res=await fetch('save_page.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({id:'<?=$p['id']?>',cambios:JSON.stringify([...cambios.values()])})});
 const txt=await res.text();
 alert(txt);
 if(res.ok){ cambios.clear(); frame.src=frame.src; }
};
</script>
<?php footer_admin(); ?>
