document.addEventListener('contextmenu', function(e) {
  e.preventDefault(); // Impede o menu de contexto padrão
});

document.addEventListener('keydown', function(e) {
  if (e.keyCode === 123) { // Código da tecla F12
    e.preventDefault();
  }
});

// Detectar scroll da página e aplicar efeito na navegação
window.addEventListener('scroll', function() {
    const nav = document.querySelector('nav');
    if (window.scrollY > 50) {
        nav.classList.add('scrolled');
    } else {
        nav.classList.remove('scrolled');
    }
});

// Adicionar event listeners
window.addEventListener('scroll', handleScrollAnimations);
window.addEventListener('load', handleScrollAnimations);

