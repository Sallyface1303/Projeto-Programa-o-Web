<?php

function destino(string $base): string
{
    return is_file(__DIR__ . "/{$base}.php") ? "{$base}.php" : "{$base}.html";
}

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (is_file(__DIR__ . '/verifica_sessao.php')) {
    require_once __DIR__ . '/verifica_sessao.php';
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ' . destino('login'));
        exit;
    }
}

require_once __DIR__ . '/conexao.php';

const LIMITE_RANKING = 50;

$niveis = ['facil' => 'FÁCIL', 'medio' => 'MÉDIO', 'dificil' => 'DIFÍCIL'];
$modos = ['normal' => 'NORMAL', 'explosivo' => 'EXPLOSIVO'];

// Filtros opcionais (GET); valores fora da lista são ignorados.
$nivelFiltro = (string) ($_GET['dificuldade'] ?? '');
$modoFiltro = (string) ($_GET['modo'] ?? '');
if (!isset($niveis[$nivelFiltro])) {
    $nivelFiltro = '';
}
if (!isset($modos[$modoFiltro])) {
    $modoFiltro = '';
}

$sql = 'SELECT u.username, p.pontuacao, p.modalidade, p.nivel_dificuldade, '
     . 'p.dimensoes_tabuleiro, p.data_hora '
     . 'FROM partidas p '
     . 'INNER JOIN usuarios u ON u.id = p.usuario_id';
$condicoes = [];
$parametros = [];

// LOWER() deixa a comparação independente de como o jogo grava o texto (FACIL/facil).
if ($nivelFiltro !== '') {
    $condicoes[] = 'LOWER(p.nivel_dificuldade) = :nivel';
    $parametros[':nivel'] = $nivelFiltro;
}
if ($modoFiltro !== '') {
    $condicoes[] = 'LOWER(p.modalidade) = :modo';
    $parametros[':modo'] = $modoFiltro;
}
if ($condicoes) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}
$sql .= ' ORDER BY p.pontuacao DESC, p.data_hora ASC LIMIT ' . LIMITE_RANKING;

$ranking = [];
$erro = '';
try {
    $consulta = $pdo->prepare($sql);
    $consulta->execute($parametros);
    $ranking = $consulta->fetchAll();
} catch (PDOException $e) {
    error_log('Falha ao carregar ranking: ' . $e->getMessage());
    $erro = 'Não foi possível carregar o ranking agora.';
}

function formatarData(string $valor): string
{
    $data = DateTime::createFromFormat('Y-m-d H:i:s', $valor);
    return $data ? $data->format('d/m/Y H:i') : $valor;
}

function rotulo(array $mapa, string $valor): string
{
    $chave = strtolower(strtr($valor, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'Á' => 'a', 'É' => 'e', 'Í' => 'i']));
    return $mapa[$chave] ?? strtoupper($valor);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="layout_ranking.css">
	<link rel="stylesheet" href="style.css">
	<link rel="icon" href="imagens/topeira_capacete.png">
	<title>Ranking global</title>
</head>
<body>
    <div class="conteiner">
        <div class="menu">
            <a href="<?= destino('main_menu') ?>" class="a">ACERTE A TOUPEIRA</a>
            <div class="lineBottom"></div>
        </div>
        <header>
            <div class="line"></div>
            <h1>RANKING GLOBAL</h1>
            <div class="line"></div>
        </header>
        <main>
            <form class="filtroDificuldade" action="global_score.php" method="get">
                <h2>DIFICULDADE:</h2>
                <input type="hidden" name="modo" value="<?= escapar($modoFiltro) ?>">
                <button type="submit" name="dificuldade" value="" <?= $nivelFiltro === '' ? 'class="ativo"' : '' ?>>TODAS</button>
                <?php foreach ($niveis as $valor => $texto): ?>
                    <button type="submit" name="dificuldade" value="<?= $valor ?>" <?= $nivelFiltro === $valor ? 'class="ativo"' : '' ?>><?= $texto ?></button>
                <?php endforeach; ?>
            </form>
            <form class="filtroModo" action="global_score.php" method="get">
                <h2>MODO:</h2>
                <input type="hidden" name="dificuldade" value="<?= escapar($nivelFiltro) ?>">
                <button type="submit" name="modo" value="" <?= $modoFiltro === '' ? 'class="ativo"' : '' ?>>TODOS</button>
                <?php foreach ($modos as $valor => $texto): ?>
                    <button type="submit" name="modo" value="<?= $valor ?>" <?= $modoFiltro === $valor ? 'class="ativo"' : '' ?>><?= $texto ?></button>
                <?php endforeach; ?>
            </form>

            <?php if ($erro !== ''): ?>
                <p class="mensagem erro" role="status"><?= escapar($erro) ?></p>
            <?php endif; ?>

            <div class="divTable">
                <table class="rankTable">
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Usuário</th>
                            <th>Pontuação</th>
                            <th>Modo</th>
                            <th>Nível</th>
                            <th>Tabuleiro</th>
                            <th>Data/hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$ranking && $erro === ''): ?>
                            <tr><td colspan="7">Nenhuma partida registrada ainda.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($ranking as $indice => $linha): ?>
                            <tr>
                                <td><?= $indice + 1 ?></td>
                                <td><?= escapar($linha['username']) ?></td>
                                <td><?= (int) $linha['pontuacao'] ?></td>
                                <td><?= escapar(rotulo($modos, $linha['modalidade'])) ?></td>
                                <td><?= escapar(rotulo($niveis, $linha['nivel_dificuldade'])) ?></td>
                                <td><?= escapar($linha['dimensoes_tabuleiro']) ?></td>
                                <td><?= escapar(formatarData($linha['data_hora'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
