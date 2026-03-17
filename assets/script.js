async function loadFragment(targetId, fragmentPath) {
  const target = document.getElementById(targetId);
  if (!target) {
    return;
  }

  try {
    const withBust = `${fragmentPath}?v=20260309`;
    const response = await fetch(withBust, { cache: 'no-store' });
    if (!response.ok) {
      throw new Error(`Falha ao carregar ${fragmentPath}`);
    }

    target.innerHTML = await response.text();

    if (targetId === 'site-header' && !target.querySelector('.desktop-nav')) {
      const retry = await fetch(withBust, { cache: 'reload' });
      if (retry.ok) {
        target.innerHTML = await retry.text();
      }
    }
  } catch (error) {
    console.error(error);
  }
}

function setCurrentYear() {
  const yearEl = document.getElementById('year');
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }
}

function setActiveNavLink() {
  const path = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('[data-nav]').forEach((link) => {
    if (link.getAttribute('href') === path) {
      link.classList.add('active');
    }
  });
}

function setSmoothAnchorScroll() {
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', (event) => {
      const href = anchor.getAttribute('href');
      if (!href || href === '#') {
        return;
      }

      const target = document.querySelector(href);
      if (!target) {
        return;
      }

      event.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
}

const searchIndex = [
  { title: 'Alojamento', url: 'alojamento.html', keywords: 'hosting web wordpress loja site' },
  { title: 'Email', url: 'email.html', keywords: 'email profissional business caixas smtp imap' },
  { title: 'Domínios', url: 'dominios.html', keywords: 'dominio dns registo transferir renovar' },
  { title: 'Servidores Virtuais', url: 'servidores-virtuais.html', keywords: 'vps cloud cpu ram linux windows' },
  { title: 'Servidores Dedicados', url: 'servidores-dedicados.html', keywords: 'dedicado bare metal infraestrutura enterprise' },
  { title: 'Segurança', url: 'seguranca.html', keywords: 'waf ddos firewall malware protecao' },
  { title: 'Soluções', url: 'solucoes.html', keywords: 'pme ecommerce agencia empresa cloud' },
  { title: 'Ajuda / FAQs', url: 'ajuda.html', keywords: 'faq ajuda suporte duvidas perguntas frequentes' },
  { title: 'Blog', url: 'blog.html', keywords: 'blog artigos novidades cybercore' },
];

function createSearchOverlay() {
  const searchOverlay = document.createElement('div');
  searchOverlay.className = 'search-overlay';
  searchOverlay.innerHTML = `
    <div class="search-modal" role="dialog" aria-modal="true" aria-label="Pesquisar no website">
      <div class="search-head">
        <input class="search-input" type="search" placeholder="Pesquisar serviços, páginas e conteúdos..." />
        <button class="search-close" type="button" aria-label="Fechar pesquisa">✕</button>
      </div>
      <div class="search-results"></div>
    </div>
  `;

  document.body.appendChild(searchOverlay);
  return searchOverlay;
}

function renderSearchResults(searchResults, query = '') {
  const cleaned = query.trim().toLowerCase();
  const matches = searchIndex.filter((item) => {
    if (!cleaned) {
      return true;
    }
    return (`${item.title} ${item.keywords}`).toLowerCase().includes(cleaned);
  }).slice(0, 9);

  if (!matches.length) {
    searchResults.innerHTML = '<p class="search-empty">Sem resultados para essa pesquisa.</p>';
    return;
  }

  searchResults.innerHTML = matches.map((item) => (
    `<a class="search-item" href="${item.url}"><strong>${item.title}</strong><small>${item.url}</small></a>`
  )).join('');
}

