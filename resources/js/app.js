import React from 'react';
import { createRoot } from 'react-dom/client';
import { GooeyToaster, gooeyToast } from 'goey-toast';
import 'goey-toast/styles.css';
import Chart from 'chart.js/auto';
import DataTable from 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';
import '@fontsource/nunito/latin-400.css';
import '@fontsource/nunito/latin-600.css';
import '@fontsource/nunito/latin-700.css';
import '@fontsource/nunito/latin-800.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';
import 'datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css';
import * as bootstrap from 'bootstrap';

window.Chart = Chart;
window.DataTable = DataTable;
window.bootstrap = bootstrap;

const sharedToastOptions = {
    preset: 'subtle',
    showProgress: true,
};

function toastSurfaceOptions() {
    const dark = document.documentElement.dataset.theme === 'dark';

    return {
        fillColor: dark ? '#182232' : '#FFFFFF',
        borderColor: dark ? '#2B3A50' : '#D8E7ED',
        borderWidth: 1,
    };
}

function appToast(type, title, options = {}) {
    const toast = typeof gooeyToast[type] === 'function' ? gooeyToast[type] : gooeyToast;

    return toast(title, { ...sharedToastOptions, ...toastSurfaceOptions(), ...options });
}

window.appToast = {
    success: (title, options) => appToast('success', title, options),
    error: (title, options) => appToast('error', title, options),
    warning: (title, options) => appToast('warning', title, options),
    info: (title, options) => appToast('info', title, options),
    loading: (title, options) => appToast('info', title, { duration: Infinity, ...options }),
    update: (id, options) => gooeyToast.update(id, options),
    dismiss: id => gooeyToast.dismiss(id),
    confirm: (title, description, onConfirm) => appToast('warning', title, {
        description,
        duration: Infinity,
        action: {
            label: 'Lanjutkan',
            onClick: onConfirm,
        },
    }),
};

function AppToastRoot() {
    const [theme, setTheme] = React.useState(() => document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light');

    React.useEffect(() => {
        const syncTheme = () => setTheme(document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light');
        window.addEventListener('theme-changed', syncTheme);

        return () => window.removeEventListener('theme-changed', syncTheme);
    }, []);

    return React.createElement(GooeyToaster, {
        position: 'bottom-right',
        theme,
        closeButton: true,
        preset: 'subtle',
        showProgress: true,
        swipeToDismiss: true,
        maxQueue: 5,
        queueOverflow: 'drop-oldest',
        showTimestamp: false,
    });
}

const toastRootElement = document.getElementById('app-toast-root');
if (toastRootElement) {
    createRoot(toastRootElement).render(React.createElement(AppToastRoot));
    window.dispatchEvent(new CustomEvent('app-toast-ready'));
}
