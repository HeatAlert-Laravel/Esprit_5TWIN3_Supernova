import { createPopper } from '@popperjs/core';
import './bootstrap';
import './auto-filter';
import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';

// flatpickr
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
// FullCalendar
import { Calendar } from 'fullcalendar';



window.Alpine = Alpine;
window.createPopper = createPopper;
window.ApexCharts = ApexCharts;
window.flatpickr = flatpickr;
window.FullCalendar = Calendar;

Alpine.start();

// Initialize components on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('[data-advice-editor]')) {
        import('./advice-editor').then(({ initAdviceEditors }) => initAdviceEditors());
    }
    const confirmDialog = document.querySelector('[data-confirm-dialog]');
    let pendingForm = null;

    if (confirmDialog) {
        const message = confirmDialog.querySelector('[data-confirm-message]');
        const subject = confirmDialog.querySelector('[data-confirm-subject]');
        const submitButton = confirmDialog.querySelector('[data-confirm-submit]');
        const cancelButton = confirmDialog.querySelector('.ha-confirm-modal__actions [data-confirm-cancel]');
        const focusable = () => Array.from(confirmDialog.querySelectorAll('button')).filter((el) => !el.hidden && !el.disabled);

        const close = (restoreFocus = true) => {
            const focusTarget = pendingForm ? pendingForm.querySelector('button[type="submit"], button:not([type])') : null;
            pendingForm = null;
            confirmDialog.hidden = true;
            confirmDialog.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('ha-modal-lock');
            if (restoreFocus && focusTarget instanceof HTMLElement) focusTarget.focus();
        };

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-confirm]');
            if (!form || form.dataset.confirmed === 'true') return;

            event.preventDefault();
            pendingForm = form;
            message.textContent = form.dataset.confirm;

            // Optional item name (neighborhood / weather alert) shown as a themed chip.
            if (subject) {
                const label = form.dataset.confirmSubject;
                subject.hidden = !label;
                if (label) subject.textContent = label;
            }

            confirmDialog.hidden = false;
            confirmDialog.setAttribute('aria-hidden', 'false');
            document.body.classList.add('ha-modal-lock');
            (cancelButton || submitButton).focus();
        });

        // Escape closes; Tab is trapped inside the dialog.
        confirmDialog.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                close();
                return;
            }
            if (event.key !== 'Tab') return;

            const items = focusable();
            if (items.length < 2) return;
            const first = items[0];
            const last = items[items.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });

        confirmDialog.querySelectorAll('[data-confirm-cancel]').forEach((button) => button.addEventListener('click', () => close()));
        submitButton.addEventListener('click', () => {
            if (!pendingForm) return;
            pendingForm.dataset.confirmed = 'true';
            HTMLFormElement.prototype.submit.call(pendingForm);
            close(false);
        });
    }

    // Map imports
    if (document.querySelector('#mapOne')) {
        import('./components/map').then(module => module.initMap());
    }

    // Chart imports
    if (document.querySelector('#chartOne')) {
        import('./components/chart/chart-1').then(module => module.initChartOne());
    }
    if (document.querySelector('#chartTwo')) {
        import('./components/chart/chart-2').then(module => module.initChartTwo());
    }
    if (document.querySelector('#chartThree')) {
        import('./components/chart/chart-3').then(module => module.initChartThree());
    }
    if (document.querySelector('#chartSix')) {
        import('./components/chart/chart-6').then(module => module.initChartSix());
    }
    if (document.querySelector('#chartEight')) {
        import('./components/chart/chart-8').then(module => module.initChartEight());
    }
    if (document.querySelector('#chartThirteen')) {
        import('./components/chart/chart-13').then(module => module.initChartThirteen());
    }

    // Calendar init
    if (document.querySelector('#calendar')) {
        import('./components/calendar-init').then(module => module.calendarInit());
    }
});