function setSearch() {
  const searchOverlay = createSearchOverlay();
  const searchInput = searchOverlay.querySelector('.search-input');
  const searchResults = searchOverlay.querySelector('.search-results');
  const closeBtn = searchOverlay.querySelector('.search-close');

  function openSearch() {
    searchOverlay.classList.add('open');
    renderSearchResults(searchResults);
    window.setTimeout(() => searchInput.focus(), 0);
  }

  function closeSearch() {
    searchOverlay.classList.remove('open');
  }

  document.querySelectorAll('[data-search-open]').forEach((button) => {
    button.addEventListener('click', openSearch);
  });

  closeBtn.addEventListener('click', closeSearch);

  searchOverlay.addEventListener('click', (event) => {
    if (event.target === searchOverlay) {
      closeSearch();
    }
  });

  searchInput.addEventListener('input', (event) => {
    renderSearchResults(searchResults, event.target.value);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeSearch();
    }
  });
}

function setCarousel() {
  const carousels = document.querySelectorAll('[data-carousel]');

  carousels.forEach((carousel) => {
    const slides = Array.from(carousel.querySelectorAll('[data-carousel-slide]'));
    const dots = Array.from(carousel.querySelectorAll('[data-carousel-dot]'));

    if (slides.length < 2) {
      return;
    }

    const intervalAttr = Number(carousel.getAttribute('data-interval'));
    const intervalMs = Number.isFinite(intervalAttr) && intervalAttr > 0 ? intervalAttr : 10000;

    let currentIndex = slides.findIndex((slide) => slide.classList.contains('is-active'));
    if (currentIndex < 0) {
      currentIndex = 0;
    }

    let timerId = null;

    function syncState(nextIndex) {
      slides.forEach((slide, index) => {
        const isActive = index === nextIndex;
        slide.classList.toggle('is-active', isActive);
        slide.setAttribute('aria-hidden', String(!isActive));
      });

      dots.forEach((dot, index) => {
        const isActive = index === nextIndex;
        dot.classList.toggle('is-active', isActive);
        dot.setAttribute('aria-selected', String(isActive));
        dot.tabIndex = isActive ? 0 : -1;
      });
    }

    function goTo(index) {
      const nextIndex = (index + slides.length) % slides.length;
      currentIndex = nextIndex;
      syncState(nextIndex);
    }

    function next() {
      goTo(currentIndex + 1);
    }

    function stopAuto() {
      if (timerId !== null) {
        window.clearInterval(timerId);
        timerId = null;
      }
    }

    function startAuto() {
      stopAuto();
      timerId = window.setInterval(next, intervalMs);
    }

    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => {
        goTo(index);
        startAuto();
      });
    });

    carousel.addEventListener('mouseenter', stopAuto);
    carousel.addEventListener('mouseleave', startAuto);
    carousel.addEventListener('focusin', stopAuto);
    carousel.addEventListener('focusout', startAuto);

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        stopAuto();
      } else {
        startAuto();
      }
    });

    goTo(currentIndex);
    startAuto();
  });
}

