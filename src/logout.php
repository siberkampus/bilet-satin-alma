<?php
session_start();

$_SESSION = [];

if (ini_get("session.use_cookies")) {
	$params = session_get_cookie_params();
	setcookie(
		session_name(),
		'',
		time() - 42000,
		$params['path'],
		$params['domain'],
		$params['secure'],
		$params['httponly']
	);
}

session_unset();
session_destroy();

setcookie('username', '', time() - 3600, '/');
setcookie('login', '', time() - 3600, '/');
setcookie('role', '', time() - 3600, '/');

header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Expires: 0');

header('Location: index.php');
exit;
?>