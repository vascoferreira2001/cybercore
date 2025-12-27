<?php 
/**
 * CyberCore - Contacto
 * Enhanced with modern design and form validation
 */
include __DIR__ . '/../inc/header.php'; 
?>

<main class="marketing contact-page">
  <!-- Hero Section -->
  <section class="hero hero-small">
    <div class="container">
      <div class="hero-copy-centered">
        <h1>Fale Connosco</h1>
        <p class="muted">A nossa equipa está pronta para ajudar. Resposta garantida em 24h.</p>
      </div>
    </div>
  </section>

  <!-- Contact Options -->
  <section class="contact-options-section">
    <div class="container">
      <div class="contact-options-grid">
        <div class="contact-option">
          <div class="contact-icon">
            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
          </div>
          <h3>Email</h3>
          <p><a href="mailto:suporte@cybercore.pt">suporte@cybercore.pt</a></p>
          <span class="muted">Resposta em 24h</span>
        </div>

        <div class="contact-option">
          <div class="contact-icon">
            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
          </div>
          <h3>Telefone</h3>
          <p><a href="tel:+351210000000">+351 210 000 000</a></p>
          <span class="muted">Seg-Sex 9h-18h</span>
        </div>

        <div class="contact-option">
          <div class="contact-icon">
            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
          </div>
          <h3>Live Chat</h3>
          <p><a href="#" onclick="openLiveChat(); return false;">Iniciar Conversa</a></p>
          <span class="muted">Disponível 24/7</span>
        </div>

        <div class="contact-option">
          <div class="contact-icon">
            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <h3>Escritório</h3>
          <p>Lisboa, Portugal</p>
          <span class="muted">Visitas por marcação</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Form -->
  <section class="contact-form-section">
    <div class="container">
      <div class="form-container">
        <div class="form-header">
          <h2>Envie-nos uma Mensagem</h2>
          <p class="muted">Preencha o formulário e entraremos em contacto brevemente</p>
        </div>

        <form id="contactForm" data-validate method="POST" action="/contact_submit.php">
          <!-- Honeypot for bots -->
          <input type="text" name="website" value="" style="display:none" aria-hidden="true" tabindex="-1">
          
          <div class="form-row">
            <div class="form-group">
              <label>Nome Completo *</label>
              <input type="text" name="name" required placeholder="João Silva">
            </div>

            <div class="form-group">
              <label>Email *</label>
              <input type="email" name="email" required placeholder="joao@empresa.pt">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Telefone</label>
              <input type="tel" name="phone" placeholder="+351 912 345 678">
            </div>

            <div class="form-group">
              <label>Empresa</label>
              <input type="text" name="company" placeholder="Nome da empresa">
            </div>
          </div>

          <div class="form-group">
            <label>Assunto *</label>
            <select name="subject" required>
              <option value="">Selecione um assunto</option>
              <option value="hosting">Alojamento Web</option>
              <option value="vps">Servidores VPS</option>
              <option value="dedicated">Servidores Dedicados</option>
              <option value="domains">Domínios</option>
              <option value="email">Email Profissional</option>
              <option value="support">Suporte Técnico</option>
              <option value="billing">Questões Financeiras</option>
              <option value="other">Outro</option>
            </select>
          </div>

          <div class="form-group">
            <label>Mensagem * (mínimo 20 caracteres)</label>
            <textarea name="message" required minlength="20" rows="6" placeholder="Descreva o seu pedido ou questão..."></textarea>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="consent" required>
              <span>Concordo com a <a href="/privacy.php" target="_blank">Política de Privacidade</a> e autorizo o tratamento dos meus dados *</span>
            </label>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn primary large">
              <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
              </svg>
              Enviar Mensagem
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="faq-section">
    <div class="container">
      <div class="section-header-centered">
        <h2>Perguntas Frequentes</h2>
      </div>
      
      <div class="faq-grid">
        <div class="faq-item">
          <h3>Qual o tempo de resposta?</h3>
          <p>Garantimos resposta em 24 horas úteis para todos os contactos. Pedidos urgentes são prioritários e normalmente respondidos em poucas horas.</p>
        </div>

        <div class="faq-item">
          <h3>Oferecem suporte 24/7?</h3>
          <p>Sim! O nosso suporte técnico está disponível 24 horas por dia, 7 dias por semana através de tickets, live chat e telefone de emergência.</p>
        </div>

        <div class="faq-item">
          <h3>Posso agendar uma demonstração?</h3>
          <p>Claro! Entre em contacto connosco e agendaremos uma demonstração personalizada das nossas soluções para a sua empresa.</p>
        </div>

        <div class="faq-item">
          <h3>Como funcionam os SLA?</h3>
          <p>Oferecemos SLA de 99.99% de uptime em todos os planos. Em caso de incumprimento, aplicam-se créditos automáticos conforme contrato.</p>
        </div>
      </div>
    </div>
  </section>
</main>

<link rel="stylesheet" href="/assets/css/pages/contact.css">

<script>
// Enhanced contact form handling
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('contactForm');
  
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate form
    if (!CyberCore.Form.validate(form)) {
      CyberCore.Toast.error('Por favor corrija os erros no formulário');
      return;
    }
    
    // Get form data
    const formData = new FormData(form);
    
    // Check honeypot
    if (formData.get('website')) {
      return; // Bot detected, silently fail
    }
    
    // Show loading
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    try {
      const response = await fetch('/contact_submit.php', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        CyberCore.Toast.success('Mensagem enviada com sucesso! Responderemos em breve.');
        CyberCore.Form.reset(form);
        
        // Optional: redirect after success
        setTimeout(() => {
          window.location.href = '/';
        }, 2000);
      } else {
        CyberCore.Toast.error(result.message || 'Erro ao enviar mensagem. Tente novamente.');
      }
    } catch (error) {
      console.error('Submit error:', error);
      CyberCore.Toast.error('Erro de conexão. Verifique a sua internet e tente novamente.');
    } finally {
      submitBtn.classList.remove('loading');
      submitBtn.disabled = false;
    }
  });
});

function openLiveChat() {
  // Integrate with your live chat system (e.g., Crisp, Tawk.to, Intercom)
  CyberCore.Toast.info('A abrir live chat...');
  // window.$crisp.push(['do', 'chat:open']);
}
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>