function setDomainSearch() {
  const form = document.querySelector('[data-domain-search-form]');
  const input = document.querySelector('[data-domain-search-input]');

  if (!form || !input) {
    return;
  }

  form.addEventListener('submit', (event) => {
    event.preventDefault();

    const rawValue = input.value.trim().toLowerCase();
    if (!rawValue) {
      input.focus();
      return;
    }

    const normalizedDomain = rawValue
      .replace(/^https?:\/\//, '')
      .replace(/^www\./, '')
      .replace(/\/.*$/, '')
      .replace(/\s+/g, '');

    const orderUrl = new URL('https://manager.cybercore.pt/order');
    orderUrl.searchParams.set('domain', normalizedDomain);

    window.open(orderUrl.toString(), '_blank', 'noopener');
  });
}

function setPartnerLogos() {
  const partnerCards = document.querySelectorAll('[data-partner-card]');
  const allowedExtensions = ['svg', 'png', 'webp', 'jpg', 'jpeg'];

  partnerCards.forEach((card) => {
    const baseName = card.getAttribute('data-logo-base');
    const preferredExtension = (card.getAttribute('data-logo-ext') || '').toLowerCase();
    const logoImg = card.querySelector('.partner-logo');

    if (!baseName || !logoImg) {
      return;
    }

    // Keep logo loading predictable even when cards start visually hidden.
    logoImg.loading = 'eager';

    const extensionsToTry = preferredExtension && allowedExtensions.includes(preferredExtension)
      ? [preferredExtension, ...allowedExtensions.filter((ext) => ext !== preferredExtension)]
      : allowedExtensions;

    let currentTry = 0;

    function setFallbackMode() {
      card.classList.remove('has-logo');
    }

    function setLogoMode() {
      card.classList.add('has-logo');
    }

    function tryNextSource() {
      if (currentTry >= extensionsToTry.length) {
        setFallbackMode();
        return;
      }

      const nextExtension = extensionsToTry[currentTry++];
      logoImg.src = `assets/parceiros/${baseName}.${nextExtension}`;
    }

    logoImg.addEventListener('load', () => {
      if (!logoImg.naturalWidth || !logoImg.naturalHeight) {
        tryNextSource();
        return;
      }

      setLogoMode();
    });

    logoImg.addEventListener('error', tryNextSource);

    setFallbackMode();
    tryNextSource();
  });
}

function setPartnersCarousel() {
  const carousels = document.querySelectorAll('[data-partners-carousel]');

  carousels.forEach((carousel) => {
    const slides = Array.from(carousel.querySelectorAll('[data-partners-slide]'));
    const prevBtn = carousel.querySelector('[data-partners-prev]');
    const nextBtn = carousel.querySelector('[data-partners-next]');
    const progressBar = carousel.parentElement?.querySelector('[data-partners-progress]');

    if (slides.length < 2) {
      return;
    }

    const intervalAttr = Number(carousel.getAttribute('data-interval'));
    const intervalMs = Number.isFinite(intervalAttr) && intervalAttr > 0 ? intervalAttr : 30000;

    let currentIndex = slides.findIndex((slide) => slide.classList.contains('is-active'));
    if (currentIndex < 0) {
      currentIndex = 0;
    }

    let timerId = null;

    function restartProgress() {
      if (!progressBar) {
        return;
      }

      progressBar.style.transition = 'none';
      progressBar.style.transform = 'scaleX(0)';
      // Trigger reflow so transition always restarts.
      void progressBar.offsetWidth;
      progressBar.style.transition = `transform ${intervalMs}ms linear`;
      progressBar.style.transform = 'scaleX(1)';
    }

    function syncState(nextIndex) {
      slides.forEach((slide, index) => {
        const isActive = index === nextIndex;
        slide.classList.toggle('is-active', isActive);
        slide.setAttribute('aria-hidden', String(!isActive));
      });
    }

    function stopAuto() {
      if (timerId !== null) {
        window.clearInterval(timerId);
        timerId = null;
      }
    }

    function startAuto() {
      stopAuto();
      restartProgress();
      timerId = window.setInterval(() => {
        goTo(currentIndex + 1, false);
      }, intervalMs);
    }

    function goTo(index, restartTimer = true) {
      currentIndex = (index + slides.length) % slides.length;
      syncState(currentIndex);
      restartProgress();

      if (restartTimer) {
        startAuto();
      }
    }

    prevBtn?.addEventListener('click', () => goTo(currentIndex - 1));
    nextBtn?.addEventListener('click', () => goTo(currentIndex + 1));

    carousel.addEventListener('mouseenter', stopAuto);
    carousel.addEventListener('mouseleave', startAuto);
    carousel.addEventListener('focusin', stopAuto);
    carousel.addEventListener('focusout', startAuto);

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        stopAuto();
      } else {
        startAuto();
      }
    });

    syncState(currentIndex);
    startAuto();
  });
}

document.addEventListener('DOMContentLoaded', async () => {
  await Promise.all([
    loadFragment('site-topbar', 'assets/topbar.html'),
    loadFragment('site-header', 'assets/header.html'),
    loadFragment('site-footer', 'assets/footer.html'),
  ]);

  setCurrentYear();
  setActiveNavLink();
  setSmoothAnchorScroll();
  setSearch();
  setCarousel();
  setDomainSearch();
  setPartnerLogos();
  setPartnersCarousel();
});
