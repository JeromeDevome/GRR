(function () {
    'use strict';

    var banner = document.getElementById('grr-install-banner');
    var helpDialog = document.getElementById('grr-install-help');
    if (!banner || !helpDialog) {
        return;
    }

    var installPrompt = null;
    var storageKey = 'grr-install-dismissed-until';
    var installed = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    function isDismissed() {
        try {
            return Number(window.localStorage.getItem(storageKey) || 0) > Date.now();
        } catch (error) {
            console.warn('GRR installation preference could not be read.', error);
            return false;
        }
    }

    function dismissForLater() {
        try {
            window.localStorage.setItem(storageKey, String(Date.now() + 30 * 24 * 60 * 60 * 1000));
        } catch (error) {
            console.warn('GRR installation preference could not be saved.', error);
        }
        banner.hidden = true;
    }

    function showBanner() {
        if (!installed && !isDismissed()) {
            banner.hidden = false;
        }
    }

    function closeHelp() {
        if (typeof helpDialog.close === 'function' && helpDialog.open) {
            helpDialog.close();
        } else {
            helpDialog.removeAttribute('open');
        }
    }

    function showHelp() {
        if (typeof helpDialog.showModal === 'function') {
            helpDialog.showModal();
        } else {
            helpDialog.setAttribute('open', 'open');
        }
    }

    if ('serviceWorker' in navigator && window.isSecureContext) {
        navigator.serviceWorker.register('./service-worker.js').catch(function (error) {
            console.error('GRR service worker registration failed.', error);
        });
    }

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        installPrompt = event;
        showBanner();
    });

    window.addEventListener('appinstalled', function () {
        installed = true;
        banner.hidden = true;
        installPrompt = null;
    });

    banner.querySelector('[data-pwa-install]').addEventListener('click', async function () {
        if (!installPrompt) {
            showHelp();
            return;
        }

        var prompt = installPrompt;
        installPrompt = null;
        await prompt.prompt();
        var choice = await prompt.userChoice;
        if (choice.outcome === 'accepted') {
            banner.hidden = true;
        } else {
            showHelp();
        }
    });

    banner.querySelector('[data-pwa-later]').addEventListener('click', dismissForLater);
    helpDialog.querySelectorAll('[data-pwa-close]').forEach(function (button) {
        button.addEventListener('click', closeHelp);
    });

    if (!installed && !isDismissed()) {
        window.setTimeout(showBanner, 2500);
    }
}());
