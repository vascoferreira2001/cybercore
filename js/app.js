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
  
  // Inicializar submenus com event delegation
  initializeSubmenus();
});

// Toggle submenu no sidebar
function toggleSubmenu(event, submenuId) {
  if(event) event.preventDefault();
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
    // Remover listeners antigos
    toggle.removeEventListener('click', handleSubmenuToggle);
    // Adicionar novo listener
    toggle.addEventListener('click', handleSubmenuToggle);
  });
}

// Handler para submenu toggle
function handleSubmenuToggle(event) {
  event.preventDefault();
  
  // Extrair o ID do data-submenu ou do onclick
  var submenuId = this.getAttribute('data-submenu');
  if(!submenuId) {
    // Tentar extrair do onclick
    var onclickAttr = this.getAttribute('onclick');
    if(onclickAttr) {
      var match = onclickAttr.match(/toggleSubmenu\([^,]*,\s*'([^']+)'\)/);
      if(match) submenuId = match[1];
    }
  }
  
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
