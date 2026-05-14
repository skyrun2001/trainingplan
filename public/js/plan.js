document.getElementById('tabs').addEventListener('click', e => {
  if (!e.target.classList.contains('tab')) return;
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.day').forEach(d => d.classList.remove('active'));
  e.target.classList.add('active');
  document.getElementById('day-' + e.target.dataset.day).classList.add('active');
  window.scrollTo({ top: 0, behavior: 'smooth' });
});

const _hash = location.hash.slice(1);
if (_hash) {
  const btn = document.querySelector(`.tab[data-day="${_hash}"]`);
  if (btn) btn.click();
}
