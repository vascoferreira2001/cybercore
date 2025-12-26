/**
 * CyberCore Dashboard - JavaScript
 * Handles mobile menu, interactions, dynamic features, and session management
 */

(function() {
  'use strict';

  // ========== SESSION MANAGEMENT ==========
  let sessionCheckInterval = null;
  
  // Check session validity every 5 minutes
  function initSessionCheck() {
    sessionCheckInterval = setInterval(function() {
      fetch('/inc/check_session.php')
        .then(function(response) { return response.json(); })
        .then(function(data) {
          if (!data.valid) {
            clearInterval(sessionCheckInterval);
            alert('A sua sessão expirou. Será redirecionado para o login.');
            window.location.href = '/login.php';
          }
        })
        .catch(function(error) {
          console.error('Session check failed:', error);
        });
    }, 300000); // 5 minutes
  }

  // Update activity timestamp on user interaction
  function updateActivity() {
    fetch('/inc/update_activity.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    }).catch(function(error) {
      console.log('Activity update failed:', error);
    });
  }

  // Track user activity
  let activityTimeout;
  function resetActivityTimer() {
    clearTimeout(activityTimeout);
    activityTimeout = setTimeout(updateActivity, 60000); // 1 minute
  }

  document.addEventListener('mousemove', resetActivityTimer);
  document.addEventListener('keypress', resetActivityTimer);
  document.addEventListener('click', resetActivityTimer);

  // Initialize session check
  initSessionCheck();

  // ========== SIDEBAR TOGGLE ==========
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  const sidebar = document.getElementById('sidebar');

  if (mobileMenuBtn && sidebar) {
    mobileMenuBtn.addEventListener('click', function() {
      sidebar.classList.toggle('active');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
      if (window.innerWidth <= 768) {
        if (!sidebar.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
          sidebar.classList.remove('active');
        }
      }
    });
  }

  // ========== ACTIVE NAV ITEM ==========
  const currentPath = window.location.pathname;
  const navItems = document.querySelectorAll('.nav-item');

  navItems.forEach(function(item) {
    const href = item.getAttribute('href');
    if (href && currentPath.includes(href)) {
      navItems.forEach(function(navItem) {
        navItem.classList.remove('active');
      });
      item.classList.add('active');
    }
  });

  // ========== SEARCH FUNCTIONALITY ==========
  const searchInput = document.querySelector('.search-box input');
  
  if (searchInput) {
    searchInput.addEventListener('keypress', function(event) {
      if (event.key === 'Enter') {
        const searchTerm = this.value.trim();
        if (searchTerm) {
          // Redirect to search page (to be implemented)
          window.location.href = '/search.php?q=' + encodeURIComponent(searchTerm);
        }
      }
    });
  }

  // ========== NOTIFICATION BELL ==========
  const notificationBtn = document.querySelector('.notification-btn');
  
  if (notificationBtn) {
    notificationBtn.addEventListener('click', function(e) {
      e.preventDefault();
      window.location.href = '/notifications.php';
    });
  }

  // Auto-refresh notification count every 30 seconds
  setInterval(function() {
    fetch('/inc/get_notification_count.php')
      .then(function(response) { return response.json(); })
      .then(function(data) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
          if (data.count > 0) {
            badge.textContent = data.count > 99 ? '99+' : data.count;
            badge.style.display = 'flex';
          } else {
            badge.style.display = 'none';
          }
        }
      })
      .catch(function(error) {
        console.error('Failed to fetch notification count:', error);
      });
  }, 30000);

  // ========== USER MENU ==========
  const userMenu = document.querySelector('.user-menu');
  
  if (userMenu) {
    userMenu.addEventListener('click', function() {
      // Toggle user dropdown (to be implemented)
      // For now, could redirect to profile
      // window.location.href = '/profile.php';
    });
  }

  // ========== SMOOTH SCROLL ==========
  document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(event) {
      event.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // ========== AUTO-UPDATE STATS ==========
  function updateStats() {
    fetch('/inc/get_dashboard_stats.php')
      .then(function(response) { return response.json(); })
      .then(function(data) {
        if (data.success) {
          // Update stat card values
          updateStatValue('domains', data.stats.domains);
          updateStatValue('services', data.stats.services);
          updateStatValue('tickets', data.stats.tickets);
          updateStatValue('invoices', data.stats.invoices);
        }
      })
      .catch(function(error) {
        console.error('Failed to update stats:', error);
      });
  }

  function updateStatValue(statId, value) {
    const statElement = document.querySelector('[data-stat="' + statId + '"]');
    if (statElement) {
      // Animate number change
      const currentValue = parseInt(statElement.textContent);
      if (currentValue !== value) {
        statElement.style.transition = 'all 0.3s ease';
        statElement.textContent = value;
        statElement.style.transform = 'scale(1.1)';
        setTimeout(function() {
          statElement.style.transform = 'scale(1)';
        }, 300);
      }
    }
  }

  // Update stats every 60 seconds
  setInterval(updateStats, 60000);

  // ========== TOOLTIPS (Simple) ==========
  const tooltipElements = document.querySelectorAll('[data-tooltip]');
  
  tooltipElements.forEach(function(element) {
    element.addEventListener('mouseenter', function() {
      const tooltipText = this.getAttribute('data-tooltip');
      const tooltip = document.createElement('div');
      tooltip.className = 'tooltip';
      tooltip.textContent = tooltipText;
      document.body.appendChild(tooltip);
      
      const rect = this.getBoundingClientRect();
      tooltip.style.position = 'absolute';
      tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
      tooltip.style.left = (rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)) + 'px';
      tooltip.style.background = '#1a1a1a';
      tooltip.style.color = 'white';
      tooltip.style.padding = '6px 12px';
      tooltip.style.borderRadius = '6px';
      tooltip.style.fontSize = '12px';
      tooltip.style.zIndex = '9999';
    });
    
    element.addEventListener('mouseleave', function() {
      const tooltips = document.querySelectorAll('.tooltip');
      tooltips.forEach(function(tooltip) {
        tooltip.remove();
      });
    });
  });

  // ========== CONSOLE INFO ==========
  console.log('%c🚀 CyberCore Dashboard', 'color: #007dff; font-size: 16px; font-weight: bold;');
  console.log('%cProduction-ready dashboard loaded successfully', 'color: #10b981; font-size: 12px;');

})();
