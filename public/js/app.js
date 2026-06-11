// CSRF defence: the server rejects mutating requests without this header
// (see RequireXhrHeaderSubscriber), so attach it to every same-origin fetch.
const _origFetch = window.fetch.bind(window);
window.fetch = (input, init = {}) => {
  const url = typeof input === 'string' ? input : (input && input.url) || '';
  const isSameOrigin = !/^https?:\/\//i.test(url) || url.startsWith(location.origin);
  if (isSameOrigin) {
    const headers = new Headers(init.headers || (input instanceof Request ? input.headers : undefined));
    headers.set('X-Requested-With', 'XMLHttpRequest');
    init = { ...init, headers };
  }
  return _origFetch(input, init);
};

function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast ' + type;
  setTimeout(() => t.classList.add('show'), 10);
  setTimeout(() => t.classList.remove('show'), 2800);
}

function toggleLocaleMenu() {
    document.getElementById('localeSwitcher').classList.toggle('open');
}

// close when clicking outside
document.addEventListener('click', function (event) {
    const el = document.getElementById('localeSwitcher');

    if (!el.contains(event.target)) {
        el.classList.remove('open');
    }
});
