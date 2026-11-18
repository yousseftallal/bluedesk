/* BlueDesk Admin — interactions */
(function () {
  'use strict';

  // Sidebar toggle (mobile)
  const toggle = document.querySelector('.sidebar-toggle');
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.overlay');
  if (toggle && sidebar) {
    const close = () => {
      sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('show');
      toggle.setAttribute('aria-expanded', 'false');
    };
    toggle.addEventListener('click', () => {
      const open = sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('show', open);
      toggle.setAttribute('aria-expanded', open);
    });
    if (overlay) overlay.addEventListener('click', close);
    window.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
  }

  // Delete / dangerous-action confirmation
  const confirmedForms = new WeakSet();
  document.querySelectorAll('[data-confirm]').forEach(el => {
    const form = el.form;
    if (!form) {
      el.addEventListener('click', event => {
        const message = el.getAttribute('data-confirm') || 'هل أنت متأكد؟';
        if (!window.confirm(message)) event.preventDefault();
      });
      return;
    }
    if (confirmedForms.has(form)) return;
    confirmedForms.add(form);
    form.addEventListener('submit', event => {
      const submitter = event.submitter || document.activeElement;
      const control = submitter && submitter.closest ? submitter.closest('[data-confirm]') : null;
      const confirmation = control || form.querySelector('[data-confirm]');
      if (!confirmation) return;
      const message = confirmation.getAttribute('data-confirm') || 'هل أنت متأكد؟';
      if (!window.confirm(message)) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }
    });
  });

  document.querySelectorAll('form[data-void-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
      const reason = window.prompt(form.getAttribute('data-void-confirm') || 'اكتب سبب الإلغاء:');
      if (reason === null || reason.trim().length < 3) {
        event.preventDefault();
        return;
      }
      const field = form.querySelector('[name="void_reason"]');
      if (field) field.value = reason.trim();
      if (!window.confirm('سيتم إلغاء الحركة مع الاحتفاظ بسجلها. هل تريد المتابعة؟')) event.preventDefault();
    });
  });

  document.querySelectorAll('[data-auto-submit]').forEach(el => {
    el.addEventListener('change', () => {
      if (el.form) el.form.requestSubmit();
    });
  });

  const payrollEmployee = document.getElementById('payrollEmployee');
  const payrollSalary = document.querySelector('[name="base_salary"]');
  if (payrollEmployee && payrollSalary) {
    payrollEmployee.addEventListener('change', () => {
      const selected = payrollEmployee.options[payrollEmployee.selectedIndex];
      if (selected && selected.dataset.salary && (payrollSalary.value === '' || Number(payrollSalary.value) === 0)) {
        payrollSalary.value = selected.dataset.salary;
      }
    });
  }

  // Auto-hide flash messages
  document.querySelectorAll('.admin-flash').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .4s ease, transform .4s ease';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-8px)';
      setTimeout(() => el.remove(), 420);
    }, 5000);
  });

  // Prevent double submit + loading state
  document.querySelectorAll('form[data-loading]').forEach(form => {
    form.addEventListener('submit', () => {
      const btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جارٍ الحفظ...';
        setTimeout(() => { btn.disabled = false; btn.innerHTML = original; }, 8000);
      }
    });
  });

  // Copy shortcode / URL fields
  document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', () => {
      const value = btn.getAttribute('data-copy');
      if (navigator.clipboard) {
        navigator.clipboard.writeText(value).then(() => {
          const old = btn.innerHTML;
          btn.innerHTML = '<i class="fa-solid fa-check"></i>';
          setTimeout(() => (btn.innerHTML = old), 1400);
        });
      }
    });
  });

  // Color picker -> text sync
  document.querySelectorAll('[data-color-sync]').forEach(picker => {
    const target = document.querySelector(picker.getAttribute('data-color-sync'));
    if (!target) return;
    picker.addEventListener('input', () => { target.value = picker.value; });
    target.addEventListener('input', () => {
      if (/^#[0-9a-fA-F]{6}$/.test(target.value)) picker.value = target.value;
    });
  });
})();
