// Reglas del protocolo del INS (2024) y recomendaciones de la OPS (2022).
// Mismo contenido que database/seed.sql del prototipo PHP. Cambiar una regla no exige tocar el motor.
window.REGLAS = {
  version: { id: 1, fecha: '2026-11-12', fuente: 'INS (2024) Protocolo de vigilancia en salud pública: Dengue; OPS (2022)' },
  clases: {
    1: { id: 1, nombre: 'Dengue sin signos de alarma', urgencia: 'Vigilancia',
         recomendacion: 'Hidratación, reposo y vigilancia; vuelva a consultar si aparece alguna señal.',
         mensajeRiesgo: 'Como hay una condición de riesgo, consulte con un profesional de la salud para que valore su caso aunque no tenga señales de alarma.' },
    2: { id: 2, nombre: 'Dengue con signos de alarma', urgencia: 'El mismo día',
         recomendacion: 'Acuda hoy a un centro de salud.', mensajeRiesgo: null },
    3: { id: 3, nombre: 'Dengue grave', urgencia: 'Inmediata',
         recomendacion: 'Acuda ahora a urgencias o llame a la línea de emergencias 123.', mensajeRiesgo: null }
  },
  signos: [
    { codigo: 'A1', nombre: 'Dolor abdominal intenso y continuo', pregunta: '¿Tiene dolor de barriga fuerte que no se le quita?', tipo: 'alarma' },
    { codigo: 'A2', nombre: 'Vómito persistente', pregunta: '¿Ha vomitado varias veces y no logra retener líquidos?', tipo: 'alarma' },
    { codigo: 'A3', nombre: 'Sangrado de mucosas', pregunta: '¿Tiene sangrado por las encías o por la nariz?', tipo: 'alarma' },
    { codigo: 'A4', nombre: 'Letargia o irritabilidad', pregunta: '¿Está muy decaído, con mucho sueño o muy irritable?', tipo: 'alarma' },
    { codigo: 'G1', nombre: 'Choque o dificultad respiratoria', pregunta: '¿Tiene la piel fría, pálida o sudorosa, el pulso débil o dificultad para respirar?', tipo: 'gravedad' },
    { codigo: 'G2', nombre: 'Sangrado grave', pregunta: '¿Tiene sangrado abundante o que no se detiene (vómito o heces con sangre)?', tipo: 'gravedad' },
    { codigo: 'G3', nombre: 'Alteración del estado de conciencia', pregunta: '¿Tiene confusión, desmayo o convulsiones?', tipo: 'gravedad' }
  ],
  reglas: [
    { prioridad: 1, condicion: 'ANY:G1,G2,G3', clase: 3 },
    { prioridad: 2, condicion: 'ANY:A1,A2,A3,A4', clase: 2 },
    { prioridad: 3, condicion: 'ALWAYS', clase: 1 }
  ]
};
