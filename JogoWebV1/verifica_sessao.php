<?php
require_once __DIR__ . '/sessao.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuarioId       = (int) $_SESSION['usuario_id'];
$usuarioUsername = $_SESSION['username'] ?? '';
$usuarioNome     = $_SESSION['nome'] ?? '';
