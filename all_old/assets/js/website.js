// CyberCore - Website interactions
(function(){
  // Menu toggle (mobile)
  const toggleBtn = document.querySelector('.menu-toggle');
  if(toggleBtn){
    const targetId = toggleBtn.getAttribute('data-target');
    const navEl = document.getElementById(targetId);
    if(navEl){
      toggleBtn.addEventListener('click', () => {
        const visible = getComputedStyle(navEl).display !== 'none';
        navEl.style.display = visible ? 'none' : 'flex';
      });
    }
  }

  // Dropdown toggles
  const groups = document.querySelectorAll('.menu-group .menu-link');
  groups.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const parent = btn.closest('.menu-group');
      const isOpen = parent.classList.contains('open');
      document.querySelectorAll('.menu-group.open').forEach(g => g.classList.remove('open'));
      if(!isOpen){
        parent.classList.add('open');
        btn.setAttribute('aria-expanded','true');
      } else {
        btn.setAttribute('aria-expanded','false');
      }
    });
  });
  document.addEventListener('click', (e) => {
    if(!e.target.closest('.menu-group')){
      document.querySelectorAll('.menu-group.open').forEach(g => g.classList.remove('open'));
      document.querySelectorAll('.menu-group .menu-link').forEach(b => b.setAttribute('aria-expanded','false'));
    }
  });

  // Cookie banner dismiss
  const banner = document.querySelector('.cookie-banner');
  const dismiss = document.querySelector('[data-cookie-dismiss]');
  if(banner){
    const accepted = localStorage.getItem('cookiesAccepted') === '1';
    if(accepted){ banner.style.display = 'none'; }
    if(dismiss){
      dismiss.addEventListener('click', () => {
        localStorage.setItem('cookiesAccepted','1');
        banner.style.display = 'none';
      });
    }
  }
})();