<?php
// Homepage - CyberCore
$page_title = 'CyberCore – Alojamento Web & Soluções Digitais em Portugal';
$page_description = 'Alojamento web profissional em Portugal. Servidores dedicados, VPS Cloud, domínios e SSL. Suporte 24/7 em português.';
$extra_css = ['/assets/css/home.css'];

require_once __DIR__ . '/../inc/header.php';
?>

<!-- Hero Section -->
<section class="hero">
  <div class="container">
    <div class="hero-content">
      <span class="hero-badge">🚀 Novos planos com até 50% de desconto</span>
      <h1 class="hero-title">
        Alojamento Web Profissional<br>
        <span class="gradient-text">em Portugal</span>
      </h1>
      <p class="hero-subtitle">
        Infraestrutura de alto desempenho com suporte técnico 24/7 em português. 
        A escolha de milhares de empresas portuguesas.
      </p>
      <div class="hero-actions">
        <a href="#plans" class="btn btn-primary btn-lg">Ver Planos</a>
        <a href="/contact.php" class="btn btn-outline btn-lg">Falar com Vendas</a>
      </div>
      <div class="hero-stats">
        <div class="stat">
          <strong>99.9%</strong>
          <span>Uptime</span>
        </div>
        <div class="stat">
          <strong>24/7</strong>
          <span>Suporte</span>
        </div>
        <div class="stat">
          <strong>5000+</strong>
          <span>Clientes</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Hosting Plans -->
<section id="plans" class="plans-section">
  <div class="container">
    <div class="section-header">
      <h2>Planos de Alojamento Web</h2>
      <p>Escolha o plano ideal para o seu projeto</p>
    </div>
    
    <div class="plans-grid">
      <article class="plan-card">
        <div class="plan-header">
          <h3>Starter</h3>
          <div class="plan-price">
            <span class="price">4,99€</span>
            <span class="period">/mês</span>
          </div>
        </div>
        <ul class="plan-features">
          <li>10 GB SSD NVMe</li>
          <li>100 GB Tráfego</li>
          <li>1 Website</li>
          <li>5 Contas Email</li>
          <li>SSL Grátis</li>
          <li>Backups Diários</li>
        </ul>
        <a href="/hosting.php?plan=starter" class="btn btn-outline btn-block">Escolher Plano</a>
      </article>

      <article class="plan-card plan-featured">
        <div class="plan-badge">Mais Popular</div>
        <div class="plan-header">
          <h3>Business</h3>
          <div class="plan-price">
            <span class="price">9,99€</span>
            <span class="period">/mês</span>
          </div>
        </div>
        <ul class="plan-features">
          <li>50 GB SSD NVMe</li>
          <li>500 GB Tráfego</li>
          <li>Websites Ilimitados</li>
          <li>Emails Ilimitados</li>
          <li>SSL Grátis</li>
          <li>Backups Diários</li>
          <li>CDN Grátis</li>
          <li>Suporte Prioritário</li>
        </ul>
        <a href="/hosting.php?plan=business" class="btn btn-primary btn-block">Escolher Plano</a>
      </article>

      <article class="plan-card">
        <div class="plan-header">
          <h3>Pro</h3>
          <div class="plan-price">
            <span class="price">19,99€</span>
            <span class="period">/mês</span>
          </div>
        </div>
        <ul class="plan-features">
          <li>100 GB SSD NVMe</li>
          <li>1 TB Tráfego</li>
          <li>Websites Ilimitados</li>
          <li>Emails Ilimitados</li>
          <li>SSL Grátis</li>
          <li>Backups Diários</li>
          <li>CDN Grátis</li>
          <li>Staging Environment</li>
          <li>Suporte Premium</li>
        </ul>
        <a href="/hosting.php?plan=pro" class="btn btn-outline btn-block">Escolher Plano</a>
      </article>
    </div>
  </div>
</section>

