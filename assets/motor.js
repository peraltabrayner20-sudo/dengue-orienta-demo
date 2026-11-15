// Motor de clasificación: port a JavaScript de src/Classifier.php (misma gramática de condiciones).
(function (root) {
  'use strict';

  function cumple(condicion, presentes) {
    var c = String(condicion).trim();
    if (c === 'ALWAYS') { return true; }
    var m = /^ANY:([A-Z0-9,]+)$/.exec(c);
    if (!m) { throw new Error('Condición de regla no soportada: ' + c); }
    return m[1].split(',').some(function (k) { return !!presentes[k]; });
  }

  function codigosDe(condicion) {
    var m = /^ANY:([A-Z0-9,]+)$/.exec(String(condicion).trim());
    return m ? m[1].split(',') : [];
  }

  function clasificar(reglas, presentes) {
    var ordenadas = reglas.reglas.slice().sort(function (a, b) { return a.prioridad - b.prioridad; });
    for (var i = 0; i < ordenadas.length; i++) {
      var r = ordenadas[i];
      if (!cumple(r.condicion, presentes)) { continue; }
      var clase = reglas.clases[r.clase];
      var senales = [];
      codigosDe(r.condicion).forEach(function (k) {
        if (presentes[k]) {
          var s = reglas.signos.filter(function (x) { return x.codigo === k; })[0];
          senales.push({ codigo: k, nombre: s ? s.nombre : k });
        }
      });
      return { id_clase: clase.id, nombre: clase.nombre, urgencia: clase.urgencia,
               recomendacion: clase.recomendacion, mensaje_riesgo: clase.mensajeRiesgo, senales: senales };
    }
    throw new Error('Ninguna regla aplica: revise las reglas.');
  }

  var api = { cumple: cumple, codigosDe: codigosDe, clasificar: clasificar };
  if (typeof module !== 'undefined' && module.exports) { module.exports = api; } else { root.Motor = api; }
})(typeof window !== 'undefined' ? window : globalThis);
