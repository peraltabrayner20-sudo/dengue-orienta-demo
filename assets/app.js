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

  var PASOS = [
    { titulo: 'Datos básicos', html: function () {
        var dias = [['1-2', '1 a 2 días'], ['3-7', '3 a 7 días'], ['8+', '8 días o más']].map(function (d) {
          return '<span class="op"><input type="radio" id="dias_' + d[0] + '" name="dias_fiebre" value="' + d[0] + '"' + (estado.dias_fiebre === d[0] ? ' checked' : '') + '>' +
                 '<label for="dias_' + d[0] + '">' + d[1] + '</label></span>';
        }).join('');
        return '<fieldset class="pregunta" data-grupo="dias_fiebre"><legend>¿Cuántos días lleva con fiebre?</legend><div class="opciones">' + dias + '</div></fieldset>' +
               pregunta('riesgo', '¿Está embarazada, es menor de 5 años, tiene 65 años o más o una enfermedad crónica?');
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
      var name = fs.getAttribute('data-grupo');
      var sel = document.querySelector('#contenido-paso input[name="' + name + '"]:checked');
      if (sel) { estado[name] = sel.value; } else { out.push(fs); }
    });
    return out;
  }

  function avanzar(delta) {
    var f = faltan();
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
    var presentes = {};
    R.signos.forEach(function (s) { presentes[s.codigo] = estado[s.codigo] === 'si'; });
    var res = M.clasificar(R, presentes);
    var id = res.id_clase, clase = { 1: 'sin', 2: 'alarma', 3: 'grave' }[id], icono = { 1: '●', 2: '▲', 3: '■' }[id];
    var h = '';
    if (id === 3) { h += '<div class="urgencia" role="alert"><strong>URGENCIA INMEDIATA:</strong> ' + e(res.recomendacion) + '</div>'; }
    h += '<div class="resultado r-' + clase + '"><p class="etiqueta">Resultado de la orientación</p><h1><span aria-hidden="true">' + icono + '</span> ' + e(res.nombre) +
         '</h1><p class="urg">Nivel de atención: <strong>' + e(res.urgencia) + '</strong></p></div>';
    h += '<h2>Qué hacer</h2><p class="accion">' + e(res.recomendacion) + '</p>';
    if (id === 1 && estado.riesgo === 'si' && res.mensaje_riesgo) { h += '<p class="accion">' + e(res.mensaje_riesgo) + '</p>'; }
    h += '<h2>Señales que activaron este resultado</h2>';
    h += res.senales.length ? '<ul>' + res.senales.map(function (s) { return '<li>' + e(s.nombre) + '</li>'; }).join('') + '</ul>'
                            : '<p>No marcó señales de alarma ni de gravedad.</p>';
    if (id === 1) {
      h += '<h2>Cuidados en casa</h2><ul>' +
        '<li>Tome abundantes líquidos (agua, suero oral, sopas, jugos) y descanse.</li>' +
        '<li>Para la fiebre y el dolor use acetaminofén (paracetamol) según el empaque o la indicación de su médico. <strong>No use aspirina ni ibuprofeno</strong> u otros antiinflamatorios.</li>' +
        '<li>Evite que lo picen mosquitos (toldillo, repelente, ropa larga) y elimine el agua estancada.</li></ul>';
      if (estado.dias_fiebre === '3-7' || estado.dias_fiebre === '8+') {
        h += '<p class="aviso-dias">Cuando la fiebre empieza a bajar (hacia los días 3 a 7) es cuando más aparecen los signos de alarma: vigile con más cuidado.</p>';
      }
      h += '<h2>Vuelva a consultar de inmediato si aparece</h2><ul>' +
        '<li>Dolor de barriga fuerte que no se quita, o vómito repetido.</li><li>Sangrado por encías o nariz.</li>' +
        '<li>Mucho sueño, decaimiento o irritabilidad.</li>' +
        '<li>Piel fría, pálida o sudorosa, dificultad para respirar, confusión o convulsiones.</li></ul>';
    }
    h += '<p class="no-diagnostica"><strong>Importante:</strong> esto no es un diagnóstico. La herramienta solo orienta y no reemplaza la valoración médica.</p>';
    h += '<button class="boton" type="button" id="nueva">Nueva evaluación</button>';
    $('resultado-contenido').innerHTML = h;
    $('nueva').addEventListener('click', reiniciar);
    mostrarPantalla('p-resultado');
  }

  function reiniciar() { estado = {}; paso = 0; pintarPaso(); mostrarPantalla('p-evaluacion'); }

  $('aceptar').addEventListener('click', function () {
    if (!$('acepto').checked) { $('error-aviso').hidden = false; return; }
    $('error-aviso').hidden = true; reiniciar();
  });
  $('atras').addEventListener('click', function () { avanzar(-1); });
  $('siguiente').addEventListener('click', function () { avanzar(1); });
  $('ver').addEventListener('click', verResultado);
  mostrarPantalla('p-aviso');
})();
