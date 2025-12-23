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
});

// Toggle submenu no sidebar
function toggleSubmenu(event, submenuId) {
  event.preventDefault();
  var submenu = document.getElementById(submenuId + '-submenu');
  if(submenu) {
    submenu.classList.toggle('active');
  }
}

