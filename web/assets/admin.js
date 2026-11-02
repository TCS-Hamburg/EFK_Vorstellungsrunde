'use strict';
document.querySelector('#clear-form')?.addEventListener('submit', e => {
  if (!window.confirm('Alle Antworten unwiderruflich löschen und eine neue Abfragerunde starten?')) e.preventDefault();
});
