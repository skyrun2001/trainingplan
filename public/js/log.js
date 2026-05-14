const TYPE      = window.PAGE.type;
const EXERCISES = window.PAGE.exercises;
let prevWeights = {};

// ── Previous weights ──────────────────────────────────────────────────────────
fetch('/api/last-weights/' + TYPE)
  .then(r => r.json())
  .then(data => {
    prevWeights = data;
    document.querySelectorAll('.exercise-card').forEach((card, idx) => {
      const name = card.dataset.name;
      const hint = document.getElementById('prev-' + idx);
      if (hint && data[name]) {
        const span = document.createElement('span');
        span.textContent = `${data[name].weight} kg x ${data[name].reps} Wdh (${data[name].date})`;
        hint.textContent = 'Letzte Session: ';
        hint.appendChild(span);
        card.querySelectorAll('.weight-input').forEach(inp => {
          inp.value = data[name].weight;
          inp.classList.add('filled');
        });
      } else if (hint) {
        const span = document.createElement('span');
        span.textContent = 'Noch keine Daten';
        hint.textContent = 'Letzte Session: ';
        hint.appendChild(span);
      }
    });
  })
  .catch(() => {});

// ── Set expand / collapse ─────────────────────────────────────────────────────
function toggleSets(header) {
  const card      = header.closest('.exercise-card');
  const idx       = [...document.querySelectorAll('.exercise-card')].indexOf(card);
  const container = document.getElementById('sets-' + idx);
  const chevron   = document.getElementById('chevron-' + idx);
  const isOpen    = container.classList.toggle('open');
  chevron.classList.toggle('open', isOpen);
}

// ── Add / remove sets ─────────────────────────────────────────────────────────
function addSet(idx) {
  const rows      = document.getElementById('rows-' + idx);
  const setNum    = rows.querySelectorAll('.set-row').length + 1;
  const prevWeight = parseFloat(rows.querySelector('.weight-input:last-of-type')?.value) || '';
  const prevReps   = parseInt(rows.querySelector('.reps-input:last-of-type')?.value, 10)  || '';

  const row = document.createElement('div');
  row.className = 'set-row';

  const label = document.createElement('span');
  label.className = 'set-label';
  label.textContent = setNum;

  const repsIn = document.createElement('input');
  Object.assign(repsIn, { type:'number', className:'set-input reps-input', placeholder:'Wdh', min:1, max:99, value: prevReps });
  repsIn.dataset.set = setNum;
  repsIn.setAttribute('oninput', `updateSummary(${idx})`);

  const weightIn = document.createElement('input');
  Object.assign(weightIn, { type:'number', className:'set-input weight-input', placeholder:'kg', min:0, max:999, step:0.5, value: prevWeight });
  weightIn.dataset.set = setNum;
  weightIn.setAttribute('oninput', `onWeightInput(this); updateSummary(${idx})`);

  const rpeIn = document.createElement('select');
  rpeIn.className = 'set-input rpe-input';
  rpeIn.dataset.set = setNum;
  rpeIn.setAttribute('onchange', 'onRpeChange(this)');
  [['', '--'], ['6', '6 - easy'], ['7', '7 - moderat'], ['8', '8 - schwer'], ['9', '9 - sehr schwer'], ['10', '10 - max']]
    .forEach(([v, t]) => {
      const o = document.createElement('option');
      o.value = v; o.textContent = t;
      rpeIn.appendChild(o);
    });

  const removeBtn = document.createElement('button');
  removeBtn.className = 'remove-set';
  removeBtn.title = 'Satz loeschen';
  removeBtn.textContent = 'x';
  removeBtn.setAttribute('onclick', 'removeSet(this)');

  row.append(label, repsIn, weightIn, rpeIn, removeBtn);
  rows.appendChild(row);
  if (prevWeight) row.querySelector('.weight-input').classList.add('filled');
  updateSummary(idx);
}

