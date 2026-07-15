(function () {
  'use strict';

  // --- Menu burger (les deux pages) ---
  var toggle = document.getElementById('nav-toggle');
  var menu = document.getElementById('nav-menu');

  if (toggle && menu) {
    var setOpen = function (open) {
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
      menu.classList.toggle('is-open', open);
    };

    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    menu.addEventListener('click', function (e) {
      if (e.target.closest('a')) setOpen(false);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        setOpen(false);
        toggle.focus();
      }
    });

    // Le panneau est masqué en desktop : le rouvrir en repassant sous 861px surprendrait.
    window.matchMedia('(min-width: 861px)').addEventListener('change', function (e) {
      if (e.matches) setOpen(false);
    });
  }

  // --- Sommaire de la documentation (page /docs/ uniquement) ---
  var toc = document.getElementById('toc');
  if (!toc) return;

  // Replié en mobile ; en desktop il devient la colonne de gauche et reste ouvert.
  var deskToc = window.matchMedia('(min-width: 1024px)');
  var syncToc = function (mq) { toc.open = mq.matches; };
  syncToc(deskToc);
  deskToc.addEventListener('change', syncToc);

  // Surligne dans le sommaire la section en cours de lecture.
  var links = [].slice.call(toc.querySelectorAll('.toc__list a'));
  var targets = links
    .map(function (a) { return document.getElementById(a.getAttribute('href').slice(1)); })
    .filter(Boolean);

  if (!('IntersectionObserver' in window) || !targets.length) return;

  var visible = new Set();
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) visible.add(entry.target.id);
      else visible.delete(entry.target.id);
    });

    var current = targets.filter(function (t) { return visible.has(t.id); })[0];
    if (!current) return;

    links.forEach(function (a) {
      if (a.getAttribute('href') === '#' + current.id) a.setAttribute('aria-current', 'true');
      else a.removeAttribute('aria-current');
    });
  }, { rootMargin: '-80px 0px -70% 0px' });

  targets.forEach(function (t) { observer.observe(t); });
})();
