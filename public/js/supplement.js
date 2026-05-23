/**
 * Supplement tracker UI
 */
'use strict';

const Suppl = (() => {
    let items = (window.PAGE && window.PAGE.supplements) ? [...window.PAGE.supplements] : [];
    let restockId = null;

    const TIMING_META = {
        morning:      { label: 'Morning',     icon: '🌅' },
        evening:      { label: 'Evening',     icon: '🌙' },
        pre_training: { label: 'Pre-workout', icon: '⚡' },
        post_training:{ label: 'Post-workout',icon: '✅' },
    };

    // ── Render ────────────────────────────────────────────────────────────────

    function render() {
        const grid = document.getElementById('supplGrid');
        if (!grid) return;

        if (items.length === 0) {
            grid.innerHTML = '<p class="empty-hint">No supplements yet. Add your first one!</p>';
            updateBanner();
            return;
        }

        grid.innerHTML = items.map(cardHtml).join('');
        updateBanner();
    }

    function cardHtml(s) {
        const pct    = s.stockPercent;
        const status = s.stockStatus;  // 'ok' | 'low' | 'empty'
        const badgeClass = status === 'empty' ? 'badge-empty' : status === 'low' ? 'badge-low' : 'badge-ok';
        const badgeText  = status === 'empty' ? 'Empty' : status === 'low' ? 'Low' : 'OK';
        const barColor   = status === 'empty' ? 'var(--push)' : status === 'low' ? 'var(--warn)' : 'var(--accent)';
        const daysText   = s.isEmpty ? '—' : s.daysRemaining < 1 ? '< 1 day' : `${s.daysRemaining} days`;
        const unitLabel  = s.unit || 'servings';
        const subLine    = s.dosage ? `${s.dosage}${s.unit ? ' · ' + s.unit : ''}` : (s.unit || '');

        const timingHtml = (s.schedule && s.schedule.length)
            ? `<div class="suppl-timings">${s.schedule.map(t => {
                const m = TIMING_META[t];
                return m ? `<span class="suppl-timing-chip">${m.icon} ${m.label}</span>` : '';
              }).join('')}</div>`
            : '';

        return `
<div class="suppl-card" data-id="${s.id}">
  <div class="suppl-card-top">
    <div class="suppl-card-info">
      <div class="suppl-name">${esc(s.name)}</div>
      ${subLine ? `<div class="suppl-sub">${esc(subLine)}</div>` : ''}
      ${timingHtml}
    </div>
    <span class="suppl-badge ${badgeClass}">${badgeText}</span>
  </div>

  <div class="suppl-progress-wrap">
    <div class="suppl-progress-bar" style="width:${pct}%;background:${barColor}"></div>
  </div>
  <div class="suppl-progress-labels">
    <span>${s.servingsRemaining} / ${s.totalServings} ${esc(unitLabel)}</span>
    <span>${daysText} left</span>
  </div>

  ${s.notes ? `<div class="suppl-notes">${esc(s.notes)}</div>` : ''}

  <div class="suppl-actions">
    <button class="btn btn-sm btn-ghost" onclick="Suppl.logDose(${s.id})">Log dose (×${s.servingsPerDay})</button>
    <button class="btn btn-sm btn-ghost" onclick="Suppl.openRestock(${s.id})">Restock</button>
    <button class="btn btn-sm btn-ghost" onclick="Suppl.openEditModal(${s.id})">Edit</button>
    <button class="btn btn-sm btn-danger" onclick="Suppl.del(${s.id})">Delete</button>
  </div>
</div>`;
    }

    function updateBanner() {
        const banner = document.getElementById('lowStockBanner');
        if (!banner) return;
        const low = items.filter(s => s.stockStatus === 'low' || s.stockStatus === 'empty');
        if (low.length === 0) { banner.style.display = 'none'; return; }
        const names = low.map(s => `<strong>${esc(s.name)}</strong>`).join(', ');
        banner.innerHTML = `⚠ Running low: ${names}`;
        banner.style.display = 'block';
    }

    // ── Timing toggle helpers ─────────────────────────────────────────────────

    function getSchedule() {
        return [...document.querySelectorAll('.timing-check:checked')].map(i => i.value);
    }

    function setSchedule(arr) {
        document.querySelectorAll('.timing-check').forEach(i => {
            i.checked = arr.includes(i.value);
        });
    }

    // ── Add / Edit modal ──────────────────────────────────────────────────────

    function openAddModal() {
        resetForm();
        document.getElementById('modalTitle').textContent = 'Add Supplement';
        document.getElementById('supplModal').style.display = 'flex';
        document.getElementById('fName').focus();
    }

    function openEditModal(id) {
        const s = items.find(x => x.id === id);
        if (!s) return;
        resetForm();
        document.getElementById('modalTitle').textContent = 'Edit Supplement';
        document.getElementById('supplId').value             = s.id;
        document.getElementById('fName').value               = s.name;
        document.getElementById('fDosage').value             = s.dosage || '';
        document.getElementById('fUnit').value               = s.unit || '';
        document.getElementById('fServingsPerDay').value     = s.servingsPerDay;
        document.getElementById('fServingsRemaining').value  = s.servingsRemaining;
        document.getElementById('fTotalServings').value      = s.totalServings;
        document.getElementById('fWarningDays').value        = s.warningDays;
        document.getElementById('fNotes').value              = s.notes || '';
        setSchedule(s.schedule || []);
        document.getElementById('supplModal').style.display = 'flex';
        document.getElementById('fName').focus();
    }

    function closeModal() {
        document.getElementById('supplModal').style.display = 'none';
    }

    function resetForm() {
        document.getElementById('supplId').value            = '';
        document.getElementById('fName').value              = '';
        document.getElementById('fDosage').value            = '';
        document.getElementById('fUnit').value              = '';
        document.getElementById('fServingsPerDay').value    = '1';
        document.getElementById('fServingsRemaining').value = '0';
        document.getElementById('fTotalServings').value     = '0';
        document.getElementById('fWarningDays').value       = '7';
        document.getElementById('fNotes').value             = '';
        document.querySelectorAll('.timing-check').forEach(i => i.checked = false);
    }

    async function saveForm(e) {
        e.preventDefault();
        const id = document.getElementById('supplId').value;

        const payload = {
            name:              document.getElementById('fName').value.trim(),
            dosage:            document.getElementById('fDosage').value.trim() || null,
            unit:              document.getElementById('fUnit').value.trim() || null,
            servingsPerDay:    parseInt(document.getElementById('fServingsPerDay').value, 10),
            servingsRemaining: parseFloat(document.getElementById('fServingsRemaining').value),
            totalServings:     parseFloat(document.getElementById('fTotalServings').value),
            warningDays:       parseInt(document.getElementById('fWarningDays').value, 10),
            notes:             document.getElementById('fNotes').value.trim() || null,
            schedule:          getSchedule(),
        };

        try {
            let res;
            if (id) {
                res = await apiPatch(`/supplements/${id}`, payload);
                const idx = items.findIndex(x => x.id === parseInt(id, 10));
                if (idx !== -1) items[idx] = res;
            } else {
                res = await apiPost('/supplements', payload);
                items.push(res);
            }
            closeModal();
            render();
            showToast(id ? 'Saved.' : 'Added.');
        } catch (err) {
            showToast(err.message || 'Error saving', true);
        }
    }

    // ── Log dose ──────────────────────────────────────────────────────────────

    async function logDose(id) {
        try {
            const res = await apiPost(`/supplements/${id}/dose`, {});
            const idx = items.findIndex(x => x.id === id);
            if (idx !== -1) items[idx] = res;
            render();
            showToast('Dose logged.');
        } catch (err) {
            showToast(err.message || 'Error', true);
        }
    }

    // ── Restock modal ─────────────────────────────────────────────────────────

    function openRestock(id) {
        const s = items.find(x => x.id === id);
        if (!s) return;
        restockId = id;
        document.getElementById('restockName').textContent    = s.name;
        document.getElementById('restockServings').value      = s.totalServings > 0 ? s.totalServings : 60;
        document.getElementById('restockModal').style.display = 'flex';
        document.getElementById('restockServings').focus();
    }

    function closeRestock() {
        restockId = null;
        document.getElementById('restockModal').style.display = 'none';
    }

    async function confirmRestock() {
        if (restockId === null) return;
        const servings = parseFloat(document.getElementById('restockServings').value);
        if (!servings || servings <= 0) { showToast('Enter a valid number', true); return; }

        try {
            const res = await apiPost(`/supplements/${restockId}/restock`, { servings });
            const idx = items.findIndex(x => x.id === restockId);
            if (idx !== -1) items[idx] = res;
            closeRestock();
            render();
            showToast('Restocked!');
        } catch (err) {
            showToast(err.message || 'Error', true);
        }
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    async function del(id) {
        const s = items.find(x => x.id === id);
        if (!s) return;
        if (!confirm(`Delete "${s.name}"?`)) return;

        try {
            await apiDelete(`/supplements/${id}`);
            items = items.filter(x => x.id !== id);
            render();
            showToast('Deleted.');
        } catch (err) {
            showToast(err.message || 'Error', true);
        }
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────────

    async function apiPost(url, body) {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(body),
        });
        const json = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(json.error || `HTTP ${r.status}`);
        return json;
    }

    async function apiPatch(url, body) {
        const r = await fetch(url, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(body),
        });
        const json = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(json.error || `HTTP ${r.status}`);
        return json;
    }

    async function apiDelete(url) {
        const r = await fetch(url, {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!r.ok) {
            const json = await r.json().catch(() => ({}));
            throw new Error(json.error || `HTTP ${r.status}`);
        }
    }

    // ── Misc utils ────────────────────────────────────────────────────────────

    function esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showToast(msg, isError = false) {
        const t = document.getElementById('toast');
        if (!t) return;
        t.textContent = msg;
        t.classList.toggle('error', isError);
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 2800);
    }

    // ── Init ──────────────────────────────────────────────────────────────────

    document.getElementById('supplModal')?.addEventListener('click', e => {
        if (e.target === e.currentTarget) closeModal();
    });
    document.getElementById('restockModal')?.addEventListener('click', e => {
        if (e.target === e.currentTarget) closeRestock();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeModal(); closeRestock(); }
    });

    document.addEventListener('DOMContentLoaded', () => render());

    return { openAddModal, openEditModal, closeModal, saveForm, logDose, openRestock, closeRestock, confirmRestock, del };
})();
