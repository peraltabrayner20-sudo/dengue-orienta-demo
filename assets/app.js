// Asistente de la demostración estática. No guarda ningún dato: todo ocurre en la memoria del navegador.
(function () {
  'use strict';
  var R = window.REGLAS, M = window.Motor;
  var $ = function (id) { return document.getElementById(id); };
  var estado, paso;

  function e(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  function pregunta(name, texto) {
    var v = estado[name] || '';
    return '<fieldset class="pregunta" data-grupo="' + name + '"><legend>' + e(texto) + '</legend><div class="opciones">' +
      ['si', 'no'].map(function (val) {
        var id = name + '_' + val;
        return '<span class="op"><input type="radio" id="' + id + '" name="' + name + '" value="' + val + '"' + (v === val ? ' checked' : '') + '>' +
               '<label for="' + id + '">' + (val === 'si' ? 'Sí' : 'No') + '</label></span>';
      }).join('') + '</div></fieldset>';
  }

  var DEPARTAMENTOS = ['Amazonas','Antioquia','Arauca','Atlántico','Bogotá, D. C.','Bolívar','Boyacá','Caldas','Caquetá','Casanare','Cauca','Cesar','Chocó','Córdoba','Cundinamarca','Guainía','Guaviare','Huila','La Guajira','Magdalena','Meta','Nariño','Norte de Santander','Putumayo','Quindío','Risaralda','San Andrés y Providencia','Santander','Sucre','Tolima','Valle del Cauca','Vaupés','Vichada'];
  var MUN_CESAR = ['Aguachica','Agustín Codazzi','Astrea','Becerril','Bosconia','Chimichagua','Chiriguaná','Curumaní','El Copey','El Paso','Gamarra','González','La Gloria','La Jagua de Ibirico','La Paz','Manaure Balcón del Cesar','Pailitas','Pelaya','Pueblo Bello','Río de Oro','San Alberto','San Diego','San Martín','Tamalameque','Valledupar'];
  var CRONICAS = [['diabetes','Diabetes'],['corazon','Problemas del corazón o presión alta'],['rinon','Enfermedad del riñón'],['defensas','Defensas bajas (por ejemplo VIH, cáncer o medicinas que bajan las defensas)'],['otra','Otra enfermedad crónica'],['ninguna','Ninguna']];

  function opciones(name, texto, lista, extra) {
    var v = estado[name] || '';
    return '<fieldset class="pregunta" data-grupo="' + name + '"><legend>' + e(texto) + '</legend><div class="opciones">' +
      lista.map(function (o) {
        var id = name + '_' + o[0];
        return '<span class="op"><input type="radio" id="' + id + '" name="' + name + '" value="' + o[0] + '"' + (v === o[0] ? ' checked' : '') + '>' +
               '<label for="' + id + '">' + e(o[1]) + '</label></span>';
      }).join('') + '</div></fieldset>';
  }
  function lista(name, texto, valores, vacio) {
    return '<fieldset class="pregunta" data-grupo="' + name + '" data-tipo="lista"><legend><label for="' + name + '">' + e(texto) + '</label></legend>' +
      '<select id="' + name + '" name="' + name + '"><option value="">Seleccione…</option>' +
      valores.map(function (x) { return '<option' + (estado[name] === x ? ' selected' : '') + '>' + e(x) + '</option>'; }).join('') + '</select></fieldset>';
  }
  function edadValor() {
    var t = String(estado.edad == null ? '' : estado.edad).trim();
    return /^\d{1,3}$/.test(t) && parseInt(t, 10) <= 110 ? parseInt(t, 10) : null;
  }

  var PASOS = [
    { titulo: 'La persona', html: function () {
        return '<fieldset class="pregunta" data-grupo="edad" data-tipo="numero"><legend>¿Qué edad tiene la persona? (en años; escriba 0 si es menor de un año)</legend>' +
               '<input class="entrada-num" type="number" inputmode="numeric" id="edad" name="edad" min="0" max="110" step="1" value="' + e(estado.edad == null ? '' : estado.edad) + '">' +
               '<p class="ayuda">En esta demostración no se guarda nada.</p></fieldset>' +
               opciones('dias_fiebre', '¿Cuántos días lleva con fiebre?', [['1-2', '1 a 2 días'], ['3-7', '3 a 7 días'], ['8+', '8 días o más']]);
    } },
    { titulo: 'Condiciones de riesgo', html: function () {
        var edad = edadValor();
        var emb = (edad !== null && (edad < 10 || edad > 55)) ? '' : opciones('embarazo', '¿La persona está embarazada?', [['si', 'Sí'], ['no', 'No'], ['na', 'No aplica']]);
        var cr = '<fieldset class="pregunta" data-grupo="cronica" data-tipo="check"><legend>¿Tiene alguna enfermedad crónica? Marque todas las que apliquen.</legend><div class="opciones opciones-col">' +
          CRONICAS.map(function (c) {
            return '<span class="op"><input type="checkbox" id="cronica_' + c[0] + '" name="cronica" value="' + c[0] + '"' + ((estado.cronica || []).indexOf(c[0]) >= 0 ? ' checked' : '') + '>' +
                   '<label for="cronica_' + c[0] + '">' + e(c[1]) + '</label></span>';
          }).join('') + '</div></fieldset>';
        return emb + cr;
    } },
    { titulo: 'Ubicación y entorno', html: function () {
        return '<p class="ayuda">No pedimos dirección ni barrio.</p>' +
               lista('departamento', 'Departamento donde vive la persona', DEPARTAMENTOS) +
               (estado.departamento === 'Cesar' ? lista('municipio', 'Municipio del Cesar', MUN_CESAR) : '') +
               opciones('zona', '¿La vivienda está en zona urbana o rural?', [['urbana', 'Zona urbana (cabecera)'], ['rural', 'Zona rural o corregimiento']]) +
               opciones('agua', '¿Hay agua estancada cerca de la vivienda (tanques destapados, llantas, charcos, floreros)?', [['si', 'Sí'], ['no', 'No'], ['nose', 'No sé']]);
    } },
    { titulo: 'Señales de alarma', html: function () {
        return R.signos.filter(function (s) { return s.tipo === 'alarma'; }).map(function (s) { return pregunta(s.codigo, s.pregunta); }).join('');
    } },
    { titulo: 'Señales de gravedad', html: function () {
        return R.signos.filter(function (s) { return s.tipo === 'gravedad'; }).map(function (s) { return pregunta(s.codigo, s.pregunta); }).join('');
    } }
  ];

  function mostrarPantalla(id) {
    ['p-aviso', 'p-evaluacion', 'p-resultado'].forEach(function (x) { $(x).hidden = (x !== id); });
    window.scrollTo(0, 0);
  }

  function pintarPaso() {
    var p = PASOS[paso];
    $('progreso').textContent = 'Paso ' + (paso + 1) + ' de ' + PASOS.length + ': ' + p.titulo;
    $('contenido-paso').innerHTML = '<h2 tabindex="-1" id="titulo-paso">Paso ' + (paso + 1) + ' · ' + p.titulo + '</h2>' + p.html();
    $('atras').hidden = paso === 0;
    $('siguiente').hidden = paso === PASOS.length - 1;
    $('ver').hidden = paso !== PASOS.length - 1;
    $('mensaje-paso').hidden = true;
    $('titulo-paso').focus();
  }

  function faltan() {
    var out = [];
    document.querySelectorAll('#contenido-paso fieldset[data-grupo]').forEach(function (fs) {
      var name = fs.getAttribute('data-grupo'), tipo = fs.getAttribute('data-tipo') || 'radio', ok = false;
      if (tipo === 'numero') {
        var inp = fs.querySelector('input'); estado.edad = inp.value; ok = edadValor() !== null;
      } else if (tipo === 'lista') {
        var sel = fs.querySelector('select'); estado[name] = sel.value; ok = !!sel.value;
      } else if (tipo === 'check') {
        var marcados = Array.prototype.map.call(fs.querySelectorAll('input:checked'), function (c) { return c.value; });
        if (marcados.indexOf('ninguna') >= 0 && marcados.length > 1) { marcados = ['ninguna']; }
        estado.cronica = marcados; ok = marcados.length > 0;
      } else {
        var r = document.querySelector('#contenido-paso input[name="' + name + '"]:checked');
        if (r) { estado[name] = r.value; ok = true; }
      }
      if (!ok) { out.push(fs); }
    });
    if (estado.departamento && estado.departamento !== 'Cesar') { estado.municipio = null; }
    return out;
  }

  function avanzar(delta) {
    var f = faltan();
    var ed = edadValor(); if (ed !== null && (ed < 10 || ed > 55)) { estado.embarazo = 'na'; }
    document.querySelectorAll('#contenido-paso fieldset').forEach(function (x) { x.classList.remove('con-error'); });
    if (delta > 0 && f.length) {
      f.forEach(function (x) { x.classList.add('con-error'); });
      $('mensaje-paso').textContent = 'Responda todas las preguntas de este paso para continuar.';
      $('mensaje-paso').hidden = false;
      var i = f[0].querySelector('input'); if (i) { i.focus(); }
      return;
    }
    paso = Math.max(0, Math.min(PASOS.length - 1, paso + delta));
    pintarPaso();
  }

  function verResultado() {
    if (faltan().length) { avanzar(1); return; }
    var ed = edadValor(); if (ed !== null && (ed < 10 || ed > 55)) { estado.embarazo = 'na'; }
    var cron = (estado.cronica || []).filter(function (c) { return c !== 'ninguna'; });
    var riesgo = M.riesgo(ed, estado.embarazo || 'na', cron);
    var condiciones = [];
    if (ed < 5) { condiciones.push('es menor de 5 años'); }
    if (ed >= 65) { condiciones.push('tiene 65 años o más'); }
    if (estado.embarazo === 'si') { condiciones.push('está embarazada'); }
    cron.forEach(function (c) { var n = CRONICAS.filter(function (x) { return x[0] === c; })[0]; condiciones.push('tiene ' + n[1].toLowerCase()); });
    var presentes = {};
    R.signos.forEach(function (s) { presentes[s.codigo] = estado[s.codigo] === 'si'; });
    var res = M.clasificar(R, presentes);
    var id = res.id_clase, clase = { 1: 'sin', 2: 'alarma', 3: 'grave' }[id], icono = { 1: '●', 2: '▲', 3: '■' }[id];
    var h = '';
    if (id === 3) { h += '<div class="urgencia" role="alert"><strong>URGENCIA INMEDIATA:</strong> ' + e(res.recomendacion) + '</div>'; }
    h += '<div class="resultado r-' + clase + '"><p class="etiqueta">Resultado de la orientación</p><h1><span aria-hidden="true">' + icono + '</span> ' + e(res.nombre) +
         '</h1><p class="urg">Nivel de atención: <strong>' + e(res.urgencia) + '</strong></p></div>';
    var li = function (t) { return '<li>' + t + '</li>'; };
    h += '<h2>Qué hacer, paso a paso</h2><p class="accion">' + e(res.recomendacion) + '</p>';
    if (id === 3) {
      h += '<ol class="paso-a-paso u-grave">' + [
        '<strong>Llame al 123 o vaya ya al servicio de urgencias más cercano.</strong> No espere a que baje la fiebre ni a que amanezca.',
        '<strong>Que alguien lo acompañe.</strong> Si está confuso, muy decaído o con la piel fría, no conduzca.',
        '<strong>Lleve su documento y el carné de la EPS si los tiene a la mano,</strong> pero no se demore buscándolos.',
        '<strong>Cuente al personal de salud</strong> cuántos días lleva con fiebre, las señales de esta pantalla y las condiciones de riesgo que indicó.',
        '<strong>No se automedique.</strong> No tome aspirina, ibuprofeno ni otros antiinflamatorios.',
        '<strong>Mientras llega la ayuda,</strong> mantenga a la persona acostada y vigilada. Si está inconsciente o convulsionando, no le dé de comer ni de beber.'].map(li).join('') + '</ol>';
    } else if (id === 2) {
      h += '<ol class="paso-a-paso u-alarma">' + [
        '<strong>Acuda hoy mismo a un centro de salud</strong> (urgencias o su IPS). No espere a mañana: estas señales pueden empeorar en horas.',
        '<strong>Vaya acompañado,</strong> sobre todo si es un niño, una embarazada o un adulto mayor.',
        '<strong>Lleve su documento y el carné de la EPS</strong> si los tiene a la mano.',
        '<strong>Cuente al médico</strong> los días de fiebre, las señales marcadas y las condiciones de riesgo que indicó.',
        '<strong>Hasta que lo atiendan:</strong> tome líquidos en sorbos pequeños si logra retenerlos y descanse. Para la fiebre use solo acetaminofén según el empaque o la indicación médica. <strong>No use aspirina ni ibuprofeno.</strong>',
        '<strong>Si en el camino aparece</strong> piel fría o pálida, dificultad para respirar, sangrado abundante, confusión o convulsiones, <strong>llame al 123</strong>.'].map(li).join('') + '</ol>';
    } else {
      h += '<ol class="paso-a-paso u-sin">' + [
        '<strong>Hidrátese:</strong> agua, suero oral, sopas o jugos, varias veces al día aunque no tenga sed.',
        '<strong>Descanse</strong> en casa y no vaya al trabajo ni al colegio mientras tenga fiebre.',
        '<strong>Para la fiebre y el dolor</strong> use acetaminofén (paracetamol) según el empaque o la indicación de su médico. <strong>No use aspirina ni ibuprofeno</strong> u otros antiinflamatorios.',
        '<strong>Evite más picaduras:</strong> toldillo, repelente y ropa larga. Así protege a su familia. Elimine el agua estancada cerca de la vivienda.',
        '<strong>Revise cada día</strong> si aparece alguna de las señales de la lista «Vuelva a consultar de inmediato» (más abajo).',
        '<strong>Si no mejora o tiene dudas,</strong> consulte en su IPS o centro de salud.'].map(li).join('') + '</ol>';
    }
    if (id === 1 && riesgo && res.mensaje_riesgo) { h += '<p class="accion">' + e(res.mensaje_riesgo) + '</p>'; }
    if (condiciones.length) {
      h += '<div class="bloque-riesgo"><p><strong>Condiciones de riesgo que usted indicó:</strong> la persona ' + e(condiciones.join('; ')) + '.</p><p>Menciónelas siempre al personal de salud. ' +
           (ed < 5 ? 'En un niño pequeño, la deshidratación puede empeorar rápido: si rechaza los líquidos o está muy dormido, consulte sin esperar. ' : '') +
           (estado.embarazo === 'si' ? 'Una embarazada con fiebre debe ser valorada por un profesional de la salud. ' : '') + '</p></div>';
    }
    h += '<h2>Señales que activaron este resultado</h2>';
    h += res.senales.length ? '<ul>' + res.senales.map(function (s) { return '<li>' + e(s.nombre) + '</li>'; }).join('') + '</ul>'
                            : '<p>No marcó señales de alarma ni de gravedad.</p>';
    if (id === 1) {
      if (estado.dias_fiebre === '3-7' || estado.dias_fiebre === '8+') {
        h += '<p class="aviso-dias">Cuando la fiebre empieza a bajar (hacia los días 3 a 7) es cuando más aparecen los signos de alarma: vigile con más cuidado.</p>';
      }
      h += '<h2>Vuelva a consultar de inmediato si aparece</h2><ul>' +
        '<li>Dolor de barriga fuerte que no se quita, o vómito repetido.</li><li>Sangrado por encías o nariz.</li>' +
        '<li>Mucho sueño, decaimiento o irritabilidad.</li>' +
        '<li>Piel fría, pálida o sudorosa, dificultad para respirar, confusión o convulsiones.</li></ul>';
    }
    h += '<p class="no-diagnostica"><strong>Importante:</strong> esto no es un diagnóstico. La herramienta solo orienta y no reemplaza la valoración médica.</p>';
    h += '<p><a href="panel.html">Ver el panel de situación (ejemplo con datos sintéticos)</a></p><button class="boton" type="button" id="nueva">Nueva evaluación</button>';
    $('resultado-contenido').innerHTML = h;
    $('nueva').addEventListener('click', reiniciar);
    mostrarPantalla('p-resultado');
  }

  function reiniciar() { estado = {}; paso = 0; pintarPaso(); mostrarPantalla('p-evaluacion'); }

  $('aceptar').addEventListener('click', function () {
    if (!$('acepto').checked) { $('error-aviso').hidden = false; return; }
    $('error-aviso').hidden = true; reiniciar();
  });
  // Al elegir el departamento se muestra (o se oculta) la lista de municipios del Cesar.
  $('contenido-paso').addEventListener('change', function (ev) {
    if (ev.target && ev.target.id === 'departamento') {
      faltan();
      pintarPaso();
      var d = $('departamento'); if (d) { d.focus(); }
    }
  });
  $('atras').addEventListener('click', function () { avanzar(-1); });
  $('siguiente').addEventListener('click', function () { avanzar(1); });
  $('ver').addEventListener('click', verResultado);
  mostrarPantalla('p-aviso');
})();
