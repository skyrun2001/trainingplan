const todayScheduledType = window.PAGE.todayScheduledType;
const todayHasSessions   = window.PAGE.todayHasSessions;
const typeLabels = {
  push:  'Push - Brust, Schulter, Trizeps',
  pull:  'Pull - Ruecken, Bizeps',
  legs:  'Beine - Komplett',
  upper: 'Upper - Schwachpunkte',
};

// ── Schedule editor ───────────────────────────────────────────────────────────
function toggleScheduleEditor() {
  document.getElementById('schedEditor').classList.toggle('open');
}

async function saveSchedule() {
  const weekSchedule = {};
  document.querySelectorAll('.sched-select').forEach(s => {
    weekSchedule[s.dataset.weekday] = s.value || null;
  });
  const r = await fetch('/api/settings', {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ weekSchedule }),
  });
  if (r.ok) {
    showToast('Wochenplan gespeichert');
    setTimeout(() => location.reload(), 600);
  } else {
    showToast('Fehler beim Speichern', 'error');
  }
}

// ── Notifications ─────────────────────────────────────────────────────────────
function updateNotifBadge() {
  const badge = document.getElementById('notifBadge');
  if (!badge) return;
  if (!('Notification' in window)) { badge.textContent = 'Nicht unterstuetzt'; return; }
  const perm = Notification.permission;
  badge.className = 'notif-badge' + (perm === 'granted' ? ' granted' : perm === 'denied' ? ' denied' : '');
  badge.textContent = perm === 'granted' ? 'Erlaubt' : perm === 'denied' ? 'Blockiert' : 'Klicken zum Erlauben';
}

async function requestNotifPermission() {
  if (!('Notification' in window)) { showToast('Browser unterstuetzt keine Benachrichtigungen', 'error'); return; }
  const perm = await Notification.requestPermission();
  updateNotifBadge();
  if (perm === 'granted') showToast('Benachrichtigungen erlaubt');
  else showToast('Benachrichtigungen blockiert', 'error');
}

async function saveReminderTime(val) {
  await fetch('/api/settings', {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ reminderTime: val || null }),
  });
  showToast(val ? `Erinnerung auf ${val} Uhr gesetzt` : 'Erinnerung deaktiviert');
  scheduleReminderCheck();
}

let reminderInterval = null;

function scheduleReminderCheck() {
  if (reminderInterval) clearInterval(reminderInterval);
  const timeInput = document.getElementById('reminderTime');
  if (!timeInput?.value) return;
  if (Notification.permission !== 'granted') return;

  reminderInterval = setInterval(() => {
    const now  = new Date();
    const hhmm = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    if (hhmm !== timeInput.value) return;
    if (!todayScheduledType || todayHasSessions) return;
    new Notification('Zeit fuers Training!', {
      body: `Heute: ${typeLabels[todayScheduledType] ?? todayScheduledType}. Viel Erfolg!`,
      icon: '/favicon.ico',
      tag:  'gym-reminder',
    });
  }, 60000);
}

// ── Progression chart ─────────────────────────────────────────────────────────
let chart = null;

function loadChart(name) {
  const canvas = document.getElementById('progressionChart');
  const empty  = document.getElementById('chartEmpty');
  if (!canvas) return;
  if (!name) {
    if (chart) chart.destroy();
    canvas.style.display = 'none';
    if (empty) empty.style.display = 'none';
    return;
  }

  fetch('/api/progression/' + encodeURIComponent(name))
    .then(r => r.json())
    .then(data => {
      if (!data?.length) {
        if (chart) chart.destroy();
        canvas.style.display = 'none';
        if (empty) empty.style.display = 'block';
        return;
      }
      if (empty) empty.style.display = 'none';
      canvas.style.display = 'block';
      if (chart) chart.destroy();
      chart = new Chart(canvas, {
        type: 'line',
        data: {
          labels: data.map(d => d.date),
          datasets: [
            {
              label: 'Max. Gewicht (kg)', data: data.map(d => d.maxWeight),
              borderColor: '#C9184A', backgroundColor: 'rgba(201,24,74,0.08)',
              fill: true, tension: 0.3, pointBackgroundColor: '#C9184A', pointRadius: 4, yAxisID: 'yWeight',
            },
            {
              label: 'Max. Wiederholungen', data: data.map(d => d.maxReps),
              borderColor: '#4361EE', fill: false, tension: 0.3,
              pointBackgroundColor: '#4361EE', pointRadius: 4, yAxisID: 'yReps', borderDash: [4, 3],
            },
          ],
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { labels: { color: '#888', font: { family: 'DM Sans', size: 12 }, boxWidth: 12 } },
            tooltip: { backgroundColor: '#1a1a1a', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, titleColor: '#E8E4DE', bodyColor: '#888' },
          },
          scales: {
            x:       { ticks: { color: '#555', font: { family: 'Space Mono', size: 10 }, maxTicksLimit: 8 }, grid: { color: 'rgba(255,255,255,0.04)' } },
            yWeight: { position: 'left',  ticks: { color: '#C9184A', font: { family: 'Space Mono', size: 10 }, callback: v => v + ' kg' }, grid: { color: 'rgba(255,255,255,0.04)' } },
            yReps:   { position: 'right', ticks: { color: '#4361EE', font: { family: 'Space Mono', size: 10 } }, grid: { drawOnChartArea: false } },
          },
        },
      });
    });
}

