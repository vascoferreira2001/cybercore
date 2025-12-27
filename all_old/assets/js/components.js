/**
 * CyberCore UI Components Library
 * Reusable JavaScript components for toasts, modals, loading states
 */

const CyberCore = {
  
  /**
   * Toast Notification System
   */
  Toast: {
    container: null,
    
    init() {
      if (!this.container) {
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
      }
    },
    
    show(message, type = 'info', duration = 4000) {
      this.init();
      
      const toast = document.createElement('div');
      toast.className = `toast toast-${type}`;
      
      const icon = this.getIcon(type);
      
      toast.innerHTML = `
        <div class="toast-icon">${icon}</div>
        <div class="toast-content">
          <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" aria-label="Close">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M12 4L4 12M4 4l8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
      `;
      
      this.container.appendChild(toast);
      
      // Trigger animation
      setTimeout(() => toast.classList.add('toast-show'), 10);
      
      // Close button
      const closeBtn = toast.querySelector('.toast-close');
      closeBtn.addEventListener('click', () => this.hide(toast));
      
      // Auto hide
      if (duration > 0) {
        setTimeout(() => this.hide(toast), duration);
      }
      
      return toast;
    },
    
    hide(toast) {
      toast.classList.add('toast-hide');
      setTimeout(() => toast.remove(), 300);
    },
    
    getIcon(type) {
      const icons = {
        success: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        error: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 6v4m0 4h.01M10 18a8 8 0 100-16 8 8 0 000 16z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        warning: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 6v4m0 4h.01M8.217 2.652l-6.5 11A2 2 0 003.433 17h13.134a2 2 0 001.716-3.348l-6.5-11a2 2 0 00-3.566 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        info: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 14v-4m0-4h.01M10 18a8 8 0 100-16 8 8 0 000 16z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
      };
      return icons[type] || icons.info;
    },
    
    // Convenience methods
    success(message, duration) { return this.show(message, 'success', duration); },
    error(message, duration) { return this.show(message, 'error', duration); },
    warning(message, duration) { return this.show(message, 'warning', duration); },
    info(message, duration) { return this.show(message, 'info', duration); }
  },
  
  /**
   * Modal Dialog System
   */
  Modal: {
    activeModals: [],
    
    open(options = {}) {
      const {
        title = '',
        content = '',
        size = 'medium', // small, medium, large, fullscreen
        showClose = true,
        closeOnOverlay = true,
        closeOnEsc = true,
        footer = null,
        onClose = null
      } = options;
      
      const modal = document.createElement('div');
      modal.className = 'modal-overlay';
      
      modal.innerHTML = `
        <div class="modal modal-${size}">
          <div class="modal-header">
            <h3 class="modal-title">${title}</h3>
            ${showClose ? '<button class="modal-close" aria-label="Close"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>' : ''}
          </div>
          <div class="modal-body">${content}</div>
          ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
        </div>
      `;
      
      document.body.appendChild(modal);
      document.body.style.overflow = 'hidden';
      
      // Trigger animation
      setTimeout(() => modal.classList.add('modal-show'), 10);
      
      // Close handlers
      const close = () => {
        this.close(modal);
        if (onClose) onClose();
      };
      
      if (showClose) {
        modal.querySelector('.modal-close').addEventListener('click', close);
      }
      
      if (closeOnOverlay) {
        modal.addEventListener('click', (e) => {
          if (e.target === modal) close();
        });
      }
      
      if (closeOnEsc) {
        const escHandler = (e) => {
          if (e.key === 'Escape') {
            close();
            document.removeEventListener('keydown', escHandler);
          }
        };
        document.addEventListener('keydown', escHandler);
      }
      
      this.activeModals.push(modal);
      return modal;
    },
    
    close(modal) {
      modal.classList.remove('modal-show');
      modal.classList.add('modal-hide');
      
      setTimeout(() => {
        modal.remove();
        this.activeModals = this.activeModals.filter(m => m !== modal);
        
        if (this.activeModals.length === 0) {
          document.body.style.overflow = '';
        }
      }, 300);
    },
    
    confirm(options = {}) {
      const {
        title = 'Confirmar',
        message = 'Tem a certeza?',
        confirmText = 'Confirmar',
        cancelText = 'Cancelar',
        confirmType = 'primary', // primary, danger
        onConfirm = null,
        onCancel = null
      } = options;
      
      return new Promise((resolve) => {
        const modal = this.open({
          title,
          content: `<p>${message}</p>`,
          size: 'small',
          footer: `
            <button class="btn ghost" data-action="cancel">${cancelText}</button>
            <button class="btn ${confirmType}" data-action="confirm">${confirmText}</button>
          `,
          closeOnOverlay: false,
          onClose: () => {
            resolve(false);
            if (onCancel) onCancel();
          }
        });
        
        modal.querySelector('[data-action="cancel"]').addEventListener('click', () => {
          this.close(modal);
          resolve(false);
          if (onCancel) onCancel();
        });
        
        modal.querySelector('[data-action="confirm"]').addEventListener('click', () => {
          this.close(modal);
          resolve(true);
          if (onConfirm) onConfirm();
        });
      });
    }
  },
  
  /**
   * Loading States
   */
  Loading: {
    overlay: null,
    
    show(message = 'A carregar...') {
      if (!this.overlay) {
        this.overlay = document.createElement('div');
        this.overlay.className = 'loading-overlay';
        document.body.appendChild(this.overlay);
      }
      
      this.overlay.innerHTML = `
        <div class="loading-spinner">
          <div class="spinner"></div>
          <div class="loading-message">${message}</div>
        </div>
      `;
      
      this.overlay.classList.add('loading-show');
      document.body.style.overflow = 'hidden';
    },
    
    hide() {
      if (this.overlay) {
        this.overlay.classList.remove('loading-show');
        document.body.style.overflow = '';
        setTimeout(() => {
          if (this.overlay) this.overlay.remove();
          this.overlay = null;
        }, 300);
      }
    }
  },
  
  /**
   * Form Validation Helpers
   */
  Form: {
    validate(form) {
      const fields = form.querySelectorAll('[required], [data-validate]');
      let isValid = true;
      
      fields.forEach(field => {
        if (!this.validateField(field)) {
          isValid = false;
        }
      });
      
      return isValid;
    },
    
    validateField(field) {
      const value = field.value.trim();
      const type = field.getAttribute('data-validate') || field.type;
      let isValid = true;
      let errorMessage = '';
      
      // Required check
      if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'Este campo é obrigatório';
      }
      
      // Email validation
      else if (type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
          isValid = false;
          errorMessage = 'Email inválido';
        }
      }
      
      // URL validation
      else if (type === 'url' && value) {
        try {
          new URL(value);
        } catch {
          isValid = false;
          errorMessage = 'URL inválida';
        }
      }
      
      // Min length
      const minLength = field.getAttribute('minlength');
      if (minLength && value && value.length < parseInt(minLength)) {
        isValid = false;
        errorMessage = `Mínimo ${minLength} caracteres`;
      }
      
      // Custom pattern
      const pattern = field.getAttribute('pattern');
      if (pattern && value) {
        const regex = new RegExp(pattern);
        if (!regex.test(value)) {
          isValid = false;
          errorMessage = field.getAttribute('data-error-message') || 'Formato inválido';
        }
      }
      
      this.setFieldState(field, isValid, errorMessage);
      return isValid;
    },
    
    setFieldState(field, isValid, errorMessage = '') {
      const formGroup = field.closest('.form-group');
      if (!formGroup) return;
      
      // Remove previous states
      formGroup.classList.remove('field-error', 'field-success');
      
      // Remove previous error message
      const existingError = formGroup.querySelector('.field-error-message');
      if (existingError) existingError.remove();
      
      if (!isValid) {
        formGroup.classList.add('field-error');
        const errorEl = document.createElement('div');
        errorEl.className = 'field-error-message';
        errorEl.textContent = errorMessage;
        formGroup.appendChild(errorEl);
      } else if (field.value) {
        formGroup.classList.add('field-success');
      }
    },
    
    reset(form) {
      form.reset();
      const formGroups = form.querySelectorAll('.form-group');
      formGroups.forEach(group => {
        group.classList.remove('field-error', 'field-success');
        const error = group.querySelector('.field-error-message');
        if (error) error.remove();
      });
    }
  },
  
  /**
   * AJAX Helper
   */
  ajax(url, options = {}) {
    const {
      method = 'GET',
      data = null,
      headers = {},
      onSuccess = null,
      onError = null,
      showLoading = false,
      loadingMessage = 'A carregar...'
    } = options;
    
    if (showLoading) this.Loading.show(loadingMessage);
    
    const fetchOptions = {
      method,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        ...headers
      }
    };
    
    if (data) {
      if (data instanceof FormData) {
        fetchOptions.body = data;
      } else {
        fetchOptions.headers['Content-Type'] = 'application/json';
        fetchOptions.body = JSON.stringify(data);
      }
    }
    
    return fetch(url, fetchOptions)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(result => {
        if (showLoading) this.Loading.hide();
        if (onSuccess) onSuccess(result);
        return result;
      })
      .catch(error => {
        if (showLoading) this.Loading.hide();
        console.error('AJAX Error:', error);
        this.Toast.error('Ocorreu um erro. Por favor tente novamente.');
        if (onError) onError(error);
        throw error;
      });
  }
};

// Make globally available
window.CyberCore = CyberCore;

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    CyberCore.Toast.init();
  });
} else {
  CyberCore.Toast.init();
}

// Live form validation
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', (e) => {
      if (!CyberCore.Form.validate(form)) {
        e.preventDefault();
      }
    });
    
    // Real-time validation
    form.querySelectorAll('[required], [data-validate]').forEach(field => {
      field.addEventListener('blur', () => {
        CyberCore.Form.validateField(field);
      });
      
      field.addEventListener('input', () => {
        if (field.classList.contains('field-error')) {
          CyberCore.Form.validateField(field);
        }
      });
    });
  });
});
