// Asistente por pasos: mejora progresiva. Sin JavaScript el formulario se muestra completo
// y la validación la hace el servidor (RF-02 a RF-04).
(function () {
  'use strict';
  document.documentElement.classList.add('js');

  var form = document.getElementById('form-evaluacion');
  if (!form) { return; }

  var pasos = Array.prototype.slice.call(form.querySelectorAll('.paso'));
  var progreso = document.getElementById('progreso');
  var mensaje = document.getElementById('mensaje-paso');
  var actual = 0;

  // Si el servidor devolvió errores, se abre el primer paso que los contiene.
  pasos.forEach(function (p, i) {
    if (actual === 0 && p.querySelector('.con-error')) { actual = i; }
  });

  function mostrar(i) {
    actual = i;
    pasos.forEach(function (p, k) { p.classList.toggle('activo', k === i); });
    Array.prototype.forEach.call(form.querySelectorAll('[data-accion]'), function (b) { b.hidden = false; });
    progreso.hidden = false;
    progreso.textContent = 'Paso ' + (i + 1) + ' de ' + pasos.length + ': ' + pasos[i].getAttribute('data-titulo');
    mensaje.hidden = true;
    var h = pasos[i].querySelector('h2');
    if (h) { h.setAttribute('tabindex', '-1'); h.focus(); }
  }

  // ---- Campos condicionales ----
  var edadEl = document.getElementById('edad');
  var bloqueEmb = document.getElementById('bloque-embarazo');
  var autoNa = false;
  function edadValor() {
    var t = edadEl ? edadEl.value.trim() : '';
    return /^\d{1,3}$/.test(t) ? parseInt(t, 10) : null;
  }
  function actualizarEmbarazo() {
    if (!bloqueEmb) { return; }
    var e = edadValor();
    var fuera = e !== null && (e < parseInt(bloqueEmb.getAttribute('data-rango-min'), 10) || e > parseInt(bloqueEmb.getAttribute('data-rango-max'), 10));
    var na = document.getElementById('embarazo_na');
    bloqueEmb.hidden = fuera;
    if (fuera && na && !na.checked) { na.checked = true; autoNa = true; }
    if (!fuera && autoNa && na) { na.checked = false; autoNa = false; }
  }
  if (edadEl) { edadEl.addEventListener('input', actualizarEmbarazo); edadEl.addEventListener('change', actualizarEmbarazo); }

  var depEl = document.getElementById('departamento');
  var bloqueMun = document.getElementById('bloque-municipio');
  function actualizarMunicipio() {
    if (!depEl || !bloqueMun) { return; }
    bloqueMun.hidden = depEl.value !== bloqueMun.getAttribute('data-solo');
    if (bloqueMun.hidden) { var m = document.getElementById('municipio'); if (m) { m.value = ''; } }
  }
  if (depEl) { depEl.addEventListener('change', actualizarMunicipio); }

  // «Ninguna» es excluyente con las demás enfermedades crónicas.
  form.addEventListener('change', function (ev) {
    var t = ev.target;
    if (!t || t.name !== 'cronica[]') { return; }
    var todas = form.querySelectorAll('input[name="cronica[]"]');
    Array.prototype.forEach.call(todas, function (c) {
      if (t.value === 'ninguna' && t.checked && c !== t) { c.checked = false; }
      if (t.value !== 'ninguna' && t.checked && c.value === 'ninguna') { c.checked = false; }
    });
  });

  function sinResponder(paso) {
    var faltan = [];
    Array.prototype.forEach.call(paso.querySelectorAll('fieldset[data-grupo]'), function (fs) {
      if (fs.closest('[hidden]')) { return; }
      var name = fs.getAttribute('data-grupo');
      var tipo = fs.getAttribute('data-tipo') || 'radio';
      var ok;
      if (tipo === 'numero') {
        var n = edadValor();
        ok = n !== null && n <= 110;
      } else if (tipo === 'lista') {
        var sel = fs.querySelector('select');
        ok = !!(sel && sel.value);
      } else if (tipo === 'check') {
        ok = !!fs.querySelector('input[type="checkbox"]:checked');
      } else {
        ok = !!form.querySelector('input[name="' + name + '"]:checked');
      }
      if (!ok) { faltan.push(fs); }
    });
    return faltan;
  }

  form.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-accion]');
    if (!b) { return; }
    if (b.getAttribute('data-accion') === 'atras') { mostrar(Math.max(0, actual - 1)); return; }
    var faltan = sinResponder(pasos[actual]);
    pasos[actual].querySelectorAll('fieldset').forEach(function (f) { f.classList.remove('con-error'); });
    if (faltan.length) {
      faltan.forEach(function (f) { f.classList.add('con-error'); });
      mensaje.textContent = 'Responda todas las preguntas de este paso para continuar.';
      mensaje.hidden = false;
      var i = faltan[0].querySelector('input, select');
      if (i) { i.focus(); }
      return;
    }
    mostrar(Math.min(pasos.length - 1, actual + 1));
  });

  form.addEventListener('submit', function (ev) {
    for (var i = 0; i < pasos.length; i++) {
      if (sinResponder(pasos[i]).length) {
        ev.preventDefault();
        mostrar(i);
        mensaje.textContent = 'Faltan respuestas en este paso.';
        mensaje.hidden = false;
        return;
      }
    }
  });

  actualizarMunicipio();
  actualizarEmbarazo();
  mostrar(actual);
})();