<!-- VPS & Cloud -->
<section class="vps-section">
  <div class="container">
    <div class="vps-content">
      <div class="vps-text">
        <h2>VPS & Cloud Computing</h2>
        <p class="lead">Máximo desempenho e controlo total sobre os seus recursos.</p>
        <ul class="features-list">
          <li>
            <svg class="icon" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
            <div>
              <strong>SSD NVMe</strong>
              <span>Velocidades até 10x superiores</span>
            </div>
          </li>
          <li>
            <svg class="icon" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
            <div>
              <strong>Snapshots Gratuitos</strong>
              <span>Recuperação instantânea</span>
            </div>
          </li>
          <li>
            <svg class="icon" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
            <div>
              <strong>IP Dedicado</strong>
              <span>IPv4 e IPv6 incluídos</span>
            </div>
          </li>
          <li>
            <svg class="icon" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
            <div>
              <strong>Acesso Root</strong>
              <span>Controlo total do servidor</span>
            </div>
          </li>
        </ul>
        <a href="/vps.php" class="btn btn-primary">Explorar VPS</a>
      </div>
      <div class="vps-pricing">
        <div class="vps-card">
          <h4>VPS Basic</h4>
          <div class="vps-specs">
            <span>2 vCPU</span>
            <span>4 GB RAM</span>
            <span>50 GB NVMe</span>
          </div>
          <div class="vps-price">
            <span class="price">14,99€</span>
            <span class="period">/mês</span>
          </div>
        </div>
        <div class="vps-card">
          <h4>VPS Pro</h4>
          <div class="vps-specs">
            <span>4 vCPU</span>
            <span>8 GB RAM</span>
            <span>100 GB NVMe</span>
          </div>
          <div class="vps-price">
            <span class="price">29,99€</span>
            <span class="period">/mês</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Features -->
<section class="features-section">
  <div class="container">
    <div class="section-header">
      <h2>Porquê Escolher a CyberCore?</h2>
      <p>Tecnologia de ponta ao serviço do seu sucesso</p>
    </div>
    
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">⚡</div>
        <h3>Velocidade Extrema</h3>
        <p>Servidores NVMe com CDN integrado para carregamentos instantâneos em qualquer parte do mundo.</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">🔒</div>
        <h3>Segurança Avançada</h3>
        <p>Proteção DDoS, firewall dedicado e certificados SSL gratuitos para máxima segurança.</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">🇵🇹</div>
        <h3>Datacenter em Portugal</h3>
        <p>Infraestrutura nacional com conformidade GDPR e latência mínima para visitantes portugueses.</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">💬</div>
        <h3>Suporte 24/7</h3>
        <p>Equipa técnica disponível 24 horas por dia, 7 dias por semana, em português.</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">📊</div>
        <h3>Painel de Controlo</h3>
        <p>Interface intuitiva para gerir todos os seus serviços de forma simples e eficiente.</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">🔄</div>
        <h3>Backups Automáticos</h3>
        <p>Cópias de segurança diárias automáticas com retenção de 30 dias incluídas.</p>
      </div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section class="testimonials-section">
  <div class="container">
    <div class="section-header">
      <h2>O Que Dizem os Nossos Clientes</h2>
      <p>Mais de 5.000 empresas confiam na CyberCore</p>
    </div>
    
    <div class="testimonials-grid">
      <article class="testimonial-card">
        <div class="testimonial-rating">★★★★★</div>
        <p class="testimonial-text">
          "Migrei o meu e-commerce para a CyberCore e a diferença foi notória. Site muito mais rápido 
          e o suporte técnico é excecional. Recomendo!"
        </p>
        <div class="testimonial-author">
          <strong>João Silva</strong>
          <span>CEO, TechStore</span>
        </div>
      </article>
      
      <article class="testimonial-card">
        <div class="testimonial-rating">★★★★★</div>
        <p class="testimonial-text">
          "Excelente serviço! Uptime de 100% nos últimos 12 meses e sempre que precisei o suporte 
          respondeu em minutos. Vale cada cêntimo."
        </p>
        <div class="testimonial-author">
          <strong>Maria Santos</strong>
          <span>Diretora, WebDesign Pro</span>
        </div>
      </article>
      
      <article class="testimonial-card">
        <div class="testimonial-rating">★★★★★</div>
        <p class="testimonial-text">
          "A melhor decisão que tomámos foi mudar para a CyberCore. Performance incrível, 
          preços justos e suporte em português que realmente ajuda."
        </p>
        <div class="testimonial-author">
          <strong>Pedro Costa</strong>
          <span>Founder, StartupPT</span>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- CTA Final -->
<section class="cta-section">
  <div class="container">
    <div class="cta-content">
      <h2>Pronto para Começar?</h2>
      <p>Junte-se a milhares de empresas que confiam na CyberCore para os seus projetos online.</p>
      <div class="cta-actions">
        <a href="/hosting.php" class="btn btn-primary btn-lg">Ver Todos os Planos</a>
        <a href="/contact.php" class="btn btn-outline-light btn-lg">Falar com Especialista</a>
      </div>
      <p class="cta-note">
        ✓ Migração gratuita &nbsp;&nbsp;|&nbsp;&nbsp; ✓ Sem compromisso &nbsp;&nbsp;|&nbsp;&nbsp; ✓ Garantia 30 dias
      </p>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
