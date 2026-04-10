<?php
// ============================================================
// config.php — Configuration & fonctions utilitaires
// Gestion de Scolarité (LAACHEMI 2025/2026)
// ============================================================

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------
// Connexion PDO à la base de données
// ------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_scolarite');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }
    return $pdo;
}

// ------------------------------------------------------------
// Fonctions d'authentification & de session
// ------------------------------------------------------------

/**
 * Vérifie si l'utilisateur est connecté
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Redirige vers une URL
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Vérifie que l'utilisateur a le rôle requis, sinon redirige
 */
function requireRole(string ...$roles): void {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    if (!in_array($_SESSION['user_role'], $roles)) {
        // Rediriger vers le bon dashboard selon le rôle
        redirectToDashboard();
        exit;
    }
}

/**
 * Redirige l'utilisateur vers son tableau de bord selon son rôle
 */
function redirectToDashboard(): void {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    switch ($_SESSION['user_role']) {
        case 'Admin':
            redirect('dashboard_admin.php');
            break;
        case 'Enseignant':
            redirect('interface_enseignant.php');
            break;
        case 'Etudiant':
            redirect('interface_etudiant.php');
            break;
        default:
            redirect('login.php');
    }
}

// ------------------------------------------------------------
// Fonctions utilitaires
// ------------------------------------------------------------

/**
 * Échappe le HTML pour prévenir les XSS
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Génère un token CSRF et le stocke en session
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF
 */
function verifyCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Affiche un champ caché avec le token CSRF
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(generateCSRFToken()) . '">';
}

/**
 * Message flash (stocke/récupère un message de notification)
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
