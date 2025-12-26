/* CyberCore Profile Page JS (pages/profile.js)
   - Centralized user state (API-first with mock fallback)
   - Centralized validation and field bindings
   - Ready for PHP/MySQL integration with CSRF/session
*/
(function(){
  'use strict';

  const state = {
    user: null,
    csrfToken: null,
    mode: 'api', // 'api' when endpoints respond, 'mock' for offline/testing
    mockExistingEmails: new Set(['existing@example.com', 'cliente@exemplo.pt']),
    mockTickets: []
  };

  const $ = sel => document.querySelector(sel);
  const $$ = sel => Array.from(document.querySelectorAll(sel));

  const bindings = {
    personal: {
      fullName: 'personal.fullName',
      email: 'personal.email',
      phone: 'personal.phone',
      address: 'personal.address',
      city: 'personal.city',
      postalCode: 'personal.postalCode',
      country: 'personal.country'
    },
    fiscal: {
      entityType: 'fiscal.entityType',
      companyName: 'fiscal.companyName',
      taxId: 'fiscal.taxId'
    }
  };

  const validators = {
    fullName: v => v && v.length >= 3 ? '' : 'Nome demasiado curto.',
    email: v => isValidEmail(v) ? '' : 'Email inválido.',
    phone: v => v && !isValidPhone(v) ? 'Telemóvel inválido.' : '',
    address: v => v && v.length < 6 ? 'Morada demasiado curta.' : '',
    city: v => v && v.length < 2 ? 'Cidade inválida.' : '',
    postalCode: v => /^\d{4}-\d{3}$/.test(v) ? '' : 'Formato inválido (NNNN-NNN).'
  };

  function get(obj, path, fallback = ''){
    if(!obj) return fallback;
    return path.split('.').reduce((acc, key) => (acc && acc[key] !== undefined ? acc[key] : null), obj) ?? fallback;
  }

  function setError(fieldId, msg){
    const el = document.querySelector(`[data-error-for="${fieldId}"]`);
    if(el) el.textContent = msg || '';
  }

  function clearErrors(form){
    form.querySelectorAll('.field-error').forEach(e => e.textContent='');
  }

  function isValidEmail(email){
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function isValidPhone(phone){
    const s = String(phone).trim();
    return /^\+?[0-9\s-]{7,}$/.test(s);
  }

  function isValidNIF(nif){
    const s = String(nif).replace(/\D/g,'');
    if(s.length !== 9) return false;
    const digits = s.split('').map(Number);
    const weights = [9,8,7,6,5,4,3,2];
    let sum = 0;
    for(let i=0;i<8;i++){ sum += digits[i]*weights[i]; }
    const check = 11 - (sum % 11);
    const cd = check >= 10 ? 0 : check;
    return cd === digits[8];
  }

  function validatePersonal(payload){
    const errors = {};
    Object.keys(validators).forEach(key => {
      const msg = validators[key](payload[key] || '');
      if(msg) errors[key] = msg;
    });
    if(state.mode === 'mock'){
      const currentEmail = state.user ? (state.user.personal.email || '') : '';
      const email = (payload.email || '').toLowerCase();
      if(email !== currentEmail.toLowerCase() && state.mockExistingEmails.has(email)){
        errors.email = 'Este email já está em uso.';
      }
    }
    return errors;
  }

  function applyBindings(section){
    if(!state.user) return;
    const map = bindings[section];
    Object.keys(map).forEach(id => {
      const el = document.getElementById(id);
      if(el) el.value = get(state.user, map[id], '');
    });
  }

  function populatePersonal(){ applyBindings('personal'); }

  function populateFiscal(){
    applyBindings('fiscal');
    ['entityType','companyName','taxId'].forEach(id => {
      const el = document.getElementById(id);
      if(el){
        el.setAttribute('readonly','true');
        el.setAttribute('aria-readonly','true');
        el.setAttribute('disabled','');
      }
    });
  }

  function initTabs(){
    const tabs = $$('.tab');
    const panels = $$('.panel');
    tabs.forEach(t => t.addEventListener('click', () => {
      tabs.forEach(x => x.classList.remove('active'));
      t.classList.add('active');
      panels.forEach(p => p.classList.add('hidden'));
      const id = t.id.replace('tab','panel');
      const panel = document.getElementById(id);
      if(panel) panel.classList.remove('hidden');
    }));
  }

  function collectPersonalPayload(){
    return {
      fullName: $('#fullName').value.trim(),
      email: $('#email').value.trim(),
      phone: $('#phone').value.trim(),
      address: $('#address').value.trim(),
      city: $('#city').value.trim(),
      postalCode: $('#postalCode').value.trim(),
      country: $('#country').value
    };
  }

  function initPersonalForm(){
    const form = $('#formPersonal');
    form.addEventListener('submit', e => {
      e.preventDefault();
      clearErrors(form);
      const payload = collectPersonalPayload();
      const errs = validatePersonal(payload);
      if(Object.keys(errs).length){
        Object.keys(errs).forEach(k => setError(k, errs[k]));
        return;
      }

      if(state.mode === 'mock'){
        state.user.personal = { ...state.user.personal, ...payload };
        toast('Informação pessoal atualizada (modo simulado).');
        return;
      }

      apiPost('/inc/profile_update.php', payload)
        .then(res => {
          if(res && res.success){
            toast('Informação pessoal atualizada.');
            return loadUser();
          }
          const serverErrs = res && res.errors ? res.errors : {};
          Object.keys(serverErrs).forEach(k => setError(k, serverErrs[k]));
        })
        .catch(() => toast('Erro ao atualizar. Tente novamente.'));
    });
  }

  function initFiscalPanel(){
    const form = $('#formFiscal');
    if(!form) return;
    
    // Check if fiscal fields are editable (Manager/Financial Support)
    const taxIdField = $('#taxId');
    const isEditable = taxIdField && !taxIdField.hasAttribute('readonly');
    
    if(isEditable) {
      // Handle fiscal form submission for Manager/Financial Support
      form.addEventListener('submit', e => {
        e.preventDefault();
        clearErrors(form);
        
        const payload = {
          entityType: $('#entityType').value,
          companyName: $('#companyName').value.trim(),
          taxId: $('#taxId').value.trim()
        };
        
        // Validate
        const errors = {};
        if(!['Singular', 'Coletiva'].includes(payload.entityType)) {
          errors.entityType = 'Tipo de entidade inválido.';
        }
        if(!payload.taxId || payload.taxId.length !== 9 || !/^\d{9}$/.test(payload.taxId)) {
          errors.taxId = 'NIF deve ter 9 dígitos.';
        }
        if(payload.entityType === 'Coletiva' && payload.companyName.length < 3) {
          errors.companyName = 'Nome da empresa é obrigatório para Pessoa Coletiva.';
        }
        
        if(Object.keys(errors).length) {
          Object.keys(errors).forEach(k => setError(k, errors[k]));
          return;
        }
        
        // Submit to backend
        apiPost('/inc/fiscal_update.php', payload)
          .then(res => {
            if(res && res.success) {
              toast('Dados fiscais atualizados com sucesso.');
              return loadUser();
            } else {
              const serverErrs = res && res.errors ? res.errors : {};
              Object.keys(serverErrs).forEach(k => setError(k, serverErrs[k]));
              if(serverErrs.permission) {
                toast(serverErrs.permission);
              }
            }
          })
          .catch(() => toast('Erro ao atualizar dados fiscais.'));
      });
    } else {
      // Prevent submission for read-only users
      form.addEventListener('submit', e => e.preventDefault());
    }
    
    // Handle fiscal change request button for Clients
    const btn = $('#requestFiscalChangeBtn');
    if(btn){
      btn.addEventListener('click', () => {
        const snapshot = state.user ? { ...state.user.fiscal } : {};
        const ticket = {
          id: `mock-${Date.now()}`,
          category: 'Billing / Fiscal Data',
          userId: state.user ? state.user.id : null,
          fiscalSnapshot: snapshot,
          createdAt: new Date().toISOString()
        };
        state.mockTickets.push(ticket);

        if(state.mode === 'mock'){
          toast('Pedido enviado (modo simulado).');
          return;
        }

        apiPost('/inc/request_fiscal_change.php', { reason: '' })
          .then(res => {
            if(res && res.success){
              toast('Pedido enviado: iremos contactá-lo para alterar os dados fiscais.');
            } else {
              toast('Pedido registado (modo simulado).');
            }
          })
          .catch(() => toast('Pedido registado (modo simulado).'));
      });
    }
  }

  function initSaveAll(){
    const btn = $('#saveAllBtn');
    btn.addEventListener('click', () => {
      $('#formPersonal').dispatchEvent(new Event('submit', {cancelable:true}));
      toast('Todas as alterações foram guardadas.');
    });
  }

  function toast(msg){
    let t = document.getElementById('toast');
    if(!t){
      t = document.createElement('div');
      t.id = 'toast';
      t.style.position = 'fixed';
      t.style.bottom = '20px';
      t.style.right = '20px';
      t.style.background = '#0b1220';
      t.style.border = '1px solid #1f2937';
      t.style.color = '#e5e7eb';
      t.style.padding = '10px 14px';
      t.style.borderRadius = '10px';
      t.style.boxShadow = '0 8px 30px rgba(0,0,0,0.25)';
      t.style.zIndex = '9999';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.opacity = '0.98';
    setTimeout(()=>{ if(t) t.style.opacity = '0'; }, 2500);
  }

  function readMetaCsrf(){
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : null;
  }

  function getCsrfToken(){
    if(state.csrfToken) return Promise.resolve(state.csrfToken);
    const meta = readMetaCsrf();
    if(meta){
      state.csrfToken = meta;
      return Promise.resolve(meta);
    }
    return fetch('/inc/get_csrf_token.php', { credentials: 'same-origin' })
      .then(r => r.json())
      .then(j => { state.csrfToken = j && j.token ? j.token : null; return state.csrfToken; });
  }

  function apiPost(url, data){
    const params = new URLSearchParams();
    Object.keys(data || {}).forEach(k => params.append(k, data[k]));
    if(state.csrfToken) params.append('csrf_token', state.csrfToken);
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params.toString(),
      credentials: 'same-origin'
    }).then(r => r.json());
  }

  function loadUser(){
    return fetch('/inc/profile_data.php', { credentials: 'same-origin' })
      .then(r => r.ok ? r.json() : null)
      .then(j => {
        if(j && j.success && j.user){
          state.user = j.user;
          state.mode = 'api';
        } else {
          useMockUser();
        }
        populatePersonal();
        populateFiscal();
        return state.user;
      })
      .catch(() => {
        useMockUser();
        populatePersonal();
        populateFiscal();
        return state.user;
      });
  }

  function useMockUser(){
    state.mode = 'mock';
    state.user = {
      id: 1,
      role: 'Cliente',
      personal: {
        fullName: 'Cliente Exemplo',
        email: 'cliente@exemplo.pt',
        phone: '+351 912 345 678',
        address: 'Rua Exemplo 123',
        city: 'Lisboa',
        postalCode: '1000-001',
        country: 'PT'
      },
      fiscal: {
        entityType: 'Singular',
        companyName: '',
        taxId: '245439360',
        locked: true
      }
    };
    return state.user;
  }

  function init(){
    initTabs();
    getCsrfToken().catch(() => null).then(loadUser).finally(() => {
      initPersonalForm();
      initFiscalPanel();
      initSaveAll();
    });
  }

  document.addEventListener('DOMContentLoaded', init);
})();
