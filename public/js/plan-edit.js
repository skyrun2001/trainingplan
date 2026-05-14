// ── Tab switching ─────────────────────────────────────────────────────────────
document.getElementById('tabs').addEventListener('click', e => {
  if (!e.target.classList.contains('tab')) return;
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.day-edit').forEach(d => d.classList.remove('active'));
  e.target.classList.add('active');
  document.getElementById('day-' + e.target.dataset.day).classList.add('active');
  window.scrollTo({ top: 0, behavior: 'smooth' });
});

const _hash = location.hash.slice(1);
if (_hash) {
  const btn = document.querySelector(`.tab[data-day="${_hash}"]`);
  if (btn) btn.click();
}

// ── Day form ──────────────────────────────────────────────────────────────────
function toggleDayForm(id) {
  document.getElementById('day-form-' + id).classList.toggle('open');
}

async function saveDayForm(id) {
  const payload = {
    label: document.getElementById('day-label-' + id).value,
    focus: document.getElementById('day-focus-' + id).value,
    note:  document.getElementById('day-note-' + id).value,
  };
  const r = await fetch('/api/plan/days/' + id, {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(payload),
  });
  if (r.ok) {
    showToast('Tag gespeichert ✓');
    toggleDayForm(id);
  } else {
    showToast('Fehler beim Speichern', 'error');
  }
}

// ── Exercise inline form ──────────────────────────────────────────────────────
function toggleExForm(id) {
  document.querySelectorAll('.ex-inline-form').forEach(f => {
    if (f.id !== 'ex-form-' + id) f.classList.remove('open');
  });
  document.getElementById('ex-form-' + id).classList.toggle('open');
}

async function saveEx(id) {
  const payload = {
    name:            document.getElementById('ex-name-' + id).value.trim(),
    section:         document.getElementById('ex-section-' + id).value.trim(),
    defaultSets:     parseInt(document.getElementById('ex-sets-' + id).value),
    defaultReps:     document.getElementById('ex-reps-' + id).value || null,
    progressionNote: document.getElementById('ex-prog-' + id).value || null,
    isNew:           document.getElementById('ex-new-' + id).checked,
  };
  if (!payload.name) { showToast('Name darf nicht leer sein', 'error'); return; }

  const r = await fetch('/api/plan/exercises/' + id, {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(payload),
  });
  if (!r.ok) { showToast('Fehler beim Speichern', 'error'); return; }

  const data = await r.json();
  const item = document.getElementById('ex-item-' + id);
  item.querySelector('.ex-edit-name').textContent = data.name;
  item.querySelector('.ex-edit-meta').textContent =
    data.defaultSets + '× ' + (data.defaultReps ? data.defaultReps + ' Wdh' : '—') + ' · ' + data.section;

  const badge = item.querySelector('.prog-badge');
  if (data.progressionNote) {
    if (badge) {
      badge.textContent = data.progressionNote;
      badge.className   = 'prog-badge' + (data.isNew ? ' is-new' : '');
    } else {
      const b = document.createElement('span');
      b.className   = 'prog-badge' + (data.isNew ? ' is-new' : '');
      b.textContent = data.progressionNote;
      item.querySelector('.ex-edit-info').appendChild(b);
    }
  } else if (badge) {
    badge.remove();
  }

  toggleExForm(id);
  showToast('Übung gespeichert ✓');
}

async function deleteEx(id) {
  if (!confirm('Übung wirklich löschen?')) return;
  const r = await fetch('/api/plan/exercises/' + id, { method: 'DELETE' });
  if (r.ok) {
    const el = document.getElementById('ex-item-' + id);
    el.style.opacity = '0';
    el.style.transition = 'opacity 0.3s';
    setTimeout(() => { el.remove(); showToast('Übung gelöscht'); }, 300);
  } else {
    showToast('Fehler beim Löschen', 'error');
  }
}

async function moveEx(id, direction) {
  const r = await fetch('/api/plan/exercises/' + id + '/move', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ direction }),
  });
  if (r.ok) {
    location.reload();
  } else {
    showToast('Fehler beim Verschieben', 'error');
  }
}

// ── Add / delete day ──────────────────────────────────────────────────────────
function toggleAddDayForm() {
  const form = document.getElementById('addDayForm');
  const tab  = document.getElementById('addDayTab');
  const open = form.style.display === 'none';
  form.style.display = open ? 'block' : 'none';
  tab.classList.toggle('active', open);
  if (open) document.getElementById('new-day-label').focus();
}

function selectColor(color) {
  document.getElementById('new-day-color').value = color;
  document.querySelectorAll('#colorPicker button').forEach(b => {
    b.style.borderColor = b.dataset.color === color ? '#fff' : 'transparent';
  });
}

async function addDay() {
  const label = document.getElementById('new-day-label').value.trim();
  const type  = document.getElementById('new-day-type').value.trim();
  if (!label) { showToast('Bezeichnung ist erforderlich', 'error'); return; }
  if (!type)  { showToast('Kürzel ist erforderlich', 'error'); return; }

  const r = await fetch('/api/plan/days', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      label,
      type,
      color: document.getElementById('new-day-color').value,
      focus: document.getElementById('new-day-focus').value.trim() || null,
    }),
  });
  const data = await r.json();
  if (!r.ok) { showToast(data.error || 'Fehler', 'error'); return; }
  showToast('Tag hinzugefügt ✓');
  location.href = '/plans/edit#' + data.type;
}

async function deleteDay(id, label) {
  if (!confirm(`Tag „${label}“ und alle seine Übungen löschen?`)) return;
  const r = await fetch('/api/plan/days/' + id, { method: 'DELETE' });
  const data = await r.json();
  if (!r.ok) { showToast(data.error || 'Fehler', 'error'); return; }
  showToast('Tag gelöscht');
  location.reload();
}

async function addExercise(dayId) {
  const name = document.getElementById('new-name-' + dayId).value.trim();
  if (!name) { showToast('Name ist erforderlich', 'error'); return; }

  const payload = {
    name,
    section:         document.getElementById('new-section-' + dayId).value.trim() || 'Hauptteil',
    defaultSets:     parseInt(document.getElementById('new-sets-' + dayId).value) || 3,
    defaultReps:     document.getElementById('new-reps-' + dayId).value || null,
    progressionNote: document.getElementById('new-prog-' + dayId).value || null,
    isNew:           false,
  };

  const r = await fetch('/api/plan/days/' + dayId + '/exercises', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(payload),
  });
  if (!r.ok) { showToast('Fehler beim Hinzufügen', 'error'); return; }
  location.reload();
}

// Init
selectColor('#C9184A');
