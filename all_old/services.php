<?php 
/**
 * CyberCore - Serviços (Website Público)
 * Página detalhada de todos os serviços oferecidos
 */
include __DIR__ . '/inc/header.php'; 
?>

<main class="marketing services-page">
  <!-- Hero Section -->
  <section class="hero hero-centered">
    <div class="container">
      <div class="hero-copy-centered">
        <div class="eyebrow">Portfólio completo de soluções</div>
        <h1>Serviços de infraestrutura cloud e digital</h1>
        <p class="muted">Do alojamento web a servidores dedicados, passando por desenvolvimento e gestão de redes sociais. Tudo o que precisa para crescer online, com suporte humano 24/7.</p>
      </div>
    </div>
  </section>

  <!-- Services Grid -->
  <section class="services-grid-section">
    <div class="container">
      
      <!-- Web Hosting -->
      <article class="service-card featured">
        <div class="service-icon">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="4" width="32" height="32" rx="2"/>
            <path d="M4 12h32M12 4v32"/>
          </svg>
        </div>
        <div class="service-content">
          <h2>Alojamento Web</h2>
          <p class="service-desc">Hosting de alta performance com SSD NVMe, certificados SSL gratuitos e backups diários. Ideal para WordPress, WooCommerce, lojas online e sites empresariais.</p>
          
          <div class="service-features">
            <h3>Inclui:</h3>
            <ul>
              <li>✓ Certificados SSL Let's Encrypt automáticos</li>
              <li>✓ Backups diários com retenção de 30 dias</li>
              <li>✓ CDN integrado para performance global</li>
              <li>✓ Firewall WAF e proteção DDoS</li>
              <li>✓ Instalador automático de WordPress</li>
              <li>✓ Monitorização 24/7 com SLA 99.9%</li>
              <li>✓ Painel cPanel intuitivo</li>
              <li>✓ Suporte técnico em português</li>
            </ul>
          </div>

          <div class="service-plans">
            <div class="plan-card">
              <div class="plan-name">Starter</div>
              <div class="plan-price">2,99€<span>/mês</span></div>
              <ul class="plan-specs">
                <li>1 website</li>
                <li>10 GB SSD NVMe</li>
                <li>Tráfego ilimitado</li>
                <li>5 contas de email</li>
              </ul>
            </div>
            <div class="plan-card highlighted">
              <div class="plan-badge">Mais Popular</div>
              <div class="plan-name">Business</div>
              <div class="plan-price">9,99€<span>/mês</span></div>
              <ul class="plan-specs">
                <li>5 websites</li>
                <li>50 GB SSD NVMe</li>
                <li>Tráfego ilimitado</li>
                <li>25 contas de email</li>
              </ul>
            </div>
            <div class="plan-card">
              <div class="plan-name">Enterprise</div>
              <div class="plan-price">29,99€<span>/mês</span></div>
              <ul class="plan-specs">
                <li>Websites ilimitados</li>
                <li>200 GB SSD NVMe</li>
                <li>Tráfego ilimitado</li>
                <li>Email ilimitado</li>
              </ul>
            </div>
          </div>

          <div class="service-actions">
            <a href="/pricing.php#hosting" class="btn primary">Ver Planos Completos</a>
            <a href="/contact.php" class="btn outline">Falar com Vendas</a>
          </div>
        </div>
      </article>

      <!-- Email Hosting -->
      <article class="service-card">
        <div class="service-icon">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 8l16 12 16-12M4 8v24h32V8H4z"/>
          </svg>
        </div>
        <div class="service-content">
          <h2>Alojamento de Email Profissional</h2>
          <p class="service-desc">Email empresarial seguro com o seu domínio. Anti-spam avançado, webmail moderno e sincronização em todos os dispositivos.</p>
          
          <div class="service-features">
            <h3>Funcionalidades:</h3>
            <ul>
              <li>✓ Domínio personalizado (@suaempresa.pt)</li>
              <li>✓ Webmail Roundcube e Horde</li>
              <li>✓ IMAP/SMTP/POP3 compatível</li>
              <li>✓ Anti-spam e antivírus integrados</li>
              <li>✓ Calendário e contactos partilhados</li>
              <li>✓ Sincronização ActiveSync</li>
              <li>✓ Armazenamento de 10 GB a 50 GB por caixa</li>
              <li>✓ Proteção DKIM, SPF e DMARC</li>
            </ul>
          </div>

          <div class="service-pricing-summary">
            <div class="price-from">A partir de <strong>4,99€/mês</strong></div>
            <p class="muted">por caixa de email</p>
          </div>

          <div class="service-actions">
            <a href="/pricing.php#email" class="btn primary">Ver Preços</a>
            <a href="/contact.php" class="btn ghost">Pedir Orçamento</a>
          </div>
        </div>
      </article>

      <!-- Domains -->
      <article class="service-card">
        <div class="service-icon">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="20" cy="20" r="16"/>
            <path d="M4 20h32M20 4a24 24 0 0110 16 24 24 0 01-10 16 24 24 0 01-10-16 24 24 0 0110-16"/>
          </svg>
        </div>
        <div class="service-content">
          <h2>Registo e Gestão de Domínios</h2>
          <p class="service-desc">Registe, transfira e gira os seus domínios num painel unificado. DNS Anycast ultra-rápido com proteção DNSSEC.</p>
          
          <div class="service-features">
            <h3>Inclui:</h3>
            <ul>
              <li>✓ Registo de .pt, .com, .eu e +400 TLDs</li>
              <li>✓ Transferências gratuitas</li>
              <li>✓ Gestão DNS avançada</li>
              <li>✓ DNSSEC para segurança adicional</li>
              <li>✓ Proteção de privacidade WHOIS</li>
              <li>✓ Renovação automática configurável</li>
              <li>✓ API para gestão programática</li>
            </ul>
          </div>

          <div class="domain-pricing">
            <div class="domain-tld">
              <span class="tld">.pt</span>
              <span class="price">9,99€/ano</span>
            </div>
            <div class="domain-tld">
              <span class="tld">.com</span>
              <span class="price">12,99€/ano</span>
            </div>
            <div class="domain-tld">
              <span class="tld">.eu</span>
              <span class="price">8,99€/ano</span>
            </div>
          </div>

          <div class="service-actions">
            <a href="/pricing.php#domains" class="btn primary">Ver Todos os TLDs</a>
            <a href="/manager/domains.php" class="btn outline">Procurar Domínio</a>
          </div>
        </div>
      </article>

      <!-- VPS Servers -->
      <article class="service-card featured">
        <div class="service-icon">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="6" width="32" height="8" rx="2"/>
            <rect x="4" y="18" width="32" height="8" rx="2"/>
            <rect x="4" y="30" width="32" height="8" rx="2"/>
          </svg>
        </div>
        <div class="service-content">
          <h2>Servidores VPS</h2>
          <p class="service-desc">Virtual Private Servers com recursos dedicados, SSD NVMe e acesso root completo. Escolha entre Linux ou Windows Server.</p>
          
          <div class="service-features">
            <h3>Especificações:</h3>
            <ul>
              <li>✓ SSD NVMe ultra-rápido</li>
              <li>✓ CPU Intel Xeon / AMD EPYC</li>
              <li>✓ Rede 1 Gbps garantida</li>
              <li>✓ IPv4 e IPv6 incluídos</li>
              <li>✓ Painel de controlo Proxmox/SolusVM</li>
              <li>✓ Snapshots e backups</li>
              <li>✓ Reinstalação automática de SO</li>
              <li>✓ Console VNC/KVM</li>
            </ul>
          </div>

          <div class="service-plans">
            <div class="plan-card">
              <div class="plan-name">VPS Basic</div>
              <div class="plan-price">19,99€<span>/mês</span></div>
              <ul class="plan-specs">
                <li>2 vCPU</li>
                <li>4 GB RAM</li>
                <li>80 GB SSD NVMe</li>
                <li>2 TB tráfego</li>
              </ul>
            </div>
            <div class="plan-card highlighted">
              <div class="plan-badge">Recomendado</div>
              <div class="plan-name">VPS Pro</div>
              <div class="plan-price">49,99€<span>/mês</span></div>
              <ul class="plan-specs">
                <li>4 vCPU</li>
                <li>8 GB RAM</li>
                <li>160 GB SSD NVMe</li>
                <li>4 TB tráfego</li>
              </ul>
            </div>
            <div class="plan-card">
              <div class="plan-name">VPS Elite</div>
              <div class="plan-price">99,99€<span>/mês</span></div>
              <ul class="plan-specs">
                <li>8 vCPU</li>
                <li>16 GB RAM</li>
                <li>320 GB SSD NVMe</li>
                <li>8 TB tráfego</li>
              </ul>
            </div>
          </div>

          <div class="service-actions">
            <a href="/pricing.php#vps" class="btn primary">Configurar VPS</a>
            <a href="/contact.php" class="btn outline">Falar com Especialista</a>
          </div>
        </div>
      </article>

      <!-- Dedicated Servers -->
      <article class="service-card">
        <div class="service-icon">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="8" width="32" height="24" rx="2"/>
            <path d="M12 16h16M12 20h8"/>
          </svg>
        </div>
        <div class="service-content">
          <h2>Servidores Dedicados</h2>
          <p class="service-desc">Hardware físico dedicado para cargas de trabalho exigentes. Configurações personalizadas, conectividade premium e gestão opcional.</p>
          
          <div class="service-features">
            <h3>Vantagens:</h3>
            <ul>
              <li>✓ 100% dos recursos dedicados</li>
              <li>✓ Processadores Intel Xeon / AMD EPYC</li>
              <li>✓ RAID por hardware</li>
              <li>✓ Uplink 1 Gbps / 10 Gbps</li>
              <li>✓ Gestão opcional (managed/unmanaged)</li>
              <li>✓ IPMI/iLO para gestão remota</li>
              <li>✓ DDoS protection incluída</li>
              <li>✓ SLA 99.99% uptime</li>
            </ul>
          </div>

          <div class="service-pricing-summary">
            <div class="price-from">A partir de <strong>199€/mês</strong></div>
            <p class="muted">Configurações personalizadas disponíveis</p>
          </div>

          <div class="service-actions">
            <a href="/contact.php" class="btn primary">Pedir Orçamento</a>
            <a href="/pricing.php#dedicated" class="btn ghost">Ver Exemplos</a>
          </div>
        </div>
      </article>

      <!-- Website Maintenance -->
      <article class="service-card">
        <div class="service-icon">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 4v8m0 16v8m-8-16h8m8 0h-8M8 12l5.66 5.66M26.34 26.34L32 32M32 12l-5.66 5.66M10.34 26.34L4 32"/>
            <circle cx="20" cy="20" r="4"/>
          </svg>
        </div>
        <div class="service-content">
          <h2>Manutenção de Websites</h2>
          <p class="service-desc">Manutenção proativa do seu website. Atualizações, backups, segurança, otimização e suporte técnico dedicado.</p>
          
          <div class="service-features">
            <h3>Serviço inclui:</h3>
            <ul>
              <li>✓ Atualizações de WordPress, plugins e temas</li>
              <li>✓ Backups semanais com restauro gratuito</li>
              <li>✓ Monitorização de uptime 24/7</li>
              <li>✓ Remoção de malware e hardening</li>
              <li>✓ Otimização de performance</li>
              <li>✓ Pequenas alterações de conteúdo</li>
              <li>✓ Relatórios mensais</li>
              <li>✓ Suporte prioritário</li>
            </ul>
          </div>

          <div class="service-pricing-summary">
            <div class="price-from">A partir de <strong>49€/mês</strong></div>
            <p class="muted">Planos flexíveis por número de sites</p>
          </div>

          <div class="service-actions">
            <a href="/pricing.php#maintenance" class="btn primary">Ver Planos</a>
            <a href="/contact.php" class="btn outline">Pedir Proposta</a>
          </div>
        </div>
      </article>

      <!-- Web Development -->
      <article class="service-card">
        <div class="service-icon">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 8l-8 8 8 8M28 8l8 8-8 8M24 4l-8 32"/>
          </svg>
        </div>
        <div class="service-content">
          <h2>Desenvolvimento de Websites</h2>
          <p class="service-desc">Criação de websites modernos, rápidos e otimizados para conversão. Do conceito ao lançamento.</p>
          
          <div class="service-features">
            <h3>O que desenvolvemos:</h3>
            <ul>
              <li>✓ Websites institucionais</li>
              <li>✓ Lojas online (WooCommerce, Shopify)</li>
              <li>✓ Plataformas SaaS personalizadas</li>
              <li>✓ Portais de clientes</li>
              <li>✓ Landing pages de conversão</li>
              <li>✓ Progressive Web Apps (PWA)</li>
              <li>✓ APIs e integrações</li>
              <li>✓ Migrações e refactoring</li>
            </ul>
          </div>

          <div class="tech-stack">
            <h4>Tecnologias:</h4>
            <div class="tech-badges">
              <span class="badge">WordPress</span>
              <span class="badge">PHP</span>
              <span class="badge">Laravel</span>
              <span class="badge">React</span>
              <span class="badge">Vue.js</span>
              <span class="badge">Node.js</span>
            </div>
          </div>

          <div class="service-actions">
            <a href="/contact.php" class="btn primary">Iniciar Projeto</a>
            <a href="/pricing.php#development" class="btn outline">Ver Portfolio</a>
          </div>
        </div>
      </article>

      <!-- Social Media Management -->
      <article class="service-card">
        <div class="service-icon">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 8a4 4 0 100-8 4 4 0 000 8zM8 20a4 4 0 100-8 4 4 0 000 8zM32 20a4 4 0 100-8 4 4 0 000 8zM20 32a4 4 0 100-8 4 4 0 000 8zM20 8v8M8 20h8M24 20h8M20 24v8"/>
          </svg>
        </div>
        <div class="service-content">
          <h2>Gestão de Redes Sociais</h2>
          <p class="service-desc">Gestão profissional das suas redes sociais. Conteúdo estratégico, engagement e crescimento orgânico da sua marca.</p>
          
          <div class="service-features">
            <h3>Serviço completo:</h3>
            <ul>
              <li>✓ Estratégia de conteúdo personalizada</li>
              <li>✓ Criação de posts (texto + imagem)</li>
              <li>✓ Agendamento e publicação</li>
              <li>✓ Gestão de comunidade</li>
              <li>✓ Resposta a mensagens e comentários</li>
              <li>✓ Análise e relatórios mensais</li>
              <li>✓ Campanhas pagas (Facebook Ads, Instagram Ads)</li>
              <li>✓ Gestão de reputação online</li>
            </ul>
          </div>

          <div class="social-platforms">
            <h4>Plataformas:</h4>
            <div class="platform-list">
              <span>Facebook</span>
              <span>Instagram</span>
              <span>LinkedIn</span>
              <span>Twitter/X</span>
              <span>TikTok</span>
            </div>
          </div>

          <div class="service-pricing-summary">
            <div class="price-from">A partir de <strong>299€/mês</strong></div>
            <p class="muted">Pacotes por número de redes e posts/mês</p>
          </div>

          <div class="service-actions">
            <a href="/contact.php" class="btn primary">Falar com Especialista</a>
            <a href="/pricing.php#social" class="btn outline">Ver Planos</a>
          </div>
        </div>
      </article>

    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="container">
      <div class="cta-card">
        <h2>Não encontrou o que procura?</h2>
        <p>Temos soluções personalizadas para necessidades específicas. Fale connosco para um orçamento à medida.</p>
        <div class="cta-actions">
          <a href="/contact.php" class="btn primary large">Contactar Equipa de Vendas</a>
          <a href="/pricing.php" class="btn ghost large">Ver Todos os Preços</a>
        </div>
      </div>
    </div>
  </section>

</main>

<link rel="stylesheet" href="/assets/css/pages/services.css">

<?php include __DIR__ . '/inc/footer.php'; ?>
