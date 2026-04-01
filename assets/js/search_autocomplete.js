/**
 * SwiftBite — Live Search Autocomplete
 * Works on any <input> tagged with [data-autocomplete]
 * Endpoint: actions/search_autocomplete.php?q=...
 */

(function () {
  'use strict';

  /* ── helpers ─────────────────────────────────────────── */
  function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  }

  /** Wrap matched text in <mark> */
  function highlight(text, query) {
    if (!query) return escHtml(text);
    const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    return escHtml(text).replace(re, '<mark>$1</mark>');
  }

  function escHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ── resolve endpoint base path ─────────────────────── */
  function resolveEndpoint() {
    // works from any depth (root, subdirectory…)
    const scripts = document.querySelectorAll('script[src]');
    for (const s of scripts) {
      const src = s.getAttribute('src');
      if (src && src.includes('search_autocomplete.js')) {
        // src =  …/assets/js/search_autocomplete.js  → strip assets/js/…
        return src.replace(/assets\/js\/search_autocomplete\.js.*$/, '') + 'actions/search_autocomplete.php';
      }
    }
    // fallback: assume root
    return 'actions/search_autocomplete.php';
  }

  const ENDPOINT = resolveEndpoint();

  /* ── catEmoji map ────────────────────────────────────── */
  const CAT_EMOJI = {
    Burgers: '🍔', Pizza: '🍕', Sushi: '🍣', Noodles: '🍜',
    Salads: '🥗', Desserts: '🍰', Chicken: '🍗', Drinks: '🥤',
    Seafood: '🦐', Pasta: '🍝', BBQ: '🥩', Breakfast: '🥞',
    Sandwich: '🥪', Soup: '🍲', Rice: '🍚',
  };

  /* ── build dropdown HTML ─────────────────────────────── */
  function buildDropdown(data, query) {
    const { foods = [], restaurants = [], categories = [] } = data;
    const total = foods.length + restaurants.length + categories.length;
    if (total === 0) return '';

    let html = '<ul class="ac-list" role="listbox">';

    /* — Search action row — */
    html += `
      <li class="ac-item ac-search-row" role="option" data-url="menu.php?keyword=${encodeURIComponent(query)}">
        <span class="ac-icon">🔍</span>
        <span class="ac-label">Search for <strong>${escHtml(query)}</strong></span>
        <span class="ac-meta ac-arrow">↵</span>
      </li>`;

    /* — Categories — */
    if (categories.length) {
      html += `<li class="ac-group-label">Categories</li>`;
      categories.forEach(c => {
        const emoji = CAT_EMOJI[c.name] ?? '🍴';
        html += `
          <li class="ac-item" role="option" data-url="${escHtml(c.url)}">
            <span class="ac-icon">${emoji}</span>
            <span class="ac-label">${highlight(c.name, query)}</span>
            <span class="ac-meta">${c.count} items</span>
          </li>`;
      });
    }

    /* — Foods — */
    if (foods.length) {
      html += `<li class="ac-group-label">Food Items</li>`;
      foods.forEach(f => {
        const thumb = f.image_path
          ? `<img src="${escHtml(f.image_path)}" alt="" class="ac-thumb" loading="lazy">`
          : `<span class="ac-icon">${escHtml(f.emoji)}</span>`;
        html += `
          <li class="ac-item" role="option" data-url="${escHtml(f.url)}">
            ${thumb}
            <span class="ac-label">
              ${highlight(f.name, query)}
              <small>${escHtml(f.category)}</small>
            </span>
            <span class="ac-meta">${escHtml(f.price)}</span>
          </li>`;
      });
    }

    /* — Restaurants — */
    if (restaurants.length) {
      html += `<li class="ac-group-label">Restaurants</li>`;
      restaurants.forEach(r => {
        const logo = r.logo_url
          ? `<img src="${escHtml(r.logo_url)}" alt="" class="ac-thumb" loading="lazy">`
          : `<span class="ac-icon">🏠</span>`;
        html += `
          <li class="ac-item" role="option" data-url="${escHtml(r.url)}">
            ${logo}
            <span class="ac-label">
              ${highlight(r.name, query)}
              <small>${escHtml(r.cuisine)}${r.city ? ' · ' + escHtml(r.city) : ''}</small>
            </span>
          </li>`;
      });
    }

    html += '</ul>';
    return html;
  }

  /* ── main init ───────────────────────────────────────── */
  function initAutocomplete(input) {
    let controller = null;   // AbortController for in-flight requests
    let activeIndex = -1;
    let dropdownEl = null;
    let lastQuery = '';

    /* create dropdown container */
    const wrap = input.closest('.ac-wrap') || input.parentElement;
    wrap.style.position = 'relative';
    dropdownEl = document.createElement('div');
    dropdownEl.className = 'ac-dropdown';
    dropdownEl.setAttribute('role', 'listbox');
    wrap.appendChild(dropdownEl);

    function closeDropdown() {
      dropdownEl.innerHTML = '';
      dropdownEl.classList.remove('is-open');
      activeIndex = -1;
    }

    function openDropdown(html) {
      dropdownEl.innerHTML = html;
      dropdownEl.classList.add('is-open');
      activeIndex = -1;
      /* click inside list items */
      dropdownEl.querySelectorAll('.ac-item').forEach(item => {
        item.addEventListener('mousedown', e => {
          e.preventDefault();
          const url = item.dataset.url;
          if (url) window.location.href = url;
        });
      });
    }

    function navigateItems(dir) {
      const items = [...dropdownEl.querySelectorAll('.ac-item')];
      if (!items.length) return;
      items.forEach(i => i.classList.remove('ac-active'));
      activeIndex += dir;
      if (activeIndex < 0) activeIndex = items.length - 1;
      if (activeIndex >= items.length) activeIndex = 0;
      items[activeIndex].classList.add('ac-active');
      items[activeIndex].scrollIntoView({ block: 'nearest' });
    }

    const fetchSuggestions = debounce(async (q) => {
      if (controller) controller.abort();
      controller = new AbortController();
      try {
        const res = await fetch(`${ENDPOINT}?q=${encodeURIComponent(q)}`, {
          signal: controller.signal,
        });
        if (!res.ok) return;
        const data = await res.json();
        openDropdown(buildDropdown(data, q));
      } catch (err) {
        if (err.name !== 'AbortError') console.warn('[AC]', err);
      }
    }, 220);

    /* ── events ── */
    input.addEventListener('input', () => {
      const q = input.value.trim();
      lastQuery = q;
      if (q.length < 2) { closeDropdown(); return; }
      fetchSuggestions(q);
    });

    input.addEventListener('keydown', e => {
      if (!dropdownEl.classList.contains('is-open')) return;
      switch (e.key) {
        case 'ArrowDown': e.preventDefault(); navigateItems(1); break;
        case 'ArrowUp':   e.preventDefault(); navigateItems(-1); break;
        case 'Enter': {
          const active = dropdownEl.querySelector('.ac-item.ac-active');
          if (active && active.dataset.url) {
            e.preventDefault();
            window.location.href = active.dataset.url;
          }
          break;
        }
        case 'Escape': closeDropdown(); input.blur(); break;
      }
    });

    input.addEventListener('focus', () => {
      const q = input.value.trim();
      if (q.length >= 2) fetchSuggestions(q);
    });

    document.addEventListener('click', e => {
      if (!wrap.contains(e.target)) closeDropdown();
    });
  }

  /* ── auto-init all tagged inputs ────────────────────── */
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[data-autocomplete]').forEach(initAutocomplete);
  });
})();
