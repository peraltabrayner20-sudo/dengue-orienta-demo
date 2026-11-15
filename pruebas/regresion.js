// Regresión: el motor JavaScript debe dar la misma clase que el motor PHP en los 256 escenarios.
// Uso: node pruebas/regresion.js
const fs = require('fs'), path = require('path');
const M = require('../assets/motor.js');
global.window = {}; eval(fs.readFileSync(path.join(__dirname, '../assets/reglas.js'), 'utf8'));
const R = global.window.REGLAS, S = ['A1','A2','A3','A4','G1','G2','G3'];
const esperado = fs.readFileSync(path.join(__dirname, 'esperado_256_php.csv'), 'utf8').trim().split('\n');
let fallas = 0;
for (let n = 0; n < 256; n++) {
  const bits = n.toString(2).padStart(8, '0'), p = {};
  S.forEach((k, i) => p[k] = bits[i] === '1');
  const obtenido = bits + ',' + M.clasificar(R, p).id_clase;
  if (obtenido !== esperado[n]) { fallas++; console.log('DIFIERE', obtenido, esperado[n]); }
}
console.log(fallas ? 'FALLÓ: ' + fallas + ' diferencias' : 'OK: 256 de 256 escenarios idénticos al motor PHP');
process.exit(fallas ? 1 : 0);
