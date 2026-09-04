document.addEventListener('DOMContentLoaded', function () {
  // Mobile nav toggle
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.querySelector('.main-nav');
  if (toggle && nav) {
    var setNavOpen = function (open) {
      nav.classList.toggle('open', open);
      document.body.classList.toggle('nav-open', open);
      document.documentElement.classList.toggle('nav-lock', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
      toggle.innerHTML = open ? '<i class="fa-solid fa-xmark"></i>' : '<i class="fa-solid fa-bars"></i>';
    };
    toggle.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      setNavOpen(!nav.classList.contains('open'));
    });
    // Auto-close the menu once a nav link is tapped
    nav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () { setNavOpen(false); });
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 900 && nav.classList.contains('open')) setNavOpen(false);
    });
    window.addEventListener('pageshow', function () {
      if (!nav.classList.contains('open')) {
        document.body.classList.remove('nav-open');
        document.documentElement.classList.remove('nav-lock');
      }
    });
  }

  // Admin sidebar toggle
  var adminToggle = document.querySelector('.mobile-menu-toggle');
  var sidebar = document.querySelector('.admin-sidebar');
  if (adminToggle && sidebar) {
    adminToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  // Auto-dismiss flash alerts
  document.querySelectorAll('.alert').forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = 'opacity 0.4s ease';
      alert.style.opacity = '0';
      setTimeout(function () { alert.remove(); }, 400);
    }, 6000);
  });

});
