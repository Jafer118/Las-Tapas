(function () {
  const pages = document.querySelectorAll('.page');
  const navigationButtons = document.querySelectorAll('[data-page]');
  const validPageNames = ['home', 'menu', 'contact'];
  const navigationLinks = document.getElementById('nav-links');
  const navigationToggle = document.getElementById('nav-toggle');

  function showPage(pageName, options = {}) {
    if (!validPageNames.includes(pageName)) {
      pageName = 'home';
    }

    pages.forEach((page) => {
      page.classList.toggle('active', page.id === `page-${pageName}`);
    });

    navigationButtons.forEach((button) => {
      if (!button.closest('nav.links')) {
        return;
      }

      if (button.dataset.page === pageName) {
        button.setAttribute('aria-current', 'page');
      } else {
        button.removeAttribute('aria-current');
      }
    });

    if (!options.skipScroll) {
      window.scrollTo({
        top: 0,
        behavior: 'instant' in window ? 'instant' : 'auto',
      });
    }

    navigationLinks?.classList.remove('open');
  }

  navigationButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const pageName = button.dataset.page;
      if (location.hash !== `#${pageName}`) {
        location.hash = pageName;
      } else {
        showPage(pageName);
      }
    });
  });

  window.addEventListener('hashchange', () => {
    showPage(location.hash.replace('#', '') || 'home');
  });

  if (navigationToggle && navigationLinks) {
    navigationToggle.addEventListener('click', () => {
      const isOpen = navigationLinks.classList.toggle('open');
      navigationToggle.setAttribute('aria-expanded', String(isOpen));
    });
  }

  showPage(location.hash.replace('#', '') || 'home', { skipScroll: true });

  const menuTabs = document.querySelectorAll('.menu-tabs button');
  const menuPanels = document.querySelectorAll('.menu-panel');

  menuTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      menuTabs.forEach((menuTab) => {
        menuTab.setAttribute('aria-selected', 'false');
      });
      menuPanels.forEach((panel) => {
        panel.classList.remove('active');
      });

      tab.setAttribute('aria-selected', 'true');
      document.getElementById(tab.dataset.target)?.classList.add('active');
    });
  });

  const currentDay = new Date().getDay();
  const todayRow = document.querySelector(`.hours-table tr[data-day="${currentDay}"]`);
  if (todayRow) {
    todayRow.classList.add('today');

    const timeCell = todayRow.querySelector('td:last-child');
    const todayHours = document.getElementById('today-hours');
    if (timeCell && todayHours) {
      todayHours.textContent = timeCell.textContent;
    }
  }
})();
