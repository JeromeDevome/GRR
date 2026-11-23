/**
 * Bandeau (templates/reservation/_header.twig) :
 *  - bouton #grr-header-toggle : masque / affiche le bandeau, choix mémorisé dans le cookie « grr_header » ;
 *  - bouton menu (mobile) : déplie / replie la liste des liens.
 */
(function () {
    'use strict';

    var COOKIE = 'grr_header';

    function init() {
        var header = document.getElementById('grr-header');
        if (!header) {
            return;
        }

        // Ancien cookie « open » (true/false) : supprimé, il n'est plus utilisé
        document.cookie = 'open=; max-age=0';

        var toggle = document.getElementById('grr-header-toggle');
        function syncToggle() {
            if (!toggle) {
                return;
            }
            var collapsed = header.classList.contains('is-collapsed');
            var label = toggle.getAttribute(collapsed ? 'data-label-show' : 'data-label-hide') || '';
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            toggle.setAttribute('aria-label', label);
            toggle.title = label;
        }
        syncToggle();
        if (toggle) {
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                var collapsed = header.classList.toggle('is-collapsed');
                document.cookie = COOKIE + '=' + (collapsed ? 'collapsed' : 'expanded') + '; max-age=31536000; SameSite=Lax';
                syncToggle();
            });
        }

        // Menus déroulants (compte, langues) : un seul ouvert à la fois, fermeture au clic extérieur / Échap
        var dropdowns = header.querySelectorAll('.grr-dropdown');
        function closeDropdowns(except) {
            for (var i = 0; i < dropdowns.length; i++) {
                if (dropdowns[i] !== except && dropdowns[i].classList.contains('is-open')) {
                    dropdowns[i].classList.remove('is-open');
                    dropdowns[i].querySelector('.grr-dropdown__toggle').setAttribute('aria-expanded', 'false');
                }
            }
        }
        for (var i = 0; i < dropdowns.length; i++) {
            (function (dropdown) {
                var button = dropdown.querySelector('.grr-dropdown__toggle');
                button.addEventListener('click', function (event) {
                    event.stopPropagation();
                    closeDropdowns(dropdown);
                    var open = dropdown.classList.toggle('is-open');
                    button.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (open) {
                        var first = dropdown.querySelector('.grr-dropdown__menu a');
                        if (first && event.detail === 0) { // ouverture au clavier : focus sur le premier lien
                            first.focus();
                        }
                    }
                });
            })(dropdowns[i]);
        }
        document.addEventListener('click', function (event) {
            if (!event.target.closest || !event.target.closest('.grr-dropdown')) {
                closeDropdowns(null);
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                var open = header.querySelector('.grr-dropdown.is-open');
                if (open) {
                    closeDropdowns(null);
                    open.querySelector('.grr-dropdown__toggle').focus();
                }
            }
        });

        var menuToggle = header.querySelector('.grr-header__menu-toggle');
        var nav = document.getElementById('grr-header-nav');
        if (menuToggle && nav) {
            menuToggle.addEventListener('click', function () {
                var open = nav.classList.toggle('is-open');
                menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && nav.classList.contains('is-open')) {
                    nav.classList.remove('is-open');
                    menuToggle.setAttribute('aria-expanded', 'false');
                    menuToggle.focus();
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
