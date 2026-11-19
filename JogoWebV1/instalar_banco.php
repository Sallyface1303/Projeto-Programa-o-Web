<?php
$host = 'localhost';
$usuario = 'root';
$senha = '';
$banco = 'whack_a_mole';

$mensagem = '';
$sucesso = false;

try {
    // 1. Conecta ao servidor MySQL sem selecionar banco para poder criá-lo
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $usuario, $senha, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 2. Cria o banco de dados se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$banco` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$banco`");

    // 3. Cria a tabela de usuários (com todos os campos exigidos no enunciado)
    $sqlUsuarios = "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome_completo VARCHAR(150) NOT NULL,
        data_nascimento DATE NOT NULL,
        cpf VARCHAR(14) NOT NULL UNIQUE,
        telefone VARCHAR(20) NOT NULL,
        email VARCHAR(120) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec($sqlUsuarios);

    // 4. Cria a tabela de partidas (Membro 3 - Histórico, Desistências e Ranking)
    $sqlPartidas = "CREATE TABLE IF NOT EXISTS partidas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        dimensoes_tabuleiro VARCHAR(30) NOT NULL,
        modalidade VARCHAR(30) NOT NULL,
        nivel_dificuldade VARCHAR(30) NOT NULL,
        pontuacao INT NOT NULL DEFAULT 0,
        status_final ENUM('concluida', 'desistencia') NOT NULL DEFAULT 'concluida',
        data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_partida_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec($sqlPartidas);

    // 5. Insere um jogador padrão de teste caso ainda não exista
    $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
    $stmtCheck->execute(['jogador1']);

    if (!$stmtCheck->fetch()) {
        $senhaHash = password_hash('123456', PASSWORD_DEFAULT);
        $stmtInsert = $pdo->prepare(
            "INSERT INTO usuarios (nome_completo, data_nascimento, cpf, telefone, email, username, senha) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmtInsert->execute([
            'Jogador Teste Unicamp',
            '2006-04-19',
            '123.456.789-00',
            '(19) 99999-9999',
            'teste@unicamp.br',
            'jogador1',
            $senhaHash
        ]);
    }

    $sucesso = true;
    $mensagem = "Banco de dados e tabelas (usuarios e partidas) criados com sucesso!";
} catch (PDOException $e) {
    $mensagem = "Erro ao criar o banco de dados: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação do Banco de Dados - Whack-a-Mole</title>
    <link rel="stylesheet" href="style_jogo.css">
</head>
<body>
    <main class="modal-jogo">
        <div class="modal-card">
            <h1>INSTALAÇÃO DO BANCO</h1>
            <p><?= htmlspecialchars($mensagem) ?></p>
            <?php if ($sucesso): ?>
                <div class="modal-estatisticas">
                    USUÁRIO DE TESTE CRIADO:<br>
                    Username: jogador1<br>
                    Senha: 123456
                </div>
                <div class="modal-acoes">
                    <a href="login.php" class="btn-acao primario">IR PARA O LOGIN</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
