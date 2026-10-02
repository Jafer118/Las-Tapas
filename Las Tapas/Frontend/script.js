/* =========================================================
   LAS TAPAS - WEBSITE JAVASCRIPT
========================================================= */


/* =========================================================
   PAGE ROUTER
========================================================= */

(function () {

  var pages = document.querySelectorAll('.page');
  var navButtons = document.querySelectorAll('[data-page]');
  var validPages = ['home', 'menu', 'contact'];


  function showPage(name, opts) {

    if (validPages.indexOf(name) === -1) {
      name = 'home';
    }


    pages.forEach(function (page) {

      page.classList.toggle(
        'active',
        page.id === 'page-' + name
      );

    });


    navButtons.forEach(function (button) {

      if (button.closest('nav.links')) {

        if (button.dataset.page === name) {

          button.setAttribute(
            'aria-current',
            'page'
          );

        } else {

          button.removeAttribute(
            'aria-current'
          );

        }

      }

    });


    if (!opts || !opts.skipScroll) {

      window.scrollTo({
        top: 0,
        behavior:
          'instant' in window
            ? 'instant'
            : 'auto'
      });

    }


    var navLinks =
      document.getElementById('nav-links');

    if (navLinks) {
      navLinks.classList.remove('open');
    }


    var toggle =
      document.getElementById('nav-toggle');

    if (toggle) {

      toggle.setAttribute(
        'aria-expanded',
        'false'
      );

    }

  }


  navButtons.forEach(function (button) {

    button.addEventListener(
      'click',
      function () {

        var name =
          button.dataset.page;


        if (
          location.hash !== '#' + name
        ) {

          location.hash = name;

        } else {

          showPage(name);

        }

      }
    );

  });


  window.addEventListener(
    'hashchange',
    function () {

      showPage(
        location.hash.replace('#', '') || 'home'
      );

    }
  );


  /* =======================================================
     MOBILE NAV
  ======================================================= */

  var toggle =
    document.getElementById('nav-toggle');

  var navLinksEl =
    document.getElementById('nav-links');


  if (toggle && navLinksEl) {

    toggle.addEventListener(
      'click',
      function () {

        var open =
          navLinksEl.classList.toggle('open');


        toggle.setAttribute(
          'aria-expanded',
          open ? 'true' : 'false'
        );

      }
    );

  }


  /* =======================================================
     INITIAL ROUTE
  ======================================================= */

  showPage(
    location.hash.replace('#', '') || 'home',
    {
      skipScroll: true
    }
  );

})();



/* =========================================================
   MENU TABS
========================================================= */

(function () {

  var tabs =
    document.querySelectorAll(
      '.menu-tabs button'
    );

  var panels =
    document.querySelectorAll(
      '.menu-panel'
    );


  tabs.forEach(function (tab) {

    tab.addEventListener(
      'click',
      function () {

        tabs.forEach(
          function (otherTab) {

            otherTab.setAttribute(
              'aria-selected',
              'false'
            );

          }
        );


        panels.forEach(
          function (panel) {

            panel.classList.remove(
              'active'
            );

          }
        );


        tab.setAttribute(
          'aria-selected',
          'true'
        );


        var target =
          document.getElementById(
            tab.dataset.target
          );


        if (target) {

          target.classList.add(
            'active'
          );

        }

      }
    );

  });

})();



/* =========================================================
   OPENINGSTIJDEN
========================================================= */

(function () {

  try {

    var dayIndex =
      new Date().getDay();

    var row =
      document.querySelector(
        '.hours-table tr[data-day="' +
        dayIndex +
        '"]'
      );


    if (row) {

      row.classList.add('today');


      var timeCell =
        row.querySelector(
          'td:last-child'
        );


      var heroTime =
        document.getElementById(
          'today-hours'
        );


      if (
        heroTime &&
        timeCell
      ) {

        heroTime.textContent =
          timeCell.textContent;

      }

    }

  } catch (error) {

    // Statische openingstijden blijven zichtbaar.

  }

})();



/* =========================================================
   WEEKEND LIVE
   Alleen zaterdag en zondag
========================================================= */

