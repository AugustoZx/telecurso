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

// Função para verificar se elemento está visível (mais flexível)
function isElementVisible(el) {
    const rect = el.getBoundingClientRect();
    const windowHeight = window.innerHeight || document.documentElement.clientHeight;
    
    // Elemento está visível quando pelo menos 20% dele está na tela
    return rect.top < windowHeight * 0.8;
}

// Aplicar animações quando elementos entram na viewport
function handleScrollAnimations() {
    const sections = document.querySelectorAll('.conteudo-curso');
    
    sections.forEach(section => {
        if (isElementVisible(section) && !section.classList.contains('animate')) {
            section.classList.add('animate');
        }
    });
}

// Adicionar event listeners
window.addEventListener('scroll', handleScrollAnimations);
window.addEventListener('load', handleScrollAnimations);

