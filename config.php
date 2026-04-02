<?php
// ══════════════════════════════════════════════
// CONFIGURAÇÃO DO BANCO DE DADOS — DANIEL QUEIROZ
// ══════════════════════════════════════════════

define('DB_HOST', 'localhost');
define('DB_NAME', 'u794239949_danielqueiroz');
define('DB_USER', 'u794239949_danielqueiroz');
define('DB_PASS', 'DanielDQ@2026!');
define('DB_CHARSET', 'utf8mb4');

// Conexão segura com o banco
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['erro' => 'Erro de conexão com o banco de dados.']));
        }
    }
    return $pdo;
}

// Sessão segura
function iniciarSessao() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 7200, // 2 horas
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

// Verificar se está logado
function verificarLogin() {
    iniciarSessao();
    if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
        header('Location: /admin/index.php');
        exit;
    }
    // Expirar sessão após 2 horas de inatividade
    if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > 7200) {
        session_destroy();
        header('Location: /admin/index.php?expirou=1');
        exit;
    }
    $_SESSION['ultimo_acesso'] = time();
}