// Auto-select first exercise
const firstEx = document.getElementById('exerciseSelect');
if (firstEx?.options.length > 1) { firstEx.selectedIndex = 1; loadChart(firstEx.value); }

// ── Health Dashboard ──────────────────────────────────────────────────────────
let todayMetrics  = window.PAGE.todayMetrics;
const availableKeys = window.PAGE.availableKeys;
let hdConfig      = window.PAGE.healthDashboard;
let rawChartData  = window.PAGE.healthChartJson;

function hexToRgba(hex, a) {
  const r = parseInt(hex.slice(1, 3), 16), g = parseInt(hex.slice(3, 5), 16), b = parseInt(hex.slice(5, 7), 16);
  return `rgba(${r},${g},${b},${a})`;
}

const METRIC_DEFS = {
  steps:              { icon: '👣', color: '#6DB6FF', label: 'Schritte',   fmt: v => v >= 1000 ? (v / 1000).toFixed(1) + 'k' : String(Math.round(v)), chartType: 'bar'  },
  sleep_minutes:      { icon: '💤', color: '#9C84F7', label: 'Schlaf',     fmt: v => `${Math.floor(v / 60)}h ${Math.round(v % 60)}m`,                chartType: 'bar'  },
  active_minutes:     { icon: '🏃', color: '#22c55e', label: 'Aktiv Min',  fmt: v => Math.round(v) + 'min',                                          chartType: 'bar'  },
  weight_kg:          { icon: '⚖️', color: '#E0A03E', label: 'Gewicht',    fmt: v => v.toFixed(1) + ' kg',                                           chartType: 'line' },
  calories_kcal:      { icon: '🔥', color: '#FF8C42', label: 'Kalorien',   fmt: v => Math.round(v) + ' kcal',                                        chartType: 'bar'  },
  heart_rate_avg:     { icon: '❤️', color: '#ef4444', label: 'Herzrate',   fmt: v => Math.round(v) + ' bpm',                                         chartType: 'line' },
  heart_rate_resting: { icon: '💗', color: '#f87171', label: 'Ruhepuls',   fmt: v => Math.round(v) + ' bpm',                                         chartType: 'line' },
  distance_meters:    { icon: '📍', color: '#60a5fa', label: 'Distanz',    fmt: v => v >= 1000 ? (v / 1000).toFixed(2) + ' km' : Math.round(v) + ' m', chartType: 'bar' },
  vo2_max:            { icon: '💨', color: '#a78bfa', label: 'VO2 Max',    fmt: v => v.toFixed(1),                                                   chartType: 'line' },
};

function metaDef(key) {
  return METRIC_DEFS[key] ?? {
    icon: '📊', color: '#888888',
    label: key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()),
    fmt:  v => Number.isInteger(v) ? String(v) : v.toFixed(1),
    chartType: 'line',
  };
}

// ── Metric cards ──────────────────────────────────────────────────────────────
function renderCards() {
  const grid = document.getElementById('healthMetricsGrid');
  grid.innerHTML = '';
  if (!hdConfig.cards.length) {
    grid.innerHTML = '<p style="color:var(--muted);font-size:12px;grid-column:1/-1;text-align:center;margin:8px 0">Keine Karten — zum Anpassen</p>';
    return;
  }
  hdConfig.cards.forEach(({ metric }) => {
    const d   = metaDef(metric);
    const val = todayMetrics[metric] ?? null;
    const el  = document.createElement('div');
    el.className = 'health-card';
    el.innerHTML = `<div class="health-icon">${d.icon}</div>
<div class="health-value" style="color:${d.color}">${val !== null ? d.fmt(val) : '—'}</div>
<div class="health-label">${d.label}</div>`;
    grid.appendChild(el);
  });
}

