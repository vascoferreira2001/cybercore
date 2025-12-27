<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exemplos UI Components - CyberCore</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
</head>
<body>
  <div class="container" style="padding: 40px 20px; max-width: 900px;">
    <h1>CyberCore UI Components</h1>
    <p class="muted">Biblioteca de componentes reutilizáveis para toda a plataforma</p>
    
    <hr style="margin: 40px 0; border: none; border-top: 1px solid #e9ecef;">
    
    <!-- Toast Examples -->
    <section style="margin-bottom: 40px;">
      <h2>Toast Notifications</h2>
      <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 20px;">
        <button class="btn primary" onclick="CyberCore.Toast.success('Operação realizada com sucesso!')">Success Toast</button>
        <button class="btn danger" onclick="CyberCore.Toast.error('Ocorreu um erro. Tente novamente.')">Error Toast</button>
        <button class="btn" onclick="CyberCore.Toast.warning('Atenção: Esta ação é irreversível.')">Warning Toast</button>
        <button class="btn ghost" onclick="CyberCore.Toast.info('Dica: Pode personalizar a duração dos toasts.')">Info Toast</button>
      </div>
      
      <pre style="margin-top: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px; overflow-x: auto;"><code>// Uso
CyberCore.Toast.success('Mensagem de sucesso');
CyberCore.Toast.error('Mensagem de erro', 6000); // 6 segundos
CyberCore.Toast.warning('Aviso importante');
CyberCore.Toast.info('Informação útil');</code></pre>
    </section>
    
    <!-- Modal Examples -->
    <section style="margin-bottom: 40px;">
      <h2>Modal Dialogs</h2>
      <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 20px;">
        <button class="btn primary" onclick="showSimpleModal()">Simple Modal</button>
        <button class="btn" onclick="showLargeModal()">Large Modal</button>
        <button class="btn danger" onclick="showConfirmDialog()">Confirm Dialog</button>
      </div>
      
      <pre style="margin-top: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px; overflow-x: auto;"><code>// Modal simples
CyberCore.Modal.open({
  title: 'Título do Modal',
  content: '&lt;p&gt;Conteúdo HTML aqui&lt;/p&gt;',
  size: 'medium'
});

// Diálogo de confirmação
const confirmed = await CyberCore.Modal.confirm({
  title: 'Confirmar ação',
  message: 'Tem a certeza que deseja continuar?',
  confirmText: 'Sim, confirmar',
  confirmType: 'danger'
});

if (confirmed) {
  // User clicked confirm
}</code></pre>
    </section>
    
    <!-- Loading Examples -->
    <section style="margin-bottom: 40px;">
      <h2>Loading States</h2>
      <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 20px;">
        <button class="btn primary" onclick="showLoadingDemo()">Show Loading</button>
        <button class="btn loading">Button Loading</button>
      </div>
      
      <pre style="margin-top: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px; overflow-x: auto;"><code>// Full page loading
CyberCore.Loading.show('A processar...');
// ... do async work
CyberCore.Loading.hide();

// Button loading state
button.classList.add('loading');
// ... do async work
button.classList.remove('loading');</code></pre>
    </section>
    
    <!-- Form Validation Examples -->
    <section style="margin-bottom: 40px;">
      <h2>Form Validation</h2>
      <form data-validate style="max-width: 500px; margin-top: 20px;">
        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" required placeholder="seu@email.com">
        </div>
        
        <div class="form-group">
          <label>Website</label>
          <input type="url" name="website" data-validate="url" placeholder="https://exemplo.com">
        </div>
        
        <div class="form-group">
          <label>Mensagem * (mín. 10 caracteres)</label>
          <textarea name="message" required minlength="10" rows="4"></textarea>
        </div>
        
        <button type="submit" class="btn primary">Validar Formulário</button>
      </form>
      
      <pre style="margin-top: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px; overflow-x: auto;"><code>// Validação automática com data-validate
&lt;form data-validate&gt;
  &lt;input type="email" required&gt;
  &lt;input type="url" data-validate="url"&gt;
  &lt;textarea required minlength="10"&gt;&lt;/textarea&gt;
&lt;/form&gt;

// Validação manual
if (CyberCore.Form.validate(form)) {
  // Form is valid
}</code></pre>
    </section>
    
    <!-- AJAX Examples -->
    <section>
      <h2>AJAX Helper</h2>
      <button class="btn primary" onclick="ajaxDemo()">Test AJAX</button>
      
      <pre style="margin-top: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px; overflow-x: auto;"><code>// AJAX com loading automático
CyberCore.ajax('/api/endpoint', {
  method: 'POST',
  data: { key: 'value' },
  showLoading: true,
  loadingMessage: 'A guardar...',
  onSuccess: (result) => {
    CyberCore.Toast.success('Guardado com sucesso!');
  },
  onError: (error) => {
    console.error('Error:', error);
  }
});</code></pre>
    </section>
  </div>
  
  <script src="/assets/js/components.js"></script>
  <script>
    function showSimpleModal() {
      CyberCore.Modal.open({
        title: 'Exemplo de Modal',
        content: '<p>Este é um modal simples com conteúdo HTML.</p><p>Pode incluir qualquer HTML aqui, incluindo formulários, imagens, etc.</p>',
        size: 'medium'
      });
    }
    
    function showLargeModal() {
      CyberCore.Modal.open({
        title: 'Modal Grande',
        content: '<p>Este modal é maior e pode conter mais conteúdo.</p>' + '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. '.repeat(10) + '</p>',
        size: 'large',
        footer: '<button class="btn ghost" onclick="CyberCore.Modal.close(this.closest(\'.modal-overlay\'))">Fechar</button><button class="btn primary">Guardar</button>'
      });
    }
    
    async function showConfirmDialog() {
      const confirmed = await CyberCore.Modal.confirm({
        title: 'Eliminar Item',
        message: 'Tem a certeza que deseja eliminar este item? Esta ação não pode ser desfeita.',
        confirmText: 'Sim, eliminar',
        cancelText: 'Cancelar',
        confirmType: 'danger'
      });
      
      if (confirmed) {
        CyberCore.Toast.success('Item eliminado com sucesso!');
      } else {
        CyberCore.Toast.info('Operação cancelada');
      }
    }
    
    function showLoadingDemo() {
      CyberCore.Loading.show('A processar o seu pedido...');
      
      setTimeout(() => {
        CyberCore.Loading.hide();
        CyberCore.Toast.success('Processamento concluído!');
      }, 2000);
    }
    
    function ajaxDemo() {
      CyberCore.Loading.show('A fazer pedido AJAX...');
      
      // Simula AJAX call
      setTimeout(() => {
        CyberCore.Loading.hide();
        CyberCore.Toast.success('Pedido AJAX bem-sucedido! (simulado)');
      }, 1500);
    }
    
    // Handle form submission
    document.querySelector('form[data-validate]').addEventListener('submit', (e) => {
      e.preventDefault();
      CyberCore.Toast.success('Formulário válido! Dados prontos para envio.');
    });
  </script>
</body>
</html>
