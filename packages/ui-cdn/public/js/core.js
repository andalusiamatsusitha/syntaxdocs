/**
 * Syntax Core UI Engine (core.js)
 * Lightweight, zero-dependency Vanilla JS engine for UI interactions.
 * Works seamlessly across PHP, Node.js, Golang or static HTML.
 */

(function (window, document) {
  'use strict';

  const CoreUI = {
    version: '1.0.0',

    // ==========================================
    // 1. Modal Controller
    // ==========================================
    Modal: {
      open(modalOrSelector) {
        const modal = typeof modalOrSelector === 'string'
          ? document.querySelector(modalOrSelector)
          : modalOrSelector;

        if (!modal) return;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        // Dispatch Custom Event
        modal.dispatchEvent(new CustomEvent('core:modal:open', { bubbles: true, detail: { modal } }));
      },

      close(modalOrSelector) {
        const modal = typeof modalOrSelector === 'string'
          ? document.querySelector(modalOrSelector)
          : modalOrSelector;

        if (!modal) return;

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        
        // Check if any other modal is still open
        if (!document.querySelector('.c-modal.is-open')) {
          document.body.style.overflow = '';
        }

        // Dispatch Custom Event
        modal.dispatchEvent(new CustomEvent('core:modal:close', { bubbles: true, detail: { modal } }));
      },

      closeAll() {
        document.querySelectorAll('.c-modal.is-open').forEach((m) => CoreUI.Modal.close(m));
      }
    },

    // ==========================================
    // 2. Dropdown Controller
    // ==========================================
    Dropdown: {
      toggle(triggerEl) {
        const dropdown = triggerEl.closest('.c-dropdown');
        if (!dropdown) return;

        const menu = dropdown.querySelector('.c-dropdown__menu');
        if (!menu) return;

        const isOpen = menu.classList.contains('is-open');
        CoreUI.Dropdown.closeAll();

        if (!isOpen) {
          menu.classList.add('is-open');
          dropdown.dispatchEvent(new CustomEvent('core:dropdown:open', { bubbles: true, detail: { dropdown } }));
        }
      },

      closeAll() {
        document.querySelectorAll('.c-dropdown__menu.is-open').forEach((menu) => {
          menu.classList.remove('is-open');
          const dropdown = menu.closest('.c-dropdown');
          if (dropdown) {
            dropdown.dispatchEvent(new CustomEvent('core:dropdown:close', { bubbles: true, detail: { dropdown } }));
          }
        });
      }
    },

    // ==========================================
    // 3. Alert Controller
    // ==========================================
    Alert: {
      dismiss(alertEl) {
        if (!alertEl) return;
        alertEl.style.transition = 'opacity 200ms ease, transform 200ms ease';
        alertEl.style.opacity = '0';
        alertEl.style.transform = 'translateY(-8px)';
        setTimeout(() => {
          if (alertEl.parentNode) alertEl.parentNode.removeChild(alertEl);
        }, 200);
      }
    }
  };

  // ==========================================
  // 4. Global Event Delegation
  // ==========================================
  document.addEventListener('click', function (e) {
    // 4.1 Modal Open Trigger
    const modalTrigger = e.target.closest('[data-toggle="modal"]');
    if (modalTrigger) {
      e.preventDefault();
      const targetSelector = modalTrigger.getAttribute('data-target') || modalTrigger.getAttribute('href');
      if (targetSelector) CoreUI.Modal.open(targetSelector);
      return;
    }

    // 4.2 Modal Dismiss Trigger
    const modalDismiss = e.target.closest('[data-dismiss="modal"]');
    if (modalDismiss) {
      e.preventDefault();
      const modal = modalDismiss.closest('.c-modal');
      if (modal) CoreUI.Modal.close(modal);
      return;
    }

    // 4.3 Dropdown Trigger
    const dropdownTrigger = e.target.closest('[data-toggle="dropdown"]');
    if (dropdownTrigger) {
      e.preventDefault();
      CoreUI.Dropdown.toggle(dropdownTrigger);
      return;
    }

    // If clicked outside any active dropdown, close dropdowns
    if (!e.target.closest('.c-dropdown')) {
      CoreUI.Dropdown.closeAll();
    }

    // 4.4 Alert Dismiss Trigger
    const alertDismiss = e.target.closest('[data-dismiss="alert"]');
    if (alertDismiss) {
      e.preventDefault();
      const alert = alertDismiss.closest('.c-alert');
      if (alert) CoreUI.Alert.dismiss(alert);
      return;
    }
  });

  // 4.5 Keyboard Accessibility (Escape to close modals & dropdowns)
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
      CoreUI.Modal.closeAll();
      CoreUI.Dropdown.closeAll();
    }
  });

  // Expose to window
  window.CoreUI = CoreUI;

})(window, document);
