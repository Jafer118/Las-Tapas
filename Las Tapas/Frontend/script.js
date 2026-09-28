// Simple page router (Home / Menu / Contact)
(function () {
  var pages = document.querySelectorAll('.page');
  var navButtons = document.querySelectorAll('[data-page]');
  var validPages = ['home', 'menu', 'contact'];

  function showPage(name, opts) {
    if (validPages.indexOf(name) === -1) name = 'home';
    pages.forEach(function (p) {
      p.classList.toggle('active', p.id === 'page-' + name);
    });
    navButtons.forEach(function (b) {
      if (b.closest('nav.links')) {
        if (b.dataset.page === name) b.setAttribute('aria-current', 'page');
        else b.removeAttribute('aria-current');
      }
    });
    if (!opts || !opts.skipScroll) window.scrollTo({ top: 0, behavior: 'instant' in window ? 'instant' : 'auto' });
    var navLinks = document.getElementById('nav-links');
    if (navLinks) navLinks.classList.remove('open');
  }

  navButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var name = btn.dataset.page;
      if (location.hash !== '#' + name) {
        location.hash = name;
      } else {
        showPage(name);
      }
    });
  });

  window.addEventListener('hashchange', function () {
    showPage(location.hash.replace('#', '') || 'home');
  });

  // Mobile nav toggle
  var toggle = document.getElementById('nav-toggle');
  var navLinksEl = document.getElementById('nav-links');
  if (toggle && navLinksEl) {
    toggle.addEventListener('click', function () {
      var open = navLinksEl.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // Initial route
  showPage(location.hash.replace('#', '') || 'home', { skipScroll: true });
})();

// Tab switching for menu categories
(function () {
  var tabs = document.querySelectorAll('.menu-tabs button');
  var panels = document.querySelectorAll('.menu-panel');
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.setAttribute('aria-selected', 'false'); });
      panels.forEach(function (p) { p.classList.remove('active'); });
      tab.setAttribute('aria-selected', 'true');
      var target = document.getElementById(tab.dataset.target);
      if (target) target.classList.add('active');
    });
  });
})();

// Highlight today's opening hours row + update hero strip
(function () {
  try {
    var dayIndex = new Date().getDay(); // 0 = Sunday
    var row = document.querySelector('.hours-table tr[data-day="' + dayIndex + '"]');
    if (row) {
      row.classList.add('today');
      var timeCell = row.querySelector('td:last-child');
      var heroTime = document.getElementById('today-hours');
      if (heroTime && timeCell) heroTime.textContent = timeCell.textContent;
    }
  } catch (e) {
    // fail silently, static hours already shown
  }
})();