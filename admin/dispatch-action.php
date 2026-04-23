<?php
/**
 * DEPRECATED — this file is no longer used.
 *
 * The active dispatch AJAX handler is /admin/api/dispatch.php
 * All requests are forwarded there so nothing silently breaks
 * if a stale bookmark or external script still points here.
 */
header('Location: /admin/api/dispatch.php?' . http_build_query($_GET));
exit;
