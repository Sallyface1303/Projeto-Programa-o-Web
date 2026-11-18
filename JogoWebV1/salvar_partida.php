<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$usuarioId = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? null;

if (!$usuarioId) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'erro' => 'Usuário não autenticado.']);
    exit;
}

$entrada = file_get_contents('php://input');
$dados = json_decode($entrada, true);

if (!$dados) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Dados da partida inválidos.']);
    exit;
}

require_once 'conexao.php';

$dimensoes   = trim($dados['dimensoes_tabuleiro'] ?? '2x3 (6)');
$modalidade  = trim($dados['modalidade'] ?? 'NORMAL');
$dificuldade = trim($dados['nivel_dificuldade'] ?? 'FACIL');
$pontuacao   = isset($dados['pontuacao']) ? (int)$dados['pontuacao'] : 0;
$statusFinal = ($dados['status_final'] ?? '') === 'desistencia' ? 'desistencia' : 'concluida';

try {
    $sql = "INSERT INTO partidas 
            (usuario_id, dimensoes_tabuleiro, modalidade, nivel_dificuldade, pontuacao, status_final, data_hora) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $usuarioId,
        $dimensoes,
        $modalidade,
        $dificuldade,
        $pontuacao,
        $statusFinal
    ]);

    echo json_encode(['sucesso' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao registrar partida no banco.']);
}