// ── Charts ────────────────────────────────────────────────────────────────────
const hCharts = {};

function renderCharts() {
  const wrap = document.getElementById('healthChartsContainer');
  wrap.innerHTML = '';
  Object.values(hCharts).forEach(c => c.destroy());
  Object.keys(hCharts).forEach(k => delete hCharts[k]);

  const metrics = hdConfig.chartMetrics;
  if (!metrics.length || rawChartData.length < 2) return;

  const tabBar = document.createElement('div');
  tabBar.className = 'health-tab-bar';
  wrap.appendChild(tabBar);

  const baseOpts = {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { display: false },
      tooltip: { backgroundColor: '#1a1a1a', titleColor: '#E8E4DE', bodyColor: '#888', borderColor: 'rgba(255,255,255,0.08)', borderWidth: 1 },
    },
    scales: {
      x: { ticks: { color: '#555', font: { family: 'Space Mono', size: 9 }, maxTicksLimit: 12 }, grid: { color: 'rgba(255,255,255,0.04)' } },
      y: { ticks: { color: '#666', font: { family: 'Space Mono', size: 9 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
    },
  };

  const labels = rawChartData.map(row => { const [, m, day] = row.date.split('-'); return day + '.' + m; });

  metrics.forEach((metric, i) => {
    const d = metaDef(metric);

    const tab = document.createElement('button');
    tab.className = 'health-tab' + (i === 0 ? ' active' : '');
    tab.textContent = d.label;
    tab.onclick = () => switchHealthTab(metric, tab);
    tabBar.appendChild(tab);

    const panel = document.createElement('div');
    panel.className = 'health-chart-panel' + (i === 0 ? ' active' : '');
    panel.id = 'hpanel-' + metric;
    const cWrap = document.createElement('div');
    cWrap.className = 'health-chart-wrap';
    const canvas = document.createElement('canvas');
    canvas.id = 'hchart-' + metric;
    cWrap.appendChild(canvas);
    panel.appendChild(cWrap);
    wrap.appendChild(panel);

    const isLine = d.chartType === 'line';
    hCharts[metric] = new Chart(canvas, {
      type: isLine ? 'line' : 'bar',
      data: {
        labels,
        datasets: [{
          label: d.label,
          data:  rawChartData.map(row => row[metric] ?? null),
          borderColor:     d.color,
          backgroundColor: hexToRgba(d.color, isLine ? 0.12 : 0.5),
          fill: isLine, tension: 0.3,
          pointRadius:  isLine ? 3 : undefined,
          spanGaps:     true,
          borderRadius: isLine ? undefined : 3,
          borderWidth:  isLine ? 2 : 1,
        }],
      },
      options: baseOpts,
    });
  });
}

function switchHealthTab(metric, btn) {
  document.querySelectorAll('.health-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.health-chart-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('hpanel-' + metric)?.classList.add('active');
}

// ── Edit mode ─────────────────────────────────────────────────────────────────
function toggleHealthEdit() {
  const panel = document.getElementById('healthEditPanel');
  const btn   = document.getElementById('healthEditBtn');
  const open  = panel.style.display === 'none';
  panel.style.display = open ? 'block' : 'none';
  btn.textContent = open ? 'x' : 'Edit';
  if (open) renderEditChips();
}

function renderEditChips() {
  const cardChips  = document.getElementById('cardChips');
  const chartChips = document.getElementById('chartChips');
  cardChips.innerHTML = chartChips.innerHTML = '';

  const activeCards  = hdConfig.cards.map(c => c.metric);
  const activeCharts = hdConfig.chartMetrics;

  const mkChip = (key, isOn, toggle) => {
    const d    = metaDef(key);
    const chip = document.createElement('div');
    chip.className = 'edit-chip' + (isOn ? ' on' : '');
    chip.textContent = d.icon + ' ' + d.label;
    chip.onclick = () => toggle(chip);
    return chip;
  };

  availableKeys.forEach(key => {
    cardChips.appendChild(mkChip(key, activeCards.includes(key), chip => {
      const idx = hdConfig.cards.findIndex(c => c.metric === key);
      if (idx >= 0) hdConfig.cards.splice(idx, 1); else hdConfig.cards.push({ metric: key });
      chip.classList.toggle('on', hdConfig.cards.some(c => c.metric === key));
    }));
    chartChips.appendChild(mkChip(key, activeCharts.includes(key), chip => {
      const idx = hdConfig.chartMetrics.indexOf(key);
      if (idx >= 0) hdConfig.chartMetrics.splice(idx, 1); else hdConfig.chartMetrics.push(key);
      chip.classList.toggle('on', hdConfig.chartMetrics.includes(key));
    }));
  });

  document.querySelectorAll('.day-btn').forEach(b => {
    b.classList.toggle('active', parseInt(b.dataset.days) === hdConfig.chartDays);
  });
}

function setChartDays(days, btn) {
  hdConfig.chartDays = days;
  document.querySelectorAll('.day-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

async function saveHealthConfig() {
  const r = await fetch('/api/settings', {
    method: 'PATCH', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ healthDashboard: hdConfig }),
  });
  if (!r.ok) { showToast('Fehler beim Speichern', 'error'); return; }
  showToast('Dashboard gespeichert');
  toggleHealthEdit();
  const res = await fetch('/api/health/data?days=' + hdConfig.chartDays);
  rawChartData = await res.json();
  renderCards();
  renderCharts();
  renderEntryForm();
}

// ── Entry form ────────────────────────────────────────────────────────────────
function renderEntryForm() {
  const container = document.getElementById('healthEntryFields');
  container.innerHTML = '';
  const shown = [...new Set([...hdConfig.cards.map(c => c.metric), ...hdConfig.chartMetrics])];
  const keys  = shown.length ? shown : ['steps', 'weight_kg', 'calories_kcal', 'sleep_minutes', 'active_minutes'];
  keys.forEach(key => {
    const d   = metaDef(key);
    const cur = todayMetrics[key] ?? '';
    const grp = document.createElement('div');
    grp.className = 'health-input-group';
    grp.innerHTML = `<label>${d.icon} ${d.label}</label>
<input type="number" class="health-input health-entry-input" data-metric="${key}"
       placeholder="--" value="${cur !== '' ? cur : ''}" step="any" min="0">`;
    container.appendChild(grp);
  });
}

function toggleHealthForm() {
  const form  = document.getElementById('healthForm');
  const arrow = document.getElementById('healthToggleArrow');
  const open  = form.classList.toggle('open');
  arrow.textContent = open ? 'v' : '>';
}

async function saveHealthEntry() {
  const btn     = document.getElementById('healthSaveBtn');
  const msg     = document.getElementById('healthSaveMsg');
  const date    = document.getElementById('healthDate').value;
  const metrics = {};

  document.querySelectorAll('.health-entry-input').forEach(inp => {
    if (inp.value.trim() !== '') metrics[inp.dataset.metric] = parseFloat(inp.value);
  });

  if (!Object.keys(metrics).length) { showToast('Bitte mindestens einen Wert eingeben', 'error'); return; }

  btn.disabled = true;
  try {
    const r    = await fetch('/health/save', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ date, metrics }),
    });
    const data = await r.json();
    if (data.success) {
      Object.assign(todayMetrics, data.saved ?? {});
      msg.style.display = 'inline';
      setTimeout(() => { msg.style.display = 'none'; }, 3000);
      renderCards();
    } else {
      showToast(data.error ?? 'Fehler', 'error');
    }
  } catch { showToast('Verbindungsfehler', 'error'); }
  finally  { btn.disabled = false; }
}

// ── History delete ────────────────────────────────────────────────────────────
function deleteSession(id) {
  if (!confirm('Training wirklich loeschen?')) return;
  fetch('/log/' + id + '/delete', { method: 'DELETE' })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const el = document.getElementById('session-' + id);
        el.style.opacity = '0';
        el.style.transition = 'opacity 0.3s';
        setTimeout(() => { el.remove(); showToast('Training geloescht'); }, 300);
      }
    });
}

// ── Init ──────────────────────────────────────────────────────────────────────
updateNotifBadge();
scheduleReminderCheck();
renderCards();
renderCharts();
renderEntryForm();
