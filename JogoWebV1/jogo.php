<!DOCTYPE html>
<html lang="pt-br" class="crt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Whack-a-Mole - Fliperama</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_jogo.css">
    <link rel="icon" href="imagens/topeira_capacete.png">
</head>
<body>
    <header class="header">
        <div class="nav-controles">
            <button type="button" class="pause" title="Pausar"></button>
            <a href="selecao.html" class="btn-topo link-saida">&lt; DESISTIR</a>
            <a href="global_score.php" class="btn-topo link-saida">RANKING GLOBAL</a>
            <a href="edicao.php" class="btn-topo link-saida">EDITAR PERFIL</a>
            <a href="titulo.html" class="btn-topo link-saida">DESCONECTAR</a>
        </div>
    </header>

    <div class="layout-arcade">
        <aside class="hud-lateral">
            <div class="hud-caixa">
                <span class="hud-titulo">PONTOS</span>
                <span class="hud-valor" id="pontValue">0</span>
            </div>
            <div class="hud-caixa">
                <span class="hud-titulo">COMBO</span>
                <span class="hud-valor destaque-combo" id="comboValue">x1</span>
            </div>
        </aside>

        <main class="jogo-container">
            <div class="jogo" id="tabuleiro"></div>
        </main>

        <aside class="hud-lateral">
            <div class="hud-caixa">
                <span class="hud-titulo">TEMPO</span>
                <span class="hud-valor destaque-tempo" id="timeValue">60s</span>
            </div>
        </aside>
    </div>

    <div class="modal-jogo" id="modalFimPause" hidden>
        <div class="modal-card">
            <h1 id="modalTitulo">JOGO PAUSADO</h1>
            <p id="modalSubtitulo">PONTUAÇÃO ATUAL: 0</p>

            <div class="modal-estatisticas" id="modalStats"></div>

            <div class="modal-acoes">
                <button type="button" class="btn-acao primario" id="btnContinuar">CONTINUAR</button>
                <button type="button" class="btn-acao primario" id="btnReiniciar">NOVA PARTIDA</button>
                <a href="selecao.html" class="btn-acao link-saida">MUDAR CONFIGURAÇÕES</a>
                <a href="score.html" class="btn-acao link-saida">MEU HISTÓRICO</a>
                <a href="global_score.php" class="btn-acao link-saida">RANKING GLOBAL</a>
                <a href="edicao.php" class="btn-acao link-saida">EDITAR DADOS PESSOAIS</a>
                <a href="main_menu.html" class="btn-acao link-saida">MENU PRINCIPAL</a>
                <a href="titulo.html" class="btn-acao link-saida">DESCONECTAR</a>
            </div>
        </div>
    </div>

    <script src="jogo.js"></script>
</body>
</html>
