/**
 * Horloge du bandeau : date et heure dans la langue de la page (<html lang>),
 * formatées par le navigateur (Intl). Mise à jour à chaque changement de minute.
 *
 * Usage : <div class="clock" data-grr-clock></div>
 */
(function () {
    'use strict';

    function start(element) {
        var locale = document.documentElement.lang || undefined;
        var dateFormat, timeFormat;
        try {
            dateFormat = new Intl.DateTimeFormat(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            timeFormat = new Intl.DateTimeFormat(locale, { hour: '2-digit', minute: '2-digit' });
        } catch (e) { // langue inconnue du navigateur : format par défaut
            dateFormat = new Intl.DateTimeFormat(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            timeFormat = new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit' });
        }

        function render() {
            var now = new Date();
            var date = dateFormat.format(now);
            element.textContent = date.charAt(0).toUpperCase() + date.slice(1) + ' ' + timeFormat.format(now);
            element.setAttribute('datetime', now.toISOString());
            // Prochaine mise à jour au début de la minute suivante
            setTimeout(render, 60000 - (now.getSeconds() * 1000 + now.getMilliseconds()));
        }

        render();
    }

    function init() {
        var elements = document.querySelectorAll('[data-grr-clock]');
        for (var i = 0; i < elements.length; i++) {
            start(elements[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
