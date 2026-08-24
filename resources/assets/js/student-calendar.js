'use strict';

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import multiMonthPlugin from '@fullcalendar/multimonth';

document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('studentCalendar');
    if (!el) return;

    const url = el.dataset.eventsUrl;

    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, multiMonthPlugin],
        initialView: 'dayGridMonth',
        firstDay: 1,
        headerToolbar: { start: 'prev,next today', center: 'title', end: 'dayGridMonth,dayGridWeek,multiMonthYear' },
        buttonText: { today: 'Today', month: 'Month', week: 'Week', year: 'Year' },
        views: { multiMonthYear: { buttonText: 'Year' } },
        height: 'auto',
        dayMaxEvents: 3,
        fixedWeekCount: false,
        eventOrder: 'sortPriority,title',
        events: function (info, success, failure) {
            fetch(`${url}?start=${info.startStr}&end=${info.endStr}`, { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then(success)
                .catch(failure);
        },
        eventClick: function (info) {
            const p = info.event.extendedProps;
            const dateStr = new Date(info.event.startStr + 'T00:00:00').toLocaleDateString('en', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
            });
            let body = `<div class="text-start"><strong>${escapeHtml(info.event.title)}</strong><div class="text-muted">${dateStr}</div>`;
            if (p.reason) body += `<div class="text-muted mt-1">${escapeHtml(p.reason)}</div>`;
            if (p.description) body += `<div class="mt-1">${escapeHtml(p.description)}</div>`;
            body += '</div>';
            Swal.fire({ html: body, confirmButtonText: 'Close', customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false });
        },
        eventDidMount: function (info) {
            const p = info.event.extendedProps;
            info.el.title = info.event.title + (p.reason ? ` — ${p.reason}` : '');

            const map = {
                present: ['att-present', 'ti-check', 'Present'],
                late: ['att-late', 'ti-clock', 'Late'],
                absent: ['att-absent', 'ti-x', 'Absent'],
            };
            const m = map[p.attendance];
            if (m) {
                const badge = document.createElement('span');
                badge.className = 'att-badge ' + m[0];
                badge.innerHTML = `<i class="ti ${m[1]}"></i>`;
                (info.el.querySelector('.fc-event-title') || info.el).appendChild(badge);
                info.el.title += ' · ' + m[2];
            }
        },
    });

    calendar.render();
    watchMorePopover(el);

    // Same fix as the admin calendar: give the "+more" popover a real dimmed
    // backdrop so the day cell underneath (with its own class/holiday colours)
    // can never show through the popover's edges.
    function watchMorePopover(root) {
        let backdrop = null;
        const harness = () => root.querySelector('.fc-view-harness') || root;

        const observer = new MutationObserver(() => {
            const popover = root.querySelector('.fc-popover');
            if (popover && !backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'cal-popover-backdrop';
                harness().appendChild(backdrop);
            } else if (!popover && backdrop) {
                backdrop.remove();
                backdrop = null;
            }
        });
        observer.observe(root, { childList: true, subtree: true });
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
});
