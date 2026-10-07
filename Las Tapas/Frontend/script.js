/* =========================================================
   LAS TAPAS - COMPLETE WEBSITE JAVASCRIPT
========================================================= */

document.addEventListener("DOMContentLoaded", function () {


  /* =======================================================
     PAGINA ROUTER
  ======================================================= */

  const pages = document.querySelectorAll(".page");
  const navButtons = document.querySelectorAll("[data-page]");

  const validPages = [
    "home",
    "menu",
    "contact"
  ];


  function showPage(name) {

    if (!validPages.includes(name)) {
      name = "home";
    }


    pages.forEach(function (page) {

      page.classList.toggle(
        "active",
        page.id === "page-" + name
      );

    });


    navButtons.forEach(function (button) {

      if (button.closest("nav.links")) {

        if (button.dataset.page === name) {

          button.setAttribute(
            "aria-current",
            "page"
          );

        } else {

          button.removeAttribute(
            "aria-current"
          );

        }

      }

    });


    window.scrollTo({
      top: 0,
      behavior: "smooth"
    });


    const navLinks =
      document.getElementById("nav-links");

    const navToggle =
      document.getElementById("nav-toggle");


    if (navLinks) {
      navLinks.classList.remove("open");
    }


    if (navToggle) {

      navToggle.setAttribute(
        "aria-expanded",
        "false"
      );

    }

  }


  navButtons.forEach(function (button) {

    button.addEventListener(
      "click",
      function () {

        const page =
          button.dataset.page;

        if (location.hash !== "#" + page) {

          location.hash = page;

        } else {

          showPage(page);

        }

      }
    );

  });


  window.addEventListener(
    "hashchange",
    function () {

      showPage(
        location.hash.replace("#", "") || "home"
      );

    }
  );


  showPage(
    location.hash.replace("#", "") || "home"
  );


  /* =======================================================
     MOBIELE NAVIGATIE
  ======================================================= */

  const navToggle =
    document.getElementById("nav-toggle");

  const navLinks =
    document.getElementById("nav-links");


  if (navToggle && navLinks) {

    navToggle.addEventListener(
      "click",
      function () {

        const open =
          navLinks.classList.toggle("open");


        navToggle.setAttribute(
          "aria-expanded",
          open ? "true" : "false"
        );

      }
    );

  }


  /* =======================================================
     MENU TABS
  ======================================================= */

  const tabs =
    document.querySelectorAll(
      ".menu-tabs button"
    );

  const panels =
    document.querySelectorAll(
      ".menu-panel"
    );


  tabs.forEach(function (tab) {

    tab.addEventListener(
      "click",
      function () {

        tabs.forEach(function (other) {

          other.classList.remove("active");

        });


        panels.forEach(function (panel) {

          panel.classList.remove("active");

        });


        tab.classList.add("active");


        const target =
          document.getElementById(
            tab.dataset.target
          );


        if (target) {

          target.classList.add("active");

        }

      }
    );

  });


  /* =======================================================
     FORMULES OP MENU
  ======================================================= */

  const menuFormulaCards =
    document.querySelectorAll(
      ".ayce-card"
    );


  menuFormulaCards.forEach(function (card) {

    card.addEventListener(
      "click",
      function () {

        menuFormulaCards.forEach(function (other) {

          other.classList.remove("selected");

          const label =
            other.querySelector(".choose-formula");

          if (label) {

            label.textContent =
              "Kies deze formule →";

          }

        });


        card.classList.add("selected");


        const label =
          card.querySelector(".choose-formula");


        if (label) {

          label.textContent =
            "Geselecteerd ✓";

        }


        /* Zelfde formule ook in reservering selecteren */

        const formula =
          card.dataset.formula;


        const reservationFormula =
          document.querySelector(
            '.booking-formula[data-formula="' +
            formula +
            '"]'
          );


        if (reservationFormula) {

          document
            .querySelectorAll(".booking-formula")
            .forEach(function (button) {

              button.classList.remove("active");

            });


          reservationFormula.classList.add(
            "active"
          );

          updateReservationSummary();

        }

      }
    );

  });


  /* =======================================================
     GERECHTEN + / -
  ======================================================= */

  const dishes =
    document.querySelectorAll(".dish");


  const selectedDishes = {};


  dishes.forEach(function (dish) {

    const name =
      dish.dataset.name;


    const minus =
      dish.querySelector(".minus");

    const plus =
      dish.querySelector(".plus");

    const number =
      dish.querySelector(
        ".quantity-control strong"
      );


    selectedDishes[name] = 0;


    if (plus) {

      plus.addEventListener(
        "click",
        function () {

          selectedDishes[name]++;

          number.textContent =
            selectedDishes[name];

          updateOrderSummary();

        }
      );

    }


    if (minus) {

      minus.addEventListener(
        "click",
        function () {

          if (selectedDishes[name] > 0) {

            selectedDishes[name]--;

          }


          number.textContent =
            selectedDishes[name];

          updateOrderSummary();

        }
      );

    }

  });


  function updateOrderSummary() {

    const summary =
      document.getElementById(
        "order-summary"
      );

    const total =
      document.getElementById(
        "total-dishes"
      );


    if (!summary || !total) {
      return;
    }


    let totalDishes = 0;

    let html = "";


    Object.keys(selectedDishes).forEach(
      function (name) {

        const quantity =
          selectedDishes[name];


        if (quantity > 0) {

          totalDishes += quantity;


          html += `
            <div class="summary-item">
              <span>
                ${quantity} × ${name}
              </span>

              <span>
                ${quantity}
              </span>
            </div>
          `;

        }

      }
    );


    if (totalDishes === 0) {

      summary.innerHTML = `
        <p class="summary-empty">
          Nog geen gerechten gekozen.
        </p>
      `;

    } else {

      summary.innerHTML = html;

    }


    total.textContent =
      totalDishes;

  }


  /* =======================================================
     RESERVERING VARIABELEN
  ======================================================= */

  let guests = 2;

  let selectedTable = null;

  let selectedPayment = "iDEAL";

  let selectedFormula = {
    name: "3 uur tafelen",
    price: 36.50
  };


  /* =======================================================
     FORMULE KIEZEN
  ======================================================= */

  const bookingFormulas =
    document.querySelectorAll(
      ".booking-formula"
    );


  bookingFormulas.forEach(function (button) {

    button.addEventListener(
      "click",
      function () {

        bookingFormulas.forEach(
          function (other) {

            other.classList.remove(
              "active"
            );

          }
        );


        button.classList.add(
          "active"
        );


        selectedFormula = {

          name:
            button.dataset.formula,

          price:
            Number(button.dataset.price)

        };


        updateReservationSummary();

      }
    );

  });


  /* =======================================================
     PERSONEN
  ======================================================= */

  const guestNumber =
    document.getElementById(
      "guest-number"
    );

  const minusPerson =
    document.getElementById(
      "minus-person"
    );

  const plusPerson =
    document.getElementById(
      "plus-person"
    );


  function updateGuests() {

    if (guestNumber) {

      guestNumber.textContent =
        guests;

    }


    const tableGuestNumber =
      document.getElementById(
        "table-guest-number"
      );


    if (tableGuestNumber) {

      tableGuestNumber.textContent =
        guests;

    }


    updateTables();

    updateReservationSummary();

  }


  if (plusPerson) {

    plusPerson.addEventListener(
      "click",
      function () {

        if (guests < 10) {

          guests++;

          updateGuests();

        }

      }
    );

  }


  if (minusPerson) {

    minusPerson.addEventListener(
      "click",
      function () {

        if (guests > 1) {

          guests--;

          updateGuests();

        }

      }
    );

  }


  /* =======================================================
     TAFELS
  ======================================================= */

  const tableContainer =
    document.getElementById(
      "tables"
    );


  /*
    Capaciteiten gebaseerd op de plattegrond:

    1-3  = 2 personen
    4-7  = 4 personen
    8-10 = 6 personen
    11-26 = 8 personen
  */

  const tableCapacities = {

    1: 2,
    2: 2,
    3: 2,

    4: 4,
    5: 4,
    6: 4,
    7: 4,

    8: 6,
    9: 6,
    10: 6,

    11: 8,
    12: 8,
    13: 8,
    14: 8,

    15: 8,
    16: 8,
    17: 8,
    18: 8,

    19: 8,
    20: 8,
    21: 8,
    22: 8,

    23: 8,
    24: 8,
    25: 8,
    26: 8

  };


  function createTables() {

    if (!tableContainer) {
      return;
    }


    tableContainer.innerHTML = "";


    for (
      let number = 1;
      number <= 26;
      number++
    ) {

      const capacity =
        tableCapacities[number];


      const button =
        document.createElement("button");


      button.type = "button";

      button.className =
        "table-option";

      button.dataset.table =
        "Tafel " + number;

      button.dataset.capacity =
        capacity;


      button.innerHTML = `
        <span class="table-icon">●</span>
        <strong>Tafel ${number}</strong>
        <small>${capacity} personen</small>
      `;


      button.addEventListener(
        "click",
        function () {

          if (
            Number(button.dataset.capacity) <
            guests
          ) {

            return;

          }


          document
            .querySelectorAll(".table-option")
            .forEach(function (other) {

              other.classList.remove(
                "selected"
              );

            });


          button.classList.add(
            "selected"
          );


          selectedTable =
            button.dataset.table;


          updateReservationSummary();

        }
      );


      tableContainer.appendChild(
        button
      );

    }


    updateTables();

  }


  function updateTables() {

    document
      .querySelectorAll(".table-option")
      .forEach(function (button) {

        const capacity =
          Number(button.dataset.capacity);


        if (capacity < guests) {

          button.classList.add(
            "disabled"
          );

        } else {

          button.classList.remove(
            "disabled"
          );

        }

      });


    if (
      selectedTable
    ) {

      const selected =
        document.querySelector(
          '.table-option[data-table="' +
          selectedTable +
          '"]'
        );


      if (
        selected &&
        Number(selected.dataset.capacity) <
        guests
      ) {

        selected.classList.remove(
          "selected"
        );

        selectedTable = null;

      }

    }

  }


  createTables();


  /* =======================================================
     DATUM MINIMAAL VANDAAG
  ======================================================= */

  const dateInput =
    document.getElementById(
      "date"
    );


  if (dateInput) {

    const today =
      new Date();


    const year =
      today.getFullYear();


    const month =
      String(
        today.getMonth() + 1
      ).padStart(2, "0");


    const day =
      String(
        today.getDate()
      ).padStart(2, "0");


    dateInput.min =
      `${year}-${month}-${day}`;

  }


  /* =======================================================
     DATUM / TIJD
  ======================================================= */

  const timeInput =
    document.getElementById(
      "time"
    );


  if (dateInput) {

    dateInput.addEventListener(
      "change",
      updateReservationSummary
    );

  }


  if (timeInput) {

    timeInput.addEventListener(
      "change",
      updateReservationSummary
    );

  }


  /* =======================================================
     RESERVERINGS OVERZICHT
  ======================================================= */

  function updateReservationSummary() {

    const formula =
      document.getElementById(
        "summary-formula"
      );

    const date =
      document.getElementById(
        "summary-date"
      );

    const time =
      document.getElementById(
        "summary-time"
      );

    const guest =
      document.getElementById(
        "summary-guests"
      );

    const table =
      document.getElementById(
        "summary-table"
      );

    const total =
      document.getElementById(
        "summary-total"
      );

    const calculation =
      document.getElementById(
        "summary-calculation"
      );


    if (formula) {

      formula.textContent =
        selectedFormula.name;

    }


    if (dateInput && dateInput.value) {

      const dateObject =
        new Date(
          dateInput.value +
          "T12:00:00"
        );


      date.textContent =
        dateObject.toLocaleDateString(
          "nl-NL",
          {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
          }
        );

    } else if (date) {

      date.textContent =
        "Nog niet gekozen";

    }


    if (time) {

      time.textContent =
        timeInput && timeInput.value
          ? timeInput.value
          : "Nog niet gekozen";

    }


    if (guest) {

      guest.textContent =
        guests + " personen";

    }


    if (table) {

      table.textContent =
        selectedTable ||
        "Nog niet gekozen";

    }


    const totalPrice =
      selectedFormula.price *
      guests;


    if (total) {

      total.textContent =
        formatPrice(totalPrice);

    }


    if (calculation) {

      calculation.textContent =
        `${guests} personen × ${formatPrice(selectedFormula.price)}`;

    }

  }


  function formatPrice(price) {

    return new Intl.NumberFormat(
      "nl-NL",
      {
        style: "currency",
        currency: "EUR"
      }
    ).format(price);

  }


  updateReservationSummary();


  /* =======================================================
     RESERVERING SUBMIT
  ======================================================= */

  const reservationForm =
    document.getElementById(
      "reservation-form"
    );


  if (reservationForm) {

    reservationForm.addEventListener(
      "submit",
      function (event) {

        event.preventDefault();


        if (!reservationForm.checkValidity()) {

          reservationForm.reportValidity();

          return;

        }


        if (!selectedTable) {

          alert(
            "Kies eerst een tafel voordat je doorgaat."
          );

          return;

        }


        const name =
          document.getElementById(
            "name"
          ).value.trim();


        const phone =
          document.getElementById(
            "phone"
          ).value.trim();


        const email =
          document.getElementById(
            "email"
          ).value.trim();


        const message =
          document.getElementById(
            "message"
          ).value.trim();


        /* Betalingsgegevens vullen */

        document.getElementById(
          "pay-formula"
        ).textContent =
          selectedFormula.name;


        document.getElementById(
          "pay-guests"
        ).textContent =
          guests;


        document.getElementById(
          "pay-table"
        ).textContent =
          selectedTable;


        document.getElementById(
          "pay-total"
        ).textContent =
          formatPrice(
            selectedFormula.price *
            guests
          );


        /* Betaling tonen */

        reservationForm.style.display =
          "none";


        document.getElementById(
          "payment-screen"
        ).style.display =
          "block";


        document.getElementById(
          "success-screen"
        ).style.display =
          "none";


        /* Stap indicator */

        document
          .getElementById("step-reservation")
          .classList.remove("active");


        document
          .getElementById("step-payment")
          .classList.add("active");


        window.scrollTo({
          top: 0,
          behavior: "smooth"
        });


        /* Gegevens bewaren */

        reservationForm.dataset.name =
          name;

        reservationForm.dataset.phone =
          phone;

        reservationForm.dataset.email =
          email;

        reservationForm.dataset.message =
          message;

      }
    );

  }


  /* =======================================================
     BETAALMETHODE
  ======================================================= */

  const paymentMethods =
    document.querySelectorAll(
      ".payment-method"
    );


  paymentMethods.forEach(function (method) {

    method.addEventListener(
      "click",
      function () {

        paymentMethods.forEach(
          function (other) {

            other.classList.remove(
              "active"
            );

          }
        );


        method.classList.add(
          "active"
        );


        selectedPayment =
          method.dataset.payment;


        const payMethod =
          document.getElementById(
            "pay-method"
          );


        if (payMethod) {

          payMethod.textContent =
            selectedPayment;

        }

      }
    );

  });


  /* =======================================================
     TERUG NAAR RESERVERING
  ======================================================= */

  const backButton =
    document.getElementById(
      "back-to-reservation"
    );


  if (backButton) {

    backButton.addEventListener(
      "click",
      function () {

        document.getElementById(
          "payment-screen"
        ).style.display =
          "none";


        reservationForm.style.display =
          "block";


        document
          .getElementById("step-payment")
          .classList.remove("active");


        document
          .getElementById("step-reservation")
          .classList.add("active");

      }
    );

  }


  /* =======================================================
     DEMO BETALING
  ======================================================= */

  const demoPay =
    document.getElementById(
      "demo-pay"
    );


  if (demoPay) {

    demoPay.addEventListener(
      "click",
      function () {

        const name =
          reservationForm.dataset.name ||
          document.getElementById(
            "name"
          ).value;


        const date =
          document.getElementById(
            "date"
          ).value;


        const time =
          document.getElementById(
            "time"
          ).value;


        /* Succesgegevens */

        document.getElementById(
          "success-name"
        ).textContent =
          name;


        const dateObject =
          new Date(
            date + "T12:00:00"
          );


        document.getElementById(
          "success-date"
        ).textContent =
          dateObject.toLocaleDateString(
            "nl-NL",
            {
              day: "2-digit",
              month: "2-digit",
              year: "numeric"
            }
          );


        document.getElementById(
          "success-time"
        ).textContent =
          time;


        document.getElementById(
          "success-table"
        ).textContent =
          selectedTable;


        document.getElementById(
          "success-guests"
        ).textContent =
          guests +
          " personen";


        document.getElementById(
          "success-formula"
        ).textContent =
          selectedFormula.name;


        document.getElementById(
          "success-total"
        ).textContent =
          formatPrice(
            selectedFormula.price *
            guests
          );


        /* Schermen */

        document.getElementById(
          "payment-screen"
        ).style.display =
          "none";


        document.getElementById(
          "success-screen"
        ).style.display =
          "block";


        document
          .getElementById("step-payment")
          .classList.remove("active");


        document
          .getElementById("step-confirmation")
          .classList.add("active");


        window.scrollTo({
          top: 0,
          behavior: "smooth"
        });

      }
    );

  }


  /* =======================================================
     NIEUWE RESERVERING
  ======================================================= */

  const newReservation =
    document.getElementById(
      "new-reservation"
    );


  if (newReservation) {

    newReservation.addEventListener(
      "click",
      function () {

        reservationForm.reset();


        reservationForm.style.display =
          "block";


        document.getElementById(
          "payment-screen"
        ).style.display =
          "none";


        document.getElementById(
          "success-screen"
        ).style.display =
          "none";


        guests = 2;

        selectedTable = null;


        selectedFormula = {
          name: "3 uur tafelen",
          price: 36.50
        };


        /* Formule reset */

        bookingFormulas.forEach(
          function (button) {

            button.classList.remove(
              "active"
            );

            if (
              button.dataset.formula ===
              "3 uur tafelen"
            ) {

              button.classList.add(
                "active"
              );

            }

          }
        );


        /* Gerechten reset */

        Object.keys(selectedDishes)
          .forEach(function (name) {

            selectedDishes[name] = 0;

          });


        dishes.forEach(function (dish) {

          const number =
            dish.querySelector(
              ".quantity-control strong"
            );

          if (number) {

            number.textContent = "0";

          }

        });


        updateOrderSummary();

        updateGuests();

        updateReservationSummary();


        document
          .getElementById("step-confirmation")
          .classList.remove("active");


        document
          .getElementById("step-payment")
          .classList.remove("active");


        document
          .getElementById("step-reservation")
          .classList.add("active");


        window.scrollTo({
          top: 0,
          behavior: "smooth"
        });

      }
    );

  }


  /* =======================================================
     OPENINGSTIJDEN
  ======================================================= */

  const today =
    new Date().getDay();


  const openingHours = {

    0: "13:00 – 21:00",
    1: "Gesloten",
    2: "17:00 – 22:30",
    3: "17:00 – 22:30",
    4: "17:00 – 23:00",
    5: "17:00 – 23:30",
    6: "13:00 – 23:30"

  };


  const todayHours =
    document.getElementById(
      "today-hours"
    );


  if (todayHours) {

    todayHours.textContent =
      openingHours[today];

  }


});