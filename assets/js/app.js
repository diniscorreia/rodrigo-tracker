/**
 * O Frasco do Rodrigo — Frontend Logic
 */
(function () {
    'use strict';

    // =========================================================================
    // State
    // =========================================================================
    const state = {
        authenticated: false,
        pin: null,
        pinCallback: null,
        status: null,
        historyPage: 1,
        historyTotal: 1,
    };

    const API = 'api.php';

    // =========================================================================
    // API Helpers
    // =========================================================================
    async function apiGet(action, params = {}) {
        const qs = new URLSearchParams({ action, ...params });
        const res = await fetch(`${API}?${qs}`);
        return res.json();
    }

    async function apiPost(action, body = {}) {
        if (!body.pin) body.pin = state.pin;
        const res = await fetch(`${API}?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        return res.json();
    }

    // =========================================================================
    // DOM refs
    // =========================================================================
    const $ = (sel) => document.querySelector(sel);
    const $$ = (sel) => document.querySelectorAll(sel);

    // =========================================================================
    // Gear / Admin flow
    // =========================================================================
    function handleGearClick() {
        if (state.authenticated) {
            openAdminModal();
        } else {
            openPinModal();
        }
    }

    function openAdminModal() {
        const overlay = $('#modal-overlay');
        const modal = $('#modal-admin');
        overlay.classList.add('active');
        modal.hidden = false;
    }

    function openPinModal(onSuccess) {
        state.pinCallback = onSuccess || null;
        const overlay = $('#modal-overlay');
        const modal = $('#modal-pin');
        $('#pin-input').value = '';
        $('#pin-error').hidden = true;

        overlay.classList.add('active');
        modal.hidden = false;
        $('#pin-input').focus();
    }

    async function handlePinSubmit() {
        const pin = $('#pin-input').value.trim();
        if (!pin) return;

        const errEl = $('#pin-error');
        errEl.hidden = true;

        const res = await apiPost('verify_pin', { pin });
        if (!res.ok) {
            errEl.textContent = res.error;
            errEl.hidden = false;
            $('#pin-input').value = '';
            $('#pin-input').focus();
            return;
        }

        state.pin = pin;
        state.authenticated = true;
        closeModals();

        $('#gear-btn').classList.add('authenticated');

        // Execute callback if one was set (e.g. from FAB)
        if (state.pinCallback) {
            const cb = state.pinCallback;
            state.pinCallback = null;
            cb();
        } else {
            openAdminModal();
        }
    }

    // =========================================================================
    // Rules modal
    // =========================================================================
    function openRulesModal() {
        const overlay = $('#modal-overlay');
        const modal = $('#modal-rules');
        overlay.classList.add('active');
        modal.hidden = false;
    }

    // =========================================================================
    // History modal
    // =========================================================================
    function openHistoryModal() {
        const overlay = $('#modal-overlay');
        const modal = $('#modal-history');
        overlay.classList.add('active');
        modal.hidden = false;
        loadHistory(1);
    }

    // =========================================================================
    // Dashboard — Load & Render
    // =========================================================================
    async function loadStatus() {
        const res = await apiGet('status');
        if (!res.ok) return;
        state.status = res.data;

        renderJar(res.data.balance);
        renderCurrentWeek(res.data.current_week, res.data.today);
        renderStreak(res.data.streak);
        renderProjection(res.data.projection);
        renderFab(res.data.current_week, res.data.today);
    }

    // =========================================================================
    // Jar
    // =========================================================================
    function renderJar(balance) {
        const amount = $('#jar-amount');
        amount.textContent = formatEuro(balance);
        amount.className = 'jar-amount';
        if (balance > 0) {
            amount.classList.add('positive');
        } else if (balance < 0) {
            amount.classList.add('negative');
        } else {
            amount.classList.add('zero');
        }
    }

    // =========================================================================
    // Current Week
    // =========================================================================
    function renderCurrentWeek(week, today) {
        const datesEl = $('#week-dates');
        datesEl.textContent = formatDateRange(week.start, week.end);

        // Update week progress ring
        const ringFill = $('#week-ring-fill');
        const ringLabel = $('#week-ring-label');
        const pct = (week.days_logged / 7) * 100;
        ringFill.style.setProperty('--week-ring-pct', pct);
        ringFill.setAttribute('class', 'week-ring-progress' + (week.days_logged >= 4 ? ' good' : ''));
        ringLabel.innerHTML = `${week.days_logged} vez${week.days_logged !== 1 ? 'es' : ''}<span class="week-ring-sublabel">esta semana</span>`;

        const loggedDates = {};
        week.days.forEach((d) => {
            loggedDates[d.log_date] = d.logged_by || true;
        });

        const todayDate = new Date(today + 'T00:00:00');
        const todayDow = todayDate.getDay() === 0 ? 7 : todayDate.getDay();

        const dots = $$('.dot');
        dots.forEach((dot) => {
            const dayNum = parseInt(dot.dataset.day);
            const dateStr = getDateForDayOfWeek(week.start, dayNum);

            dot.className = 'dot';

            if (loggedDates[dateStr]) {
                dot.classList.add('filled');
            } else if (dayNum < todayDow) {
                dot.classList.add('missed');
            } else if (dayNum > todayDow) {
                dot.classList.add('future');
            }

            if (dayNum === todayDow) {
                dot.classList.add('today');
            }

            // Click actions only when authenticated
            dot.onclick = null;
            if (state.authenticated) {
                if (loggedDates[dateStr]) {
                    dot.onclick = () => confirmDelete(dateStr);
                } else if (dayNum <= todayDow) {
                    dot.onclick = () => handleLogDay(dateStr);
                }
            }
        });
    }

    function renderFab(week, today) {
        const fab = $('#fab-log');
        if (!fab) return;

        const todayLogged = week.days.some((d) => d.log_date === today);

        if (todayLogged) {
            fab.classList.add('logged');
            fab.title = 'Já registado hoje';
            fab.disabled = true;
        } else {
            fab.classList.remove('logged');
            fab.title = 'Registar hoje';
            fab.disabled = false;
        }
    }

    function handleFabClick() {
        const fab = $('#fab-log');
        if (fab.disabled) return;

        if (state.authenticated) {
            logToday();
        } else {
            openPinModal(logToday);
        }
    }

    function logToday() {
        const today = state.status?.today || formatISODate(new Date());
        handleLogDay(today);
    }

    // =========================================================================
    // Streak
    // =========================================================================
    function renderStreak(streak) {
        $('#streak-count').textContent = streak;
    }

    // =========================================================================
    // Projection
    // =========================================================================
    function renderProjection(projection) {
        const section = $('#projection-section');
        const msgEl = $('#projection-message');

        if (!projection.visible) {
            section.hidden = true;
            return;
        }

        section.hidden = false;
        msgEl.textContent = projection.message;
        msgEl.className = 'projection-text';

        if (projection.avg_days_per_week >= 5) {
            msgEl.classList.add('positive');
        } else if (projection.avg_days_per_week < 4) {
            msgEl.classList.add('negative');
        }
    }

    // =========================================================================
    // Actions
    // =========================================================================
    async function handleLogDay(date) {
        const res = await apiPost('log_day', { date });
        if (!res.ok) {
            alert(res.error);
            return;
        }
        loadStatus();
    }

    function confirmDelete(date) {
        const overlay = $('#modal-overlay');
        const modal = $('#modal-delete');
        $('#delete-date-label').textContent = formatDate(date);

        overlay.classList.add('active');
        modal.hidden = false;

        $('#delete-confirm').onclick = async () => {
            const res = await apiPost('delete_day', { date });
            if (!res.ok) {
                alert(res.error);
            }
            closeModals();
            loadStatus();
        };
    }

    function openLogPastModal() {
        // Hide admin modal, keep overlay
        $('#modal-admin').hidden = true;
        const overlay = $('#modal-overlay');
        const modal = $('#modal-log-past');
        const input = $('#past-date-input');
        const errEl = $('#past-date-error');

        const today = new Date();
        const minDate = new Date();
        minDate.setDate(minDate.getDate() - 7);
        input.max = formatISODate(today);
        input.min = formatISODate(minDate);
        input.value = '';
        errEl.hidden = true;

        overlay.classList.add('active');
        modal.hidden = false;
    }

    async function submitLogPast() {
        const date = $('#past-date-input').value;
        const errEl = $('#past-date-error');
        errEl.hidden = true;

        if (!date) {
            errEl.textContent = 'Seleciona uma data.';
            errEl.hidden = false;
            return;
        }

        const res = await apiPost('log_day', { date });
        if (!res.ok) {
            errEl.textContent = res.error;
            errEl.hidden = false;
            return;
        }

        closeModals();
        loadStatus();
        loadHistory(1);
    }

    function openWithdrawModal() {
        // Hide admin modal, keep overlay
        $('#modal-admin').hidden = true;
        const overlay = $('#modal-overlay');
        const modal = $('#modal-withdraw');
        $('#withdraw-amount').value = '';
        $('#withdraw-note').value = '';
        $('#withdraw-error').hidden = true;

        overlay.classList.add('active');
        modal.hidden = false;
        $('#withdraw-amount').focus();
    }

    async function submitWithdraw() {
        const amount = parseFloat($('#withdraw-amount').value);
        const note = $('#withdraw-note').value.trim();
        const errEl = $('#withdraw-error');
        errEl.hidden = true;

        if (!amount || amount <= 0) {
            errEl.textContent = 'Valor inválido.';
            errEl.hidden = false;
            return;
        }

        const res = await apiPost('withdraw', { amount, note });
        if (!res.ok) {
            errEl.textContent = res.error;
            errEl.hidden = false;
            return;
        }

        closeModals();
        loadStatus();
        loadHistory(1);
    }

    function closeModals() {
        $('#modal-overlay').classList.remove('active');
        $$('.modal').forEach((m) => (m.hidden = true));
    }

    // =========================================================================
    // History
    // =========================================================================
    async function loadHistory(page) {
        state.historyPage = page;
        const res = await apiGet('history', { page, per_page: 20 });
        if (!res.ok) return;

        state.historyTotal = res.data.total_pages;
        renderHistory(res.data.weeks, res.data.withdrawals, page === 1);

        const loadMoreBtn = $('#load-more-btn');
        loadMoreBtn.hidden = page >= res.data.total_pages;
    }

    function renderHistory(weeks, withdrawals, replace) {
        const container = $('#history-list');
        if (replace) container.innerHTML = '';

        if (replace && withdrawals.length > 0) {
            const wHeader = document.createElement('h3');
            wHeader.textContent = 'Levantamentos';
            wHeader.style.cssText = 'color: var(--accent-gold); margin-bottom: 0.5rem; font-size: 0.9rem;';
            container.appendChild(wHeader);

            withdrawals.forEach((w) => {
                const el = document.createElement('div');
                el.className = 'history-withdrawal';
                const noteHtml = w.note
                    ? `<div class="history-withdrawal-note">${escapeHtml(w.note)}${w.logged_by ? ' — ' + escapeHtml(w.logged_by) : ''}</div>`
                    : (w.logged_by ? `<div class="history-withdrawal-note">${escapeHtml(w.logged_by)}</div>` : '');
                el.innerHTML = `
                    <div class="history-withdrawal-header">
                        <span>${formatDate(w.created_at.split('T')[0])}</span>
                        <span class="history-withdrawal-amount">-${formatEuro(w.amount)}</span>
                    </div>
                    ${noteHtml}
                `;
                container.appendChild(el);
            });
        }

        if (weeks.length === 0 && replace) {
            container.innerHTML += '<p class="loading">Ainda sem histórico de semanas completas.</p>';
            return;
        }

        weeks.forEach((week) => {
            const el = document.createElement('div');
            const cls = week.day_count >= 5 ? 'good' : week.day_count === 4 ? 'neutral' : 'bad';
            el.className = `history-week ${cls}`;

            const totalContrib = week.contribution + (week.bonus || 0);
            const resultCls = totalContrib > 0 ? 'positive' : totalContrib < 0 ? 'negative' : 'zero';
            const sign = totalContrib > 0 ? '+' : '';

            let detailHtml = '';
            if (week.days && week.days.length > 0) {
                detailHtml = week.days
                    .map((d) => {
                        const who = d.logged_by ? `<span class="history-day-who">${escapeHtml(d.logged_by)}</span>` : '';
                        return `<div class="history-day"><span>${formatDate(d.log_date)}</span>${who}</div>`;
                    })
                    .join('');
            } else {
                detailHtml = '<div class="history-day" style="color: var(--accent-red)">Nenhum dia registado</div>';
            }

            if (week.bonus > 0) {
                detailHtml += `<div class="history-day" style="color: var(--accent-gold)"><span>Bónus de streak!</span><span>+${formatEuro(week.bonus)}</span></div>`;
            }

            el.innerHTML = `
                <div class="history-week-header">
                    <span class="history-week-dates">${formatDateRange(week.week_start, week.week_end)}</span>
                    <span class="history-week-result ${resultCls}">${sign}${formatEuro(totalContrib)}</span>
                </div>
                <div class="history-week-days">${week.day_count} dia${week.day_count !== 1 ? 's' : ''}</div>
                <div class="history-week-detail">${detailHtml}</div>
            `;

            el.addEventListener('click', () => el.classList.toggle('expanded'));
            container.appendChild(el);
        });
    }

    // =========================================================================
    // Helpers
    // =========================================================================
    function formatEuro(amount) {
        const abs = Math.abs(amount);
        const formatted = abs.toFixed(2).replace('.', ',');
        if (amount < 0) return `-${formatted}\u00a0\u20ac`;
        return `${formatted}\u00a0\u20ac`;
    }

    function formatDate(dateStr) {
        const [y, m, d] = dateStr.split('-');
        return `${d}/${m}`;
    }

    function formatDateRange(start, end) {
        return `${formatDate(start)} \u2013 ${formatDate(end)}`;
    }

    function formatISODate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function getDateForDayOfWeek(mondayStr, dayNum) {
        const d = new Date(mondayStr + 'T00:00:00');
        d.setDate(d.getDate() + (dayNum - 1));
        return formatISODate(d);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // =========================================================================
    // Event Bindings
    // =========================================================================
    function bindEvents() {
        // Header icons
        $('#gear-btn').addEventListener('click', handleGearClick);
        $('#rules-btn').addEventListener('click', openRulesModal);
        $('#history-btn').addEventListener('click', openHistoryModal);

        // PIN modal
        $('#pin-submit').addEventListener('click', handlePinSubmit);
        $('#pin-input').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') handlePinSubmit();
        });

        // FAB — Log today
        $('#fab-log').addEventListener('click', handleFabClick);

        // Log past day
        $('#log-past-btn').addEventListener('click', openLogPastModal);
        $('#past-date-submit').addEventListener('click', submitLogPast);

        // Withdraw
        $('#withdraw-btn').addEventListener('click', openWithdrawModal);
        $('#withdraw-submit').addEventListener('click', submitWithdraw);

        // Modal cancels
        $$('.modal-cancel').forEach((btn) => {
            btn.addEventListener('click', closeModals);
        });

        // Close modal on overlay click
        $('#modal-overlay').addEventListener('click', (e) => {
            if (e.target === $('#modal-overlay')) closeModals();
        });

        // Load more history
        $('#load-more-btn').addEventListener('click', () => {
            loadHistory(state.historyPage + 1);
        });

        // Escape key closes modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModals();
        });
    }

    // =========================================================================
    // Init — public dashboard, no auth needed to view
    // =========================================================================
    function init() {
        bindEvents();
        loadStatus();
    }

    document.addEventListener('DOMContentLoaded', init);
})();
