<?php
require __DIR__ . '/includes/bootstrap.php';
if (me()) audit('logout', 'user', me()['id'], ['user' => me()['full_name']]);
$_SESSION = [];
if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']); }
session_destroy();
redirect(BASE . '/login.php');
