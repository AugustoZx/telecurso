document.addEventListener('contextmenu', function(e) {
  e.preventDefault(); // Impede o menu de contexto padrão
});

document.addEventListener('keydown', function(e) {
  if (e.keyCode === 123) { // Código da tecla F12
    e.preventDefault();
  }
});