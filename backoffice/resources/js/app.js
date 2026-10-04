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
    const confirmDialog = document.querySelector('[data-confirm-dialog]');
    let pendingForm = null;

    if (confirmDialog) {
        const message = confirmDialog.querySelector('[data-confirm-message]');
        const close = () => {
            pendingForm = null;
            confirmDialog.hidden = true;
        };

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-confirm]');
            if (!form || form.dataset.confirmed === 'true') return;

            event.preventDefault();
            pendingForm = form;
            message.textContent = form.dataset.confirm;
            confirmDialog.hidden = false;
            confirmDialog.querySelector('[data-confirm-submit]').focus();
        });

        confirmDialog.querySelectorAll('[data-confirm-cancel]').forEach((button) => button.addEventListener('click', close));
        confirmDialog.querySelector('[data-confirm-submit]').addEventListener('click', () => {
            if (!pendingForm) return;
            pendingForm.dataset.confirmed = 'true';
            HTMLFormElement.prototype.submit.call(pendingForm);
            close();
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