(function () {

  var weekendLive =
    document.getElementById(
      'weekend-live'
    );


  if (!weekendLive) {
    return;
  }


  var today =
    new Date().getDay();


  var isWeekend =
    today === 0 ||
    today === 6;


  if (!isWeekend) {

    weekendLive.classList.add(
      'weekend-hidden'
    );

  }

})();



/* =========================================================
   RESERVERINGSKNOPPEN
   "Tafel reserveren" -> Contact + formulier
========================================================= */

(function () {

  var reservationButtons =
    document.querySelectorAll(
      '[data-reservation], .reservation-button'
    );


  reservationButtons.forEach(
    function (button) {

      button.addEventListener(
        'click',
        function (event) {

          event.preventDefault();

          location.hash = 'contact';


          setTimeout(
            function () {

              var reservation =
                document.getElementById(
                  'reserveren'
                );


              if (reservation) {

                reservation.scrollIntoView({
                  behavior: 'smooth'
                });

              }

            },
            100
          );

        }
      );

    }
  );

})();



/* =========================================================
   TAFEL RESERVEREN
========================================================= */

(function () {

  var form =
    document.getElementById(
      'reservation-form'
    );


  if (!form) {
    return;
  }


  var dateInput =
    document.getElementById(
      'reservation-date'
    );


  var success =
    document.getElementById(
      'reservation-success'
    );


  var newReservation =
    document.getElementById(
      'new-reservation'
    );


  /* -----------------------------------------
     Datum minimaal vandaag
  ----------------------------------------- */

  if (dateInput) {

    var today =
      new Date();


    var year =
      today.getFullYear();


    var month =
      String(
        today.getMonth() + 1
      ).padStart(2, '0');


    var day =
      String(
        today.getDate()
      ).padStart(2, '0');


    var todayString =
      year +
      '-' +
      month +
      '-' +
      day;


    dateInput.min =
      todayString;

  }


  /* -----------------------------------------
     Formulier versturen
  ----------------------------------------- */

  form.addEventListener(
    'submit',
    function (event) {

      event.preventDefault();


      if (!form.checkValidity()) {

        form.reportValidity();

        return;

      }


      var name =
        document.getElementById(
          'reservation-name'
        ).value.trim();


      var phone =
        document.getElementById(
          'reservation-phone'
        ).value.trim();


      var email =
        document.getElementById(
          'reservation-email'
        ).value.trim();


      var guests =
        document.getElementById(
          'reservation-guests'
        ).value;


      var date =
        document.getElementById(
          'reservation-date'
        ).value;


      var time =
        document.getElementById(
          'reservation-time'
        ).value;


      var message =
        document.getElementById(
          'reservation-message'
        ).value.trim();


      /* Datum netjes weergeven */

      var dateObject =
        new Date(
          date + 'T12:00:00'
        );


      var formattedDate =
        dateObject.toLocaleDateString(
          'nl-NL',
          {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          }
        );


      /* E-mailinhoud */

      var subject =
        'Reserveringsaanvraag Las Tapas - ' +
        formattedDate +
        ' ' +
        time;


      var body =
        'NIEUWE RESERVERING LAS TAPAS' +
        '\n\n' +

        'Naam: ' +
        name +
        '\n' +

        'Telefoon: ' +
        phone +
        '\n' +

        'E-mail: ' +
        email +
        '\n\n' +

        'Datum: ' +
        formattedDate +
        '\n' +

        'Tijd: ' +
        time +
        '\n' +

        'Aantal personen: ' +
        guests +
        '\n\n' +

        'Opmerking:' +
        '\n' +
        (message || 'Geen opmerking') +
        '\n\n' +

        'Verzonden via de Las Tapas website.';


      /* Mailprogramma openen */

      var mailto =
        'mailto:reservas@lastapas.es' +
        '?subject=' +
        encodeURIComponent(subject) +
        '&body=' +
        encodeURIComponent(body);


      window.location.href =
        mailto;


      /* Formulier verbergen */

      form.hidden = true;


      success.hidden = false;

    }
  );


  /* -----------------------------------------
     Nieuwe reservering
  ----------------------------------------- */

  if (newReservation) {

    newReservation.addEventListener(
      'click',
      function () {

        form.reset();

        form.hidden = false;

        success.hidden = true;

      }
    );

  }

})();