function removeSet(btn) {
  const row  = btn.closest('.set-row');
  const rows = row.closest('.set-rows');
  if (rows.querySelectorAll('.set-row').length <= 1) return;
  row.remove();
  rows.querySelectorAll('.set-row').forEach((r, i) => {
    r.querySelector('.set-label').textContent = i + 1;
  });
  const card = rows.closest('.exercise-card');
  const idx  = [...document.querySelectorAll('.exercise-card')].indexOf(card);
  updateSummary(idx);
}

// ── Input helpers ─────────────────────────────────────────────────────────────
function onWeightInput(inp) {
  inp.classList.toggle('filled', inp.value !== '');
}

function onRpeChange(sel) {
  const v = parseInt(sel.value);
  sel.style.color = !v ? '' : v >= 10 ? '#C9184A' : v >= 8 ? '#E0A03E' : '#4CAF7D';
}

// ── Summary + progress ────────────────────────────────────────────────────────
function updateSummary(idx) {
  const rows     = document.getElementById('rows-' + idx);
  const setCount = rows.querySelectorAll('.set-row').length;
  const weights  = [...rows.querySelectorAll('.weight-input')].map(i => parseFloat(i.value)).filter(v => !isNaN(v));
  const reps     = [...rows.querySelectorAll('.reps-input')].map(i => parseInt(i.value)).filter(v => !isNaN(v));
  let summary = `${setCount} Saetze`;
  if (reps.length > 0)    summary += ` - ${Math.min(...reps)}-${Math.max(...reps)} Wdh`;
  if (weights.length > 0) summary += ` - ${Math.max(...weights)} kg`;
  document.getElementById('summary-' + idx).textContent = summary;
  updateProgress();
}

function updateProgress() {
  const total  = document.querySelectorAll('.exercise-card').length;
  const filled = [...document.querySelectorAll('.exercise-card')].filter(card =>
    [...card.querySelectorAll('.weight-input')].some(i => i.value !== '')
  ).length;
  const pct = total ? Math.round((filled / total) * 100) : 0;
  document.getElementById('progressBar').style.width = pct + '%';
}

// ── Save workout ──────────────────────────────────────────────────────────────
function saveWorkout() {
  const btn = document.getElementById('saveBtn');
  btn.disabled = true;
  btn.textContent = 'Speichern...';

  const exercises = {};
  document.querySelectorAll('.exercise-card').forEach((card) => {
    const name = card.dataset.name;
    const sets = [];
    card.querySelectorAll('.set-row').forEach((row, setIdx) => {
      const reps   = row.querySelector('.reps-input')?.value;
      const weight = row.querySelector('.weight-input')?.value;
      const rpe    = row.querySelector('.rpe-input')?.value;
      if (reps || weight) {
        sets.push({
          set:    setIdx + 1,
          reps:   reps   ? parseInt(reps)    : null,
          weight: weight ? parseFloat(weight) : null,
          rpe:    rpe    ? parseInt(rpe)     : null,
        });
      }
    });
    if (sets.length > 0) exercises[name] = sets;
  });

  if (Object.keys(exercises).length === 0) {
    showToast('Bitte mindestens eine Uebung eintragen.', 'error');
    btn.disabled = false;
    btn.textContent = 'Training speichern';
    return;
  }

  const payload = {
    type:      TYPE,
    date:      document.getElementById('metaDate').value,
    duration:  parseInt(document.getElementById('metaDuration').value) || null,
    notes:     document.getElementById('metaNotes').value || null,
    exercises,
  };

  fetch('/log/save', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(payload),
  })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast('Training gespeichert!', 'success');
        setTimeout(() => { window.location.href = '/dashboard'; }, 1200);
      } else {
        throw new Error(data.error || 'Fehler');
      }
    })
    .catch(err => {
      showToast('Fehler beim Speichern: ' + err.message, 'error');
      btn.disabled = false;
      btn.textContent = 'Training speichern';
    });
}

// Init: open first exercise
document.querySelector('.ex-header')?.click();
