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
