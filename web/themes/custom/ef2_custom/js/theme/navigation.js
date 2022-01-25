let _ = require('lodash');

const mainMenu = document.querySelector('.menu--main');
const menuToggle = document.getElementById('menu-toggle');

if (!_.isNil(mainMenu)) {
    const submenus = mainMenu.getElementsByClassName('menu-item--expanded');

    // Close all submenus when clicking outside submenu
    if (!_.isEmpty(submenus)) {
        document.onclick = function (e) {
            if (!e.target.classList.contains('menu') && !e.target.classList.contains('menu-item--expanded-toggle')) {
                closeAllSubmenus();
            }
        }
    }

    // Submenu open and close
    Array.from(submenus).forEach(submenu => {
        let submenuButton = submenu.querySelector('button');

        submenuButton.addEventListener('click', (e) => {

            if (submenuButton.getAttribute('aria-expanded') !== 'true') {
                closeAllSubmenus();

                submenu.classList.add('open');
                submenuButton.setAttribute('aria-expanded', 'true');

                return;
            }

            submenu.classList.remove('open');
            submenuButton.setAttribute('aria-expanded', 'false');
        });
    });

    function closeAllSubmenus() {
        Array.from(submenus).forEach(submenu => {
            submenu.querySelector('button').setAttribute('aria-expanded', 'false');

            submenu.classList.remove('open');
        });
    }

    // Hamburger menu toggle
    if (!_.isNil(menuToggle)) {
        menuToggle.addEventListener('click', function () {
            if (menuToggle.getAttribute('aria-expanded') === 'true') {
                menuToggle.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('main-menu-active')
                mainMenu.classList.remove('main-menu-open');
            } else {
                menuToggle.setAttribute('aria-expanded', 'true');
                document.body.classList.add('main-menu-active')
                mainMenu.classList.add('main-menu-open');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                menuToggle.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('main-menu-active');
                mainMenu.classList.remove('main-menu-open')
            }
        });
    }
}