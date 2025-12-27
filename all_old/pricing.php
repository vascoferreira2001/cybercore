<?php 
/**
 * CyberCore - Pricing (Preços)
 */
include __DIR__ . '/../inc/header.php'; 
?>

<main class="marketing pricing-page">
  <section class="hero hero-centered">
    <div class="container">
      <div class="hero-copy-centered">
        <div class="eyebrow">Preços transparentes</div>
        <h1>Planos para todos os tamanhos</h1>
        <p class="muted">Desde websites pessoais a infraestrutura enterprise.</p>
      </div>
    </div>
  </section>

  <section id="hosting" class="pricing-section">
    <div class="container">
      <div class="section-header-centered">
        <h2>Alojamento Web</h2>
      </div>
      
      <div class="pricing-grid">
        <div class="pricing-card">
          <div class="plan-header">
            <h3>Starter</h3>
            <div class="plan-price">
              <span class="amount">2,99€</span><span class="period">/mês</span>
            </div>
          </div>
          <ul class="plan-features">
            <li>1 website</li>
            <li>10 GB SSD NVMe</li>
            <li>SSL gratuito</li>
            <li>Backups diários</li>
          </ul>
          <a href="/manager/register.php?plan=starter" class="btn primary">Começar</a>
        </div>

        <div class="pricing-card featured">
          <div class="plan-badge">Popular</div>
          <div class="plan-header">
            <h3>Business</h3>
            <div class="plan-price">
              <span class="amount">9,99€</span><span class="period">/mês</span>
            </div>
          </div>
          <ul class="plan-features">
            <li>5 websites</li>
            <li>50 GB SSD</li>
            <li>SSL + CDN</li>
            <li>Suporte prioritário</li>
          </ul>
          <a href="/manager/register.php?plan=business" class="btn primary">Escolher</a>
        </div>

        <div class="pricing-card">
          <div class="plan-header">
            <h3>Enterprise</h3>
            <div class="plan-price">
              <span class="amount">29,99€</span><span class="period">/mês</span>
            </div>
          </div>
          <ul class="plan-features">
            <li>Ilimitado</li>
            <li>200 GB SSD</li>
            <li>IP dedicado</li>
            <li>Suporte 24/7</li>
          </ul>
          <a href="/manager/register.php?plan=enterprise" class="btn primary">Escolher</a>
        </div>
      </div>
    </div>
  </section>

  <section id="vps" class="pricing-section alt">
    <div class="container">
      <div class="section-header-centered">
        <h2>Servidores VPS</h2>
      </div>
      
      <div class="pricing-grid">
        <div class="pricing-card">
          <h3>VPS Basic</h3>
          <div class="plan-price"><span class="amount">19,99€</span>/mês</div>
          <ul class="plan-features">
            <li>2 vCPU</li>
            <li>4 GB RAM</li>
            <li>80 GB SSD</li>
          </ul>
          <a href="/contact.php" class="btn primary">Configurar</a>
        </div>
        <div class="pricing-card">
          <h3>VPS Pro</h3>
          <div class="plan-price"><span class="amount">49,99€</span>/mês</div>
          <ul class="plan-features">
            <li>4 vCPU</li>
            <li>8 GB RAM</li>
            <li>160 GB SSD</li>
          </ul>
          <a href="/contact.php" class="btn primary">Configurar</a>
        </div>
        <div class="pricing-card">
          <h3>VPS Elite</h3>
          <div class="plan-price"><span class="amount">99,99€</span>/mês</div>
          <ul class="plan-features">
            <li>8 vCPU</li>
            <li>16 GB RAM</li>
            <li>320 GB SSD</li>
          </ul>
          <a href="/contact.php" class="btn primary">Configurar</a>
        </div>
      </div>
    </div>
  </section>

  <section class="cta-section">
    <div class="container">
      <div class="cta-card">
        <h2>Pronto para começar?</h2>
        <p>30 dias de garantia em todos os planos.</p>
        <a href="/manager/register.php" class="btn primary large">Criar Conta</a>
      </div>
    </div>
  </section>

</main>

<link rel="stylesheet" href="/assets/css/pages/pricing.css">

<?php include __DIR__ . '/inc/footer.php'; ?>
