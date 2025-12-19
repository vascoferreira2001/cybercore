// JS mínimo para validação do formulário de registo
document.addEventListener('DOMContentLoaded', function(){
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
