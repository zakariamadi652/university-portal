<?php
// logout.php — Déconnexion
require_once 'config.php';

// Détruire toutes les données de session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Si c'est une requête beacon (fermeture de page), ne pas rediriger
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    (empty($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false)) {
    http_response_code(200);
    exit;
}

// Rediriger vers la page de connexion
redirect('login.php');
