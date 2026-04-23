/**
 * Travelr Taxi - Dispatch Real-Time Module
 * Replaces the crude 60-second full-page reload with efficient AJAX polling.
 * Handles: stats refresh, booking updates, driver panel refresh, location indicators.
 * 
 * Shared-hosting friendly: uses AJAX polling (no WebSockets).
 */

const DispatchRealtime = (function() {
    'use strict';

    // ── Configuration ──
    const CONFIG = {
        statsInterval: 15000,       // Stats poll every 15s
        bookingsInterval: 12000,    // Bookings poll every 12s
        driversInterval: 20000,     // Drivers poll every 20s
        apiBase: '/admin/api/realtime.php'
    };

    // ── State ──
    let state = {
        statsTimer: null,
        bookingsTimer: null,
        driversTimer: null,
        lastBookingPoll: null,
        currentFilter: 'today',
        isPolling: false,
        pausePolling: false   // Pause when modal is open
    };

    // ── Utility: AJAX GET ──
    function ajaxGet(url, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.timeout = 10000;
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        callback(null, JSON.parse(xhr.responseText));
                    } catch (e) {
                        callback(e, null);
                    }
                } else {
                    callback(new Error('HTTP ' + xhr.status), null);
                }
            }
        };
        xhr.ontimeout = function() { callback(new Error('Timeout'), null); };
        xhr.send();
    }

    // ── Stats Polling ──
    function pollStats() {
        if (state.pausePolling) return;

        ajaxGet(CONFIG.apiBase + '?action=poll_stats', function(err, res) {
            if (err || !res || !res.success) return;
            const s = res.stats;

            // Update stat numbers with animation
            animateStatUpdate('.dstat-active .dstat-number', s.active_trips);
            animateStatUpdate('.dstat-unassigned .dstat-number', s.unassigned);
            animateStatUpdate('.dstat-drivers .dstat-number', s.available_drivers);
            animateStatUpdate('.dstat-revenue .dstat-number', '$' + s.revenue_today);
            animateStatUpdate('.dstat-today .dstat-number', s.today_bookings);
            animateStatUpdate('.dstat-completed .dstat-number', s.completed_today);

            // Flash unassigned if > 0
            const unassignedEl = document.querySelector('.dstat-unassigned');
            if (unassignedEl) {
                if (parseInt(s.unassigned) > 0) {
                    unassignedEl.classList.add('dstat-alert');
                } else {
                    unassignedEl.classList.remove('dstat-alert');
                }
            }
        });
    }

    function animateStatUpdate(selector, newValue) {
        const el = document.querySelector(selector);
        if (!el) return;
        const current = el.textContent.trim();
        const newStr = String(newValue);
        if (current !== newStr) {
            el.textContent = newStr;
            el.classList.add('dstat-flash');
            setTimeout(function() { el.classList.remove('dstat-flash'); }, 1500);
        }
    }

    // ── Bookings Polling ──
    function pollBookings() {
        if (state.pausePolling) return;

        const since = state.lastBookingPoll || '';
        const url = CONFIG.apiBase + '?action=poll_bookings&filter=' + encodeURIComponent(state.currentFilter) + '&since=' + encodeURIComponent(since);

        ajaxGet(url, function(err, res) {
            if (err || !res || !res.success) return;

            state.lastBookingPoll = res.server_time;

            if (res.count > 0) {
                // Update existing cards or signal reload needed
                let needsReload = false;
                res.bookings.forEach(function(b) {
                    const card = document.querySelector('.dispatch-card[data-booking-id="' + b.id + '"]');
                    if (card) {
                        // Update status badge
                        const badge = card.querySelector('.status-badge');
                        if (badge) {
                            const newLabel = b.status.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
                            if (badge.textContent.trim() !== newLabel) {
                                badge.textContent = newLabel;
                                card.classList.add('dcard-updated');
                                setTimeout(function() { card.classList.remove('dcard-updated'); }, 3000);
                            }
                        }

                        // Update driver location indicator
                        updateCardLocationIndicator(card, b);

                        // Update dispatcher notes
                        if (b.dispatcher_notes) {
                            let noteEl = card.querySelector('.dcard-note-preview');
                            if (noteEl) {
                                noteEl.innerHTML = '<i class="fas fa-sticky-note"></i> ' + escapeHtml(b.dispatcher_notes.substring(0, 60));
                            }
                        }
                    } else {
                        // New booking not on page — need reload
                        needsReload = true;
                    }
                });

                if (needsReload) {
                    showSyncBanner('New bookings detected. Click to refresh.', function() {
                        location.reload();
                    });
                }
            }
        });
    }

    // ── Update location indicator on a booking card ──
    function updateCardLocationIndicator(card, booking) {
        let locEl = card.querySelector('.dcard-location');

        if (booking.driver_location_live || booking.driver_location_stale) {
            if (!locEl) {
                locEl = document.createElement('div');
                locEl.className = 'dcard-location';
                const driverEl = card.querySelector('.dcard-driver');
                if (driverEl) {
                    driverEl.after(locEl);
                }
            }
            if (locEl) {
                const isLive = booking.driver_location_live;
                locEl.className = 'dcard-location ' + (isLive ? 'dcard-loc-live' : 'dcard-loc-stale');
                locEl.innerHTML = '<span class="dcard-loc-dot"></span> ' +
                    (isLive ? 'Live' : 'Last seen ' + (booking.last_location_at_formatted || '')) +
                    ' <span class="dcard-loc-coords" title="' + booking.last_latitude + ', ' + booking.last_longitude + '"><i class="fas fa-map-pin"></i></span>';
            }
        }
    }

    // ── Drivers Panel Polling ──
    function pollDrivers() {
        if (state.pausePolling) return;

        ajaxGet(CONFIG.apiBase + '?action=poll_drivers', function(err, res) {
            if (err || !res || !res.success) return;

            const panel = document.querySelector('.dpanel-body');
            if (!panel) return;

            // Rebuild driver rows
            let html = '';
            res.drivers.forEach(function(drv) {
                const locBadge = getDriverLocationBadge(drv);
                html += '<div class="driver-row driver-' + drv.availability + '">'
                    + '<div class="driver-row-left">'
                    + '<div class="driver-avail-dot avail-' + drv.availability + '"></div>'
                    + '<div class="driver-row-info">'
                    + '<strong>' + escapeHtml(drv.first_name + ' ' + drv.last_name) + '</strong>'
                    + '<span class="driver-row-meta">'
                    + (drv.vehicle_name
                        ? escapeHtml(drv.vehicle_name) + ' &middot; ' + capitalize(drv.vehicle_type || '') + ' &middot; ' + (drv.capacity || '?') + ' seats'
                        : 'No vehicle')
                    + '</span>'
                    + '<span class="driver-row-stats">'
                    + '<i class="fas fa-star" style="color:#f59e0b"></i> ' + parseFloat(drv.rating || 0).toFixed(1)
                    + ' &middot; ' + (drv.total_trips || 0) + ' trips'
                    + '</span>'
                    + locBadge
                    + '</div>'
                    + '</div>'
                    + '<div class="driver-row-right">'
                    + '<span class="driver-status-tag tag-' + drv.availability + '">' + capitalize(drv.availability.replace(/_/g, ' ')) + '</span>'
                    + '<a href="tel:' + escapeHtml(drv.phone || '') + '" class="dcard-btn-mini" title="Call"><i class="fas fa-phone"></i></a>'
                    + '</div>'
                    + '</div>';
            });

            panel.innerHTML = html;

            // Update count
            const countEl = document.querySelector('.dpanel-count');
            if (countEl) countEl.textContent = res.drivers.length + ' total';
        });
    }

    function getDriverLocationBadge(drv) {
        if (!drv.location_sharing || !drv.last_latitude) return '';
        if (drv.location_live) {
            return '<span class="driver-loc-badge driver-loc-live"><span class="dloc-dot"></span> Live &middot; ' + (drv.last_location_at_formatted || '') + '</span>';
        }
        if (drv.location_stale) {
            return '<span class="driver-loc-badge driver-loc-stale"><span class="dloc-dot"></span> Last: ' + (drv.last_location_at_formatted || '') + '</span>';
        }
        return '';
    }

    // ── Sync Banner (for new bookings) ──
    function showSyncBanner(message, onClick) {
        if (document.getElementById('syncBanner')) return;
        const banner = document.createElement('div');
        banner.id = 'syncBanner';
        banner.className = 'dispatch-sync-banner';
        banner.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> ' + message;
        banner.addEventListener('click', onClick);
        const header = document.querySelector('.dispatch-header');
        if (header) header.after(banner);
    }

    // ── Utility ──
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function capitalize(str) {
        return str.replace(/\b\w/g, function(c) { return c.toUpperCase(); });
    }

    // ── Init ──
    function init(filter) {
        state.currentFilter = filter || 'today';
        state.isPolling = true;

        // Start all pollers
        pollStats();
        state.statsTimer = setInterval(pollStats, CONFIG.statsInterval);

        pollBookings();
        state.bookingsTimer = setInterval(pollBookings, CONFIG.bookingsInterval);

        pollDrivers();
        state.driversTimer = setInterval(pollDrivers, CONFIG.driversInterval);

        // Pause polling when modals are open
        document.addEventListener('click', function(e) {
            if (e.target.closest('.dmodal-overlay') || e.target.closest('.dmodal-close')) {
                state.pausePolling = false;
            }
        });

        // Update sync indicator
        updateSyncIndicator();
        setInterval(updateSyncIndicator, 5000);
    }

    function updateSyncIndicator() {
        const el = document.getElementById('syncIndicator');
        if (el) {
            el.innerHTML = '<span class="sync-dot sync-dot-live"></span> Live';
            el.title = 'Last sync: ' + (state.lastBookingPoll || 'connecting...');
        }
    }

    // ── Pause/Resume (for modals) ──
    function pause() { state.pausePolling = true; }
    function resume() { state.pausePolling = false; }

    // ── Cleanup ──
    function destroy() {
        if (state.statsTimer) clearInterval(state.statsTimer);
        if (state.bookingsTimer) clearInterval(state.bookingsTimer);
        if (state.driversTimer) clearInterval(state.driversTimer);
        state.isPolling = false;
    }

    // ── Public API ──
    return {
        init: init,
        pause: pause,
        resume: resume,
        destroy: destroy,
        pollStats: pollStats,
        pollBookings: pollBookings,
        pollDrivers: pollDrivers,
        CONFIG: CONFIG
    };

})();

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    DispatchRealtime.destroy();
});
