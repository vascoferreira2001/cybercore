// JS para navegação de admin e validação do formulário de registo
document.addEventListener('DOMContentLoaded', function(){
  // Validação do formulário de registo
  var regForm = document.getElementById('registerForm');
  if(regForm){
    regForm.addEventListener('submit', function(e){
      var pwd = document.getElementById('password').value;
      if(pwd.length < 8){
        alert('A password deve ter pelo menos 8 caracteres.');
        e.preventDefault();
        return false;
      }
    });
  }
  
  // Inicializar submenus
  initializeSubmenus();
  
  // Re-inicializar submenus em caso de mudanças no DOM
  var observer = new MutationObserver(function(mutations) {
    initializeSubmenus();
  });
  observer.observe(document.querySelector('.sidebar-nav'), { 
    childList: true, 
    subtree: true 
  });
});

// Toggle submenu - função global
function toggleSubmenu(event, submenuId) {
  if(event) {
    event.preventDefault();
    event.stopPropagation();
  }
  var submenu = document.getElementById(submenuId + '-submenu');
  if(submenu) {
    submenu.classList.toggle('active');
  }
  return false;
}

// Inicializar submenus com event delegation
function initializeSubmenus() {
  var submenuToggles = document.querySelectorAll('.submenu-toggle');
  submenuToggles.forEach(function(toggle) {
    // Remover listeners antigos para evitar duplicatas
    var newToggle = toggle.cloneNode(true);
    toggle.parentNode.replaceChild(newToggle, toggle);
    newToggle.addEventListener('click', handleSubmenuToggle);
  });
}

// Handler para submenu toggle
function handleSubmenuToggle(event) {
  event.preventDefault();
  event.stopPropagation();
  
  // Extrair o ID do data-submenu
  var submenuId = this.getAttribute('data-submenu');
  
  if(submenuId) {
    var submenu = document.getElementById(submenuId + '-submenu');
    if(submenu) {
      submenu.classList.toggle('active');
    }
  }
  
  return false;
}

// Re-inicializar quando a página carrega (AJAX ou navegação)
window.addEventListener('load', function() {
  initializeSubmenus();
});
