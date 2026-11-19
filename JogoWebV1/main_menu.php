<?php require_once 'verifica_sessao.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style_mainmenu.css">
    <link rel="stylesheet" href="style.css">
	<link rel="icon" href="imagens/topeira_capacete.png">

    <title>Menu Principal</title>
</head>

<body>
    <div class="menu">
        <div class="titulo">
            <h1>ACERTE A TOUPEIRA</h1>
            <div class="line"></div>
            <h2>MENU PRINCIPAL</h2>
        </div>

        <div class="botoes">
            <button class="jogar" type="button" onclick="location.href='selecao.php'">
                JOGAR
            </button>
            <button type="button" onclick="location.href='global_score.php'">
                RANKING
            </button>
            <!-- <button type="button" onclick="location.href='score.php'">
                SCORE
            </button> -->
            <button type="button" onclick="location.href='perfil.php'">
                PERFIL
            </button>
            <button class="sair" type="button" onclick="location.href='logout.php'">
                SAIR
            </button>
        </div>
    </div>
</body>
</html>
