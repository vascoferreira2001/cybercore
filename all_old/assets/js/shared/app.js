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
});

// Toggle submenu - função global (fallback)
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

// Inicializar submenus simples
function initializeSubmenus() {
  var submenuToggles = document.querySelectorAll('.submenu-toggle');
  submenuToggles.forEach(function(toggle) {
    // Adicionar listener uma única vez
    toggle.addEventListener('click', function(event) {
      event.preventDefault();
      event.stopPropagation();
      
      var submenuId = this.getAttribute('data-submenu');
      if(submenuId) {
        var submenu = document.getElementById(submenuId + '-submenu');
        if(submenu) {
          submenu.classList.toggle('active');
        }
      }
      return false;
    });
  });
}
