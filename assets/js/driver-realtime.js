/**
 * Travelr Taxi - Driver Real-Time Module
 * Handles: GPS location sharing, dashboard polling, ride detail sync
 * 
 * Shared-hosting friendly: uses AJAX polling (no WebSockets).
 * Mobile-first: uses navigator.geolocation API.
 */

const DriverRealtime = (function() {
    'use strict';

    // ── Configuration ──
    const CONFIG = {
        pollInterval: 15000,        // Dashboard poll every 15s
        rideDetailPoll: 10000,      // Ride detail poll every 10s
        locationInterval: 20000,    // GPS update every 20s
        locationHighAccuracy: true,
        locationTimeout: 15000,
        locationMaxAge: 10000,
        apiBase: '/driver/api/realtime.php'
    };

    // ── State ──
    let state = {
        pollTimer: null,
        locationTimer: null,
        watchId: null,
        lastPollTime: null,
        lastLocation: null,
        locationSharing: false,
        activeBookingId: null,
        currentPage: null
    };

    // ── Utility: AJAX GET ──
    function ajaxGet(url, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
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
        xhr.send();
    }

    // ── Utility: AJAX POST ──
    function ajaxPost(url, data, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
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
        const encoded = Object.keys(data).map(k => 
            encodeURIComponent(k) + '=' + encodeURIComponent(data[k])
        ).join('&');
        xhr.send(encoded);
    }

    // ── Utility: Show toast notification ──
    function showToast(message, type) {
        type = type || 'info';
        const existing = document.getElementById('rt-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'rt-toast';
        toast.className = 'rt-toast rt-toast-' + type;
        toast.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle') + '"></i> ' + message;
        document.body.appendChild(toast);

        setTimeout(function() { toast.classList.add('rt-toast-show'); }, 50);
        setTimeout(function() {
            toast.classList.remove('rt-toast-show');
            setTimeout(function() { toast.remove(); }, 400);
        }, 4000);
    }

    // ── GPS: Start location sharing ──
    function startLocationSharing(bookingId) {
        if (!navigator.geolocation) {
            showToast('GPS not supported on this device', 'warning');
            return false;
        }

        state.locationSharing = true;
        state.activeBookingId = bookingId || null;

        // Notify server
        ajaxPost(CONFIG.apiBase, {
            action: 'toggle_location',
            enabled: 1
        }, function(err, res) {
            if (!err && res && res.success) {
                showToast('Location sharing enabled', 'success');
            }
        });

        // Get immediate position
        sendCurrentLocation();

        // Set up periodic updates
        state.locationTimer = setInterval(sendCurrentLocation, CONFIG.locationInterval);

        // Update UI
        updateLocationUI(true);

        return true;
    }

    // ── GPS: Stop location sharing ──
    function stopLocationSharing() {
        state.locationSharing = false;

        if (state.locationTimer) {
            clearInterval(state.locationTimer);
            state.locationTimer = null;
        }
        if (state.watchId !== null) {
            navigator.geolocation.clearWatch(state.watchId);
            state.watchId = null;
        }

        // Notify server
        ajaxPost(CONFIG.apiBase, {
            action: 'toggle_location',
            enabled: 0
        }, function(err, res) {
            if (!err && res && res.success) {
                showToast('Location sharing disabled', 'info');
            }
        });

        updateLocationUI(false);
    }

    // ── GPS: Send current position to server ──
    function sendCurrentLocation() {
        if (!state.locationSharing) return;

        navigator.geolocation.getCurrentPosition(
            function(pos) {
                const data = {
                    action: 'update_location',
                    latitude: pos.coords.latitude,
                    longitude: pos.coords.longitude,
                    accuracy: pos.coords.accuracy || 0,
                    speed: pos.coords.speed || 0,
                    heading: pos.coords.heading || 0
                };
                if (state.activeBookingId) {
                    data.booking_id = state.activeBookingId;
                }

                state.lastLocation = {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    time: new Date()
                };

                ajaxPost(CONFIG.apiBase, data, function(err, res) {
                    if (err) {
                        console.warn('Location update failed:', err);
                    }
                    updateLocationStatusIndicator();
                });
            },
            function(err) {
                console.warn('Geolocation error:', err.message);
                if (err.code === 1) {
                    showToast('Location permission denied. Please enable in browser settings.', 'warning');
                    stopLocationSharing();
                }
            },
            {
                enableHighAccuracy: CONFIG.locationHighAccuracy,
                timeout: CONFIG.locationTimeout,
                maximumAge: CONFIG.locationMaxAge
            }
        );
    }

    // ── GPS: Update UI indicators ──
    function updateLocationUI(isSharing) {
        const toggle = document.getElementById('locationToggle');
        const statusDot = document.getElementById('locationStatusDot');
        const statusText = document.getElementById('locationStatusText');

        if (toggle) {
            toggle.checked = isSharing;
        }
        if (statusDot) {
            statusDot.className = 'loc-status-dot ' + (isSharing ? 'loc-live' : 'loc-off');
        }
        if (statusText) {
            statusText.textContent = isSharing ? 'Sharing Location' : 'Location Off';
        }
    }

    function updateLocationStatusIndicator() {
        const indicator = document.getElementById('locationLastUpdate');
        if (indicator && state.lastLocation) {
            const time = state.lastLocation.time;
            indicator.textContent = 'Updated ' + time.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
        }
    }

    // ── Polling: Dashboard ──
    function startDashboardPolling() {
        state.currentPage = 'dashboard';
        pollDashboard(); // Immediate first poll
        state.pollTimer = setInterval(pollDashboard, CONFIG.pollInterval);
    }

    function pollDashboard() {
        const since = state.lastPollTime || '';
        const url = CONFIG.apiBase + '?action=poll_dashboard&since=' + encodeURIComponent(since);

        ajaxGet(url, function(err, res) {
            if (err || !res || !res.success) return;

            state.lastPollTime = res.server_time;

            if (res.has_changes) {
                // Update pending badge
                const pendingBadge = document.getElementById('pendingCountBadge');
                if (pendingBadge) {
                    pendingBadge.textContent = res.pending_count;
                    pendingBadge.style.display = res.pending_count > 0 ? 'inline-flex' : 'none';
                    if (res.pending_count > 0) {
                        pendingBadge.classList.add('rt-pulse');
                        setTimeout(function() { pendingBadge.classList.remove('rt-pulse'); }, 2000);
                    }
                }

                // Update today count
                const todayEl = document.getElementById('todayCountValue');
                if (todayEl) todayEl.textContent = res.today_count;

                // Update active ride card if present
                if (res.active_ride) {
                    updateActiveRideCard(res.active_ride);
                }

                // Flash the page title for new assignments
                if (res.pending_count > 0) {
                    flashTitle('New Ride Assignment!');
                }
            }
        });
    }

    function updateActiveRideCard(ride) {
        const card = document.getElementById('activeRideCard');
        if (!card) return;

        const statusEl = card.querySelector('.active-ride-status');
        if (statusEl) {
            statusEl.textContent = ride.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            statusEl.className = 'active-ride-status status-' + ride.status;
        }
    }

    // ── Polling: Ride Detail ──
    function startRideDetailPolling(bookingId, currentStatus) {
        state.currentPage = 'ride_detail';
        state.activeBookingId = bookingId;

        function pollRide() {
            const url = CONFIG.apiBase + '?action=poll_ride&booking_id=' + bookingId + '&last_status=' + encodeURIComponent(currentStatus);
            ajaxGet(url, function(err, res) {
                if (err || !res || !res.success) return;

                if (res.reassigned) {
                    showToast('This ride has been reassigned.', 'warning');
                    setTimeout(function() { window.location.href = '/driver/'; }, 2000);
                    return;
                }

                if (res.changed) {
                    showToast('Ride status updated by dispatch: ' + res.status.replace(/_/g, ' '), 'info');
                    // Reload to reflect new status and available actions
                    setTimeout(function() { location.reload(); }, 1500);
                }

                // Update dispatcher notes if changed
                if (res.dispatcher_notes) {
                    const notesEl = document.getElementById('dispatcherNotesValue');
                    if (notesEl) notesEl.textContent = res.dispatcher_notes;
                }
            });
        }

        pollRide(); // Immediate
        state.pollTimer = setInterval(pollRide, CONFIG.rideDetailPoll);
    }

    // ── Utility: Flash browser tab title ──
    function flashTitle(message) {
        const original = document.title;
        let flashing = true;
        const interval = setInterval(function() {
            document.title = flashing ? message : original;
            flashing = !flashing;
        }, 1000);
        setTimeout(function() {
            clearInterval(interval);
            document.title = original;
        }, 10000);
    }

    // ── Cleanup ──
    function destroy() {
        if (state.pollTimer) {
            clearInterval(state.pollTimer);
            state.pollTimer = null;
        }
        if (state.locationTimer) {
            clearInterval(state.locationTimer);
            state.locationTimer = null;
        }
        if (state.watchId !== null) {
            navigator.geolocation.clearWatch(state.watchId);
            state.watchId = null;
        }
    }

    // ── Public API ──
    return {
        startLocationSharing: startLocationSharing,
        stopLocationSharing: stopLocationSharing,
        startDashboardPolling: startDashboardPolling,
        startRideDetailPolling: startRideDetailPolling,
        sendCurrentLocation: sendCurrentLocation,
        showToast: showToast,
        destroy: destroy,
        getState: function() { return state; },
        CONFIG: CONFIG
    };

})();

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    DriverRealtime.destroy();
});